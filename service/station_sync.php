<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\service;

use salvocortesiano\radioglobe\repository\station_repository;

/**
 * Sincronizzazione delle stazioni da Radio Browser.
 *
 * Il lavoro e' diviso in passi brevi (scarica una pagina, importa un
 * blocco di righe, chiude) e lo stato viene salvato su file: cosi' la
 * sincronizzazione sopravvive ai limiti di tempo degli hosting condivisi
 * e puo' essere portata avanti sia dal cron di phpBB sia dall'ACP.
 */
class station_sync
{
	/** Stazioni per pagina scaricata. */
	const PAGE_SIZE = 2500;
	/** Righe importate per passo. */
	const IMPORT_BATCH = 1000;
	/** Dopo quanti secondi un lucchetto e' considerato abbandonato. */
	const LOCK_TTL = 120;

	const FALLBACK_SERVERS = [
		'de1.api.radio-browser.info',
		'de2.api.radio-browser.info',
		'fi1.api.radio-browser.info',
	];

	protected $config;
	protected $config_text;
	protected $db;
	protected $cache;
	protected $http;
	protected $stations;
	protected $root_path;
	protected $favorites_table;
	protected $comments_table;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\cache\driver\driver_interface $cache,
		http_client $http,
		station_repository $stations,
		$root_path,
		$favorites_table,
		$comments_table
	)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->cache = $cache;
		$this->http = $http;
		$this->stations = $stations;
		$this->root_path = $root_path;
		$this->favorites_table = $favorites_table;
		$this->comments_table = $comments_table;
	}

	/* ------------------------------------------------------------------
	 * File di lavoro
	 * ---------------------------------------------------------------- */

	protected function store_dir()
	{
		$dir = $this->root_path . 'store/radioglobe/';

		if (!is_dir($dir))
		{
			@mkdir($dir, 0755, true);
			@file_put_contents($dir . 'index.htm', '');
			@file_put_contents($dir . '.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
		}

		return $dir;
	}

	protected function state_file()
	{
		return $this->store_dir() . 'sync_state.json';
	}

	protected function data_file()
	{
		return $this->store_dir() . 'sync_stations.ndjson';
	}

	public function get_state()
	{
		$file = $this->state_file();

		if (!is_file($file))
		{
			return null;
		}

		$state = json_decode((string) @file_get_contents($file), true);

		return is_array($state) ? $state : null;
	}

	protected function save_state(array $state)
	{
		@file_put_contents($this->state_file(), json_encode($state), LOCK_EX);
	}

	public function is_running()
	{
		return $this->get_state() !== null;
	}

	public function is_store_writable()
	{
		$dir = $this->store_dir();

		return is_dir($dir) && is_writable($dir);
	}

	/* ------------------------------------------------------------------
	 * Lucchetto: cron e ACP non devono lavorare insieme
	 * ---------------------------------------------------------------- */

	protected function acquire_lock()
	{
		$old = (int) $this->config['radioglobe_sync_lock'];

		if ($old > time() - self::LOCK_TTL)
		{
			return false;
		}

		return $this->config->set_atomic('radioglobe_sync_lock', $this->config['radioglobe_sync_lock'], time(), false);
	}

	/**
	 * Libera un lucchetto rimasto appeso (per esempio dopo un errore
	 * fatale), purche' non sia stato rinnovato nell'ultimo minuto.
	 */
	public function release_stale_lock()
	{
		if ((int) $this->config['radioglobe_sync_lock'] < time() - 60)
		{
			$this->release_lock();
		}
	}

	protected function release_lock()
	{
		$this->config->set('radioglobe_sync_lock', 0, false);
	}

	/* ------------------------------------------------------------------
	 * Avvio, passi, interruzione
	 * ---------------------------------------------------------------- */

	/**
	 * Prepara una nuova sincronizzazione (non scarica ancora nulla).
	 */
	public function start($source = 'manual')
	{
		if (!$this->is_store_writable())
		{
			$this->config->set('radioglobe_sync_error', 'store/radioglobe/ non scrivibile', false);
			return false;
		}

		@file_put_contents($this->data_file(), '');

		$this->save_state([
			'phase'		=> 'download',
			'source'	=> $source,
			'started'	=> time(),
			'offset'	=> 0,
			'collected'	=> 0,
			'pos'		=> 0,
			'imported'	=> 0,
			'inserted'	=> 0,
			'updated'	=> 0,
			'servers'	=> $this->discover_servers(),
			'server_i'	=> 0,
			'retries'	=> 0,
		]);

		$this->config->set('radioglobe_sync_error', '', false);

		return true;
	}

	/**
	 * Annulla una sincronizzazione in corso.
	 */
	public function abort($error = '')
	{
		@unlink($this->state_file());
		@unlink($this->data_file());
		$this->release_lock();

		if ($error !== '')
		{
			$this->config->set('radioglobe_sync_error', utf8_substr($error, 0, 250), false);
		}
	}

	/**
	 * Esegue passi finche' c'e' tempo.
	 *
	 * @param int $seconds
	 * @return array|null stato dopo l'ultimo passo, null a lavoro finito
	 */
	public function run_for($seconds, $max_steps = 0)
	{
		$steps = 0;

		$until = microtime(true) + max(1, (int) $seconds);

		if (!$this->acquire_lock())
		{
			return $this->get_state();
		}

		@set_time_limit(max(60, (int) $seconds + 60));

		$state = $this->get_state();

		try
		{
			while ($state !== null && microtime(true) < $until)
			{
				$state = $this->step($state);
				// il lucchetto viene rinnovato a ogni passo
				$this->config->set('radioglobe_sync_lock', time(), false);

				if ($max_steps > 0 && ++$steps >= $max_steps)
				{
					break;
				}
			}
		}
		catch (\Exception $e)
		{
			$this->abort($e->getMessage());
			return null;
		}

		$this->release_lock();

		return $state;
	}

	/**
	 * Un singolo passo della macchina a stati.
	 *
	 * @param array $state
	 * @return array|null
	 */
	protected function step(array $state)
	{
		switch ($state['phase'])
		{
			case 'download':
				$state = $this->step_download($state);
			break;

			case 'import':
				$state = $this->step_import($state);
			break;

			case 'finalize':
				$this->step_finalize($state);
				return null;

			default:
				$this->abort('stato non valido');
				return null;
		}

		if ($state !== null)
		{
			$this->save_state($state);
		}

		return $state;
	}

	/* ------------------------------------------------------------------
	 * Fase 1: download a pagine
	 * ---------------------------------------------------------------- */

	protected function step_download(array $state)
	{
		$servers = $state['servers'];
		$server = $servers[$state['server_i'] % count($servers)];
		$max = max(100, (int) $this->config['radioglobe_max_stations']);

		$params = [
			'has_geo_info'	=> 'true',
			'hidebroken'	=> 'true',
			'order'			=> 'clickcount',
			'reverse'		=> 'true',
			'offset'		=> (int) $state['offset'],
			'limit'			=> self::PAGE_SIZE,
		];

		if (!empty($this->config['radioglobe_https_only']))
		{
			$params['is_https'] = 'true';
		}

		if ((int) $this->config['radioglobe_min_bitrate'] > 0)
		{
			$params['bitrateMin'] = (int) $this->config['radioglobe_min_bitrate'];
		}

		$url = 'https://' . $server . '/json/stations/search?' . http_build_query($params);
		$response = $this->http->get($url, ['Accept: application/json'], 90);
		$data = ($response['status'] === 200) ? json_decode($response['body'], true) : null;

		if (!is_array($data))
		{
			// si prova il server successivo; dopo un giro completo si rinuncia
			$state['server_i']++;
			$state['retries']++;

			if ($state['retries'] >= count($servers) + 1)
			{
				$this->abort('Radio Browser non raggiungibile (' . $server . '): HTTP ' . $response['status'] . ' ' . $response['error']);
				return null;
			}

			return $state;
		}

		$state['retries'] = 0;
		$tags = $this->get_tag_filter();
		$grid = max(5, (int) $this->config['radioglobe_cluster_grid']) / 100;
		$exclude_hls = !empty($this->config['radioglobe_exclude_hls']);
		$https_only = !empty($this->config['radioglobe_https_only']);

		$lines = '';

		foreach ($data as $st)
		{
			if ($state['collected'] >= $max)
			{
				break;
			}

			$row = $this->normalize($st, $tags, $grid, $exclude_hls, $https_only);

			if ($row !== null)
			{
				$lines .= json_encode($row) . "\n";
				$state['collected']++;
			}
		}

		if ($lines !== '')
		{
			@file_put_contents($this->data_file(), $lines, FILE_APPEND | LOCK_EX);
		}

		$state['offset'] += self::PAGE_SIZE;

		if (count($data) < self::PAGE_SIZE || $state['collected'] >= $max)
		{
			if ($state['collected'] === 0)
			{
				$this->abort('Nessuna stazione corrisponde ai filtri impostati.');
				return null;
			}

			$state['phase'] = 'import';
		}

		return $state;
	}

	/**
	 * Converte una stazione di Radio Browser nella riga da salvare.
	 * Restituisce null se la stazione va scartata.
	 */
	protected function normalize(array $st, array $tags, $grid, $exclude_hls, $https_only)
	{
		if (empty($st['stationuuid']) || (isset($st['lastcheckok']) && (int) $st['lastcheckok'] !== 1))
		{
			return null;
		}

		if (!isset($st['geo_lat'], $st['geo_long']) || !is_numeric($st['geo_lat']) || !is_numeric($st['geo_long']))
		{
			return null;
		}

		$lat = (float) $st['geo_lat'];
		$lng = (float) $st['geo_long'];

		if (($lat == 0 && $lng == 0) || abs($lat) > 90 || abs($lng) > 180)
		{
			return null;
		}

		if ($exclude_hls && !empty($st['hls']))
		{
			return null;
		}

		$url = !empty($st['url_resolved']) ? trim($st['url_resolved']) : trim((string) $st['url']);

		if (!preg_match('#^https?://#i', $url) || strlen($url) > 2000)
		{
			return null;
		}

		$is_https = (stripos($url, 'https://') === 0);

		if ($https_only && !$is_https)
		{
			return null;
		}

		$station_tags = array_values(array_filter(array_map(function ($t) {
			return utf8_strtolower(trim($t));
		}, explode(',', (string) $st['tags']))));

		if (!empty($tags) && !$this->tags_match($station_tags, $tags))
		{
			return null;
		}

		$cc = strtoupper(substr(preg_replace('#[^A-Za-z]#', '', (string) $st['countrycode']), 0, 2));
		$key = ($cc !== '' ? $cc : 'XX') . '_' . (int) floor($lat / $grid) . '_' . (int) floor($lng / $grid);

		$favicon = trim((string) $st['favicon']);

		if (!preg_match('#^https?://#i', $favicon))
		{
			$favicon = '';
		}

		$homepage = trim((string) $st['homepage']);

		if (!preg_match('#^https?://#i', $homepage))
		{
			$homepage = '';
		}

		$country = isset($st['country']) ? (string) $st['country'] : '';

		return [
			'station_uuid'	=> substr((string) $st['stationuuid'], 0, 36),
			'station_name'	=> $this->clean(isset($st['name']) ? $st['name'] : '', 255),
			'stream_url'	=> $url,
			'homepage'		=> $homepage,
			'favicon'		=> $favicon,
			'tags'			=> $this->clean(implode(',', $station_tags), 255),
			'country'		=> $this->clean($country, 100),
			'countrycode'	=> $cc,
			'state'			=> $this->clean(isset($st['state']) ? $st['state'] : '', 100),
			'language'		=> $this->clean(isset($st['language']) ? $st['language'] : '', 100),
			'codec'			=> substr(preg_replace('#[^A-Za-z0-9+\-/.]#', '', (string) $st['codec']), 0, 20),
			'bitrate'		=> min(65535, max(0, (int) $st['bitrate'])),
			'is_https'		=> $is_https ? 1 : 0,
			'geo_lat'		=> (int) round($lat * station_repository::GEO_SCALE),
			'geo_long'		=> (int) round($lng * station_repository::GEO_SCALE),
			'place_key'		=> $key,
			'votes'			=> max(0, (int) $st['votes']),
			'clicks'		=> max(0, (int) $st['clickcount']),
		];
	}

	protected function clean($text, $max)
	{
		// nomi e tag possono arrivare con entita' HTML (&#1050; &amp; ...)
		$text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = self::strip_4byte($text);
		$text = trim(preg_replace('#\s+#u', ' ', $text));
		$text = utf8_normalize_nfc($text);

		return utf8_substr($text, 0, $max);
	}

	/**
	 * Toglie i caratteri UTF-8 a 4 byte (emoji e simili).
	 *
	 * Molti database MySQL dei forum phpBB usano la codifica "utf8" a 3
	 * byte, che rifiuta questi caratteri con l'errore 1366 "Incorrect
	 * string value". Nei nomi e nei tag delle radio non servono.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function strip_4byte($text)
	{
		$clean = @preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', (string) $text);

		if ($clean === null)
		{
			// sequenza UTF-8 non valida: si tengono solo i byte sicuri
			$clean = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', (string) $text);
			$clean = htmlspecialchars_decode(htmlspecialchars($clean, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_NOQUOTES);
		}

		return $clean;
	}

	protected function tags_match(array $station_tags, array $filter)
	{
		foreach ($station_tags as $tag)
		{
			foreach ($filter as $wanted)
			{
				if ($tag === $wanted || strpos($tag, $wanted) !== false)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Generi ammessi, dall'ACP (uno per riga o separati da virgola).
	 *
	 * @return array
	 */
	public function get_tag_filter()
	{
		$raw = (string) $this->config_text->get('radioglobe_tags');
		$list = preg_split('#[\r\n,]+#', $raw);

		return array_values(array_unique(array_filter(array_map(function ($t) {
			return utf8_strtolower(trim($t));
		}, $list))));
	}

	/* ------------------------------------------------------------------
	 * Fase 2: importazione a blocchi
	 * ---------------------------------------------------------------- */

	protected function step_import(array $state)
	{
		$handle = @fopen($this->data_file(), 'rb');

		if (!$handle)
		{
			$this->abort('file temporaneo della sincronizzazione mancante');
			return null;
		}

		fseek($handle, (int) $state['pos']);

		$rows = [];

		while (count($rows) < self::IMPORT_BATCH && ($line = fgets($handle)) !== false)
		{
			$row = json_decode($line, true);

			if (is_array($row) && !empty($row['station_uuid']))
			{
				// eventuali doppioni nello stesso blocco: vince l'ultimo
				$rows[$row['station_uuid']] = $row;
			}
		}

		$state['pos'] = ftell($handle);
		$eof = feof($handle);
		fclose($handle);

		if (!empty($rows))
		{
			$this->import_rows($rows, $state);
		}

		if ($eof || empty($rows))
		{
			$state['phase'] = 'finalize';
		}

		return $state;
	}

	protected function import_rows(array $rows, array &$state)
	{
		$existing = $this->stations->get_hashes(array_keys($rows));
		$now = (int) $state['started'];

		$insert = [];
		$touch = [];

		$this->db->sql_transaction('begin');

		foreach ($rows as $uuid => $row)
		{
			// le righe scaricate da una versione precedente possono
			// contenere ancora emoji: si ripuliscono qui
			foreach ($row as $field => $value)
			{
				if (is_string($value))
				{
					$row[$field] = self::strip_4byte($value);
				}
			}

			$hash = md5(json_encode($row));

			if (isset($existing[$uuid]))
			{
				$old = $existing[$uuid];

				if ($old['station_hash'] === $hash)
				{
					$touch[] = (int) $old['station_id'];
				}
				else
				{
					$row['station_hash'] = $hash;
					$row['station_active'] = 1;
					$row['last_seen'] = $now;
					unset($row['station_uuid']);

					$this->db->sql_return_on_error(true);
					$this->stations->update_station((int) $old['station_id'], $row);
					$failed = $this->db->get_sql_error_triggered();
					$this->db->sql_return_on_error(false);

					if ($failed)
					{
						$state['skipped'] = (isset($state['skipped']) ? $state['skipped'] : 0) + 1;
					}
					else
					{
						$state['updated']++;
					}
				}
			}
			else
			{
				$row['station_hash'] = $hash;
				$row['station_active'] = 1;
				$row['last_seen'] = $now;
				$insert[] = $row;
				$state['inserted']++;
			}

			$state['imported']++;
		}

		if (!empty($touch))
		{
			$this->stations->touch_many($touch, $now);
		}

		$this->db->sql_transaction('commit');

		foreach (array_chunk($insert, 250) as $chunk)
		{
			$this->insert_safely($chunk, $state);
		}
	}

	/**
	 * Inserisce un blocco di stazioni. Se il database rifiuta il blocco,
	 * le righe vengono ritentate una per una e quelle non valide saltate:
	 * una sola stazione difettosa non deve fermare l'aggiornamento.
	 */
	protected function insert_safely(array $chunk, array &$state)
	{
		$this->db->sql_return_on_error(true);
		$this->stations->insert_many($chunk);
		$failed = $this->db->get_sql_error_triggered();
		$this->db->sql_return_on_error(false);

		if (!$failed)
		{
			return;
		}

		foreach ($chunk as $row)
		{
			$this->db->sql_return_on_error(true);
			$this->stations->insert_many([$row]);
			$bad = $this->db->get_sql_error_triggered();
			$this->db->sql_return_on_error(false);

			if ($bad)
			{
				$state['skipped'] = (isset($state['skipped']) ? $state['skipped'] : 0) + 1;
				$state['inserted']--;
			}
		}
	}

	/**
	 * Avanzamento per la barra in ACP.
	 *
	 * Download 0-45% (sulle stazioni raccolte rispetto al massimo),
	 * importazione 45-95% (sui byte letti del file), chiusura 95-100%.
	 *
	 * @param array|null $state
	 * @return int
	 */
	public function get_percent($state)
	{
		if (!is_array($state))
		{
			return 100;
		}

		switch ($state['phase'])
		{
			case 'download':
				// stima sul totale dell'ultimo aggiornamento, se c'e'
				$max = max(100, (int) $this->config['radioglobe_max_stations']);
				$last = (int) $this->config['radioglobe_sync_count'];
				$expected = ($last > 0) ? min($max, (int) ceil($last * 1.05)) : $max;
				return (int) floor(45 * min(1, $state['collected'] / $expected));

			case 'import':
				$size = @filesize($this->data_file());
				$ratio = $size ? min(1, $state['pos'] / $size) : 0;
				return 45 + (int) floor(50 * $ratio);

			default:
				return 97;
		}
	}

	/* ------------------------------------------------------------------
	 * Fase 3: chiusura
	 * ---------------------------------------------------------------- */

	protected function step_finalize(array $state)
	{
		$this->stations->retire_missing((int) $state['started'], $this->favorites_table, $this->comments_table);
		$places = $this->stations->rebuild_places();

		$this->config->set('radioglobe_sync_last', time(), false);
		$this->config->set('radioglobe_sync_count', $this->stations->count_active(), false);
		$this->config->set('radioglobe_sync_places', $places, false);
		$this->config->set('radioglobe_sync_duration', time() - (int) $state['started'], false);
		$this->config->set('radioglobe_sync_error', '', false);
		$this->config->increment('radioglobe_data_version', 1, false);

		$this->cache->destroy('_radioglobe_places');

		@unlink($this->state_file());
		@unlink($this->data_file());
	}

	/* ------------------------------------------------------------------
	 * Server di Radio Browser
	 * ---------------------------------------------------------------- */

	/**
	 * Come raccomandato dalla documentazione di Radio Browser: si
	 * risolve all.api.radio-browser.info e si fa la ricerca inversa di
	 * ogni indirizzo per ottenere i nomi dei server attivi.
	 *
	 * @return array
	 */
	public function discover_servers()
	{
		$custom = trim((string) $this->config['radioglobe_api_server']);

		if ($custom !== '')
		{
			$custom = preg_replace('#^https?://#i', '', rtrim($custom, '/'));

			return [$custom];
		}

		$servers = [];
		$ips = @gethostbynamel('all.api.radio-browser.info');

		if (is_array($ips))
		{
			foreach ($ips as $ip)
			{
				$name = @gethostbyaddr($ip);

				if ($name && $name !== $ip && substr($name, -strlen('.radio-browser.info')) === '.radio-browser.info')
				{
					$servers[$name] = true;
				}
			}
		}

		$servers = array_keys($servers);

		if (empty($servers))
		{
			$servers = self::FALLBACK_SERVERS;
		}

		shuffle($servers);

		return array_values($servers);
	}

	/**
	 * Prossima esecuzione pianificata, per il riepilogo in ACP.
	 *
	 * @return int timestamp (0 se il cron e' spento)
	 */
	public function next_scheduled_time()
	{
		$hours = self::parse_hours($this->config['radioglobe_cron_hours']);

		if (empty($this->config['radioglobe_cron_enabled']) || empty($hours))
		{
			return 0;
		}

		$tz = $this->board_timezone();
		$now = new \DateTime('now', $tz);

		for ($day = 0; $day <= 1; $day++)
		{
			foreach ($hours as $h)
			{
				$candidate = new \DateTime('today', $tz);
				$candidate->modify('+' . $day . ' day');
				$candidate->setTime($h, 0, 0);

				if ($candidate > $now)
				{
					return $candidate->getTimestamp();
				}
			}
		}

		return 0;
	}

	/**
	 * Ultima esecuzione pianificata gia' passata.
	 *
	 * @return int timestamp
	 */
	public function last_scheduled_time()
	{
		$hours = self::parse_hours($this->config['radioglobe_cron_hours']);

		if (empty($hours))
		{
			return 0;
		}

		$tz = $this->board_timezone();
		$now = new \DateTime('now', $tz);
		$best = 0;

		for ($day = 0; $day >= -1; $day--)
		{
			foreach ($hours as $h)
			{
				$candidate = new \DateTime('today', $tz);
				$candidate->modify($day . ' day');
				$candidate->setTime($h, 0, 0);

				if ($candidate <= $now && $candidate->getTimestamp() > $best)
				{
					$best = $candidate->getTimestamp();
				}
			}
		}

		return $best;
	}

	protected function board_timezone()
	{
		try
		{
			return new \DateTimeZone($this->config['board_timezone'] ?: 'UTC');
		}
		catch (\Exception $e)
		{
			return new \DateTimeZone('UTC');
		}
	}

	/**
	 * "16,4,4,25" -> [4, 16]
	 *
	 * @param string $value
	 * @return array
	 */
	public static function parse_hours($value)
	{
		$hours = [];

		foreach (explode(',', (string) $value) as $h)
		{
			$h = trim($h);

			if ($h !== '' && ctype_digit($h) && (int) $h >= 0 && (int) $h <= 23)
			{
				$hours[(int) $h] = (int) $h;
			}
		}

		sort($hours);

		return array_values($hours);
	}
}
