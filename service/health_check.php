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
 * Rapporto di verifica (ACP > Radio Globe > Rapporto di verifica).
 *
 * Un controllo completo dell'estensione, una sezione per ogni passo della barra di avanzamento:
 * ambiente, file, cartella dei file scaricati, database, indirizzi, stazioni, citta', rete e
 * prove reali. Non scrive nulla ne' nel database ne' nei file: si puo' eseguire in qualsiasi
 * momento anche su un forum attivo.
 *
 * Ogni riga e' [stato, etichetta, valore] con stato 'ok', 'warn', 'error' o 'info'.
 */
class health_check
{
	const SECTIONS = ['environment', 'files', 'store', 'database', 'routes', 'stations', 'cities', 'network', 'live'];

	/** Chiavi di configurazione create da ogni migrazione: se mancano, le migrazioni non sono state eseguite. */
	const MIGRATION_KEYS = [
		'add_config'			=> 'radioglobe_sync_last',
		'add_player_opacity'	=> 'radioglobe_player_opacity',
		'add_globe_markers'		=> 'radioglobe_markers',
		'add_nogeo_stations'	=> 'radioglobe_nogeo',
		'add_dot_color'			=> 'radioglobe_dot_color',
		'add_listen_toast'		=> 'radioglobe_toast_enabled',
		'add_cover_spin'		=> 'radioglobe_cover_spin',
		'add_cities_module'		=> 'radioglobe_cities_updated',
		'add_cities_auto'		=> 'radioglobe_cities_max_months',
		'add_player_resume'		=> 'radioglobe_player_resume',
	];

	const PERMISSIONS = ['u_radioglobe_listen', 'u_radioglobe_favorite', 'u_radioglobe_comment', 'u_radioglobe_announce', 'm_radioglobe_comments', 'm_radioglobe_stations'];

	const ACP_MODES = ['settings', 'groups', 'sync', 'cities', 'comments', 'report'];

	/** File temporanei che restano solo se un aggiornamento si e' interrotto. */
	const LEFTOVERS = ['cities15000.zip.part', 'cities15000.txt', 'cities_rows.tmp', 'cities.tsv.new'];

	protected $config;
	protected $db;
	protected $http;
	protected $stations;
	protected $sync;
	protected $cities;
	protected $helper;
	protected $user;
	protected $root_path;
	protected $tables;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		http_client $http,
		station_repository $stations,
		station_sync $sync,
		city_update $cities,
		\phpbb\controller\helper $helper,
		\phpbb\user $user,
		$root_path,
		$stations_table,
		$places_table,
		$favorites_table,
		$comments_table,
		$listening_table
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->http = $http;
		$this->stations = $stations;
		$this->sync = $sync;
		$this->cities = $cities;
		$this->helper = $helper;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->tables = [
			'stations'	=> $stations_table,
			'places'	=> $places_table,
			'favorites'	=> $favorites_table,
			'comments'	=> $comments_table,
			'listening'	=> $listening_table,
		];
	}

	public function count()
	{
		return count(self::SECTIONS);
	}

	/**
	 * Esegue una sezione. Un errore imprevisto diventa una riga del rapporto, non una pagina bianca.
	 *
	 * @return array ['key' => string, 'title' => string, 'rows' => [[stato, etichetta, valore], ...]]
	 */
	public function run($index)
	{
		$key = isset(self::SECTIONS[$index]) ? self::SECTIONS[$index] : '';
		$rows = [];

		if ($key !== '')
		{
			@set_time_limit(90);

			try
			{
				$rows = $this->{'check_' . $key}();
			}
			catch (\Throwable $e)
			{
				$rows[] = ['error', $this->l('HC_UNEXPECTED'), get_class($e) . ': ' . $e->getMessage()];
			}
		}

		return ['key' => $key, 'title' => $this->l('HC_SECTION_' . strtoupper($key)), 'rows' => $rows];
	}

	/* ------------------------------------------------------------------
	 * 1. Ambiente
	 * ---------------------------------------------------------------- */

	protected function check_environment()
	{
		$meta = $this->composer();
		$rows = [];

		$rows[] = ['info', $this->l('HC_EXT_VERSION'), isset($meta['version']) ? $meta['version'] : '?'];

		$phpbb = (string) $this->config['version'];
		$rows[] = [version_compare($phpbb, '3.3.0', '>=') ? 'ok' : 'error', $this->l('HC_PHPBB_VERSION'), $phpbb];

		$rows[] = [version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'error', $this->l('HC_PHP_VERSION'), PHP_VERSION];

		if (function_exists('curl_version'))
		{
			$curl = curl_version();
			$rows[] = ['ok', $this->l('HC_CURL'), $curl['version'] . ' / ' . (isset($curl['ssl_version']) ? $curl['ssl_version'] : '-')];
		}
		else
		{
			$rows[] = [ini_get('allow_url_fopen') ? 'warn' : 'error', $this->l('HC_CURL'), $this->l(ini_get('allow_url_fopen') ? 'HC_CURL_STREAMS' : 'HC_CURL_NONE')];
		}

		$rows[] = [function_exists('mb_strtolower') ? 'ok' : 'warn', 'mbstring', $this->l(function_exists('mb_strtolower') ? 'HC_PRESENT' : 'HC_MBSTRING_MISSING')];
		$rows[] = ['info', 'intl', $this->l(class_exists('Normalizer') ? 'HC_PRESENT' : 'HC_INTL_MISSING')];
		$rows[] = [function_exists('gzinflate') ? 'ok' : 'warn', 'zlib', $this->l(function_exists('gzinflate') ? 'HC_PRESENT' : 'HC_ZLIB_MISSING')];
		$rows[] = ['info', 'zip', $this->l(class_exists('ZipArchive') ? 'HC_PRESENT' : 'HC_ZIP_MISSING')];

		$memory = (string) ini_get('memory_limit');
		$bytes = $this->bytes($memory);
		$rows[] = [($bytes > 0 && $bytes < 64 * 1048576) ? 'warn' : 'info', $this->l('HC_MEMORY'), $memory . (($bytes > 0 && $bytes < 64 * 1048576) ? ' — ' . $this->l('HC_MEMORY_LOW') : '')];
		$rows[] = ['info', $this->l('HC_MAX_TIME'), ((int) ini_get('max_execution_time')) . ' s'];
		$rows[] = ['info', $this->l('HC_SERVER_TIME'), gmdate('Y-m-d H:i:s') . ' UTC'];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 2. File dell'estensione
	 * ---------------------------------------------------------------- */

	protected function check_files()
	{
		$dir = $this->ext_dir();
		$rows = [['info', $this->l('HC_EXT_PATH'), $this->short_path($dir)]];

		$manifest = json_decode((string) @file_get_contents($dir . 'checksums.json'), true);

		if (!is_array($manifest) || empty($manifest['files']))
		{
			$rows[] = ['warn', $this->l('HC_MANIFEST'), $this->l('HC_MANIFEST_MISSING')];
			return $rows;
		}

		$meta = $this->composer();
		$version = isset($meta['version']) ? $meta['version'] : '?';
		$rows[] = [$manifest['version'] === $version ? 'ok' : 'error', $this->l('HC_MANIFEST_VERSION'),
			$manifest['version'] === $version ? $version : $this->l('HC_MANIFEST_VERSION_DIFF', $manifest['version'], $version)];

		$missing = [];
		$changed = [];
		$size = 0;

		foreach ($manifest['files'] as $path => $hash)
		{
			$file = $dir . $path;

			if (!is_file($file))
			{
				$missing[] = $path;
				continue;
			}

			$size += filesize($file);

			if (self::file_hash($file) !== $hash)
			{
				$changed[] = $path;
			}
		}

		$total = count($manifest['files']);
		$rows[] = [empty($missing) ? 'ok' : 'error', $this->l('HC_FILES_PRESENT'),
			empty($missing) ? $this->l('HC_FILES_ALL', $total, $this->size($size)) : $this->l('HC_FILES_MISSING', count($missing), $total) . ': ' . $this->short_list($missing)];
		$rows[] = [empty($changed) ? 'ok' : 'warn', $this->l('HC_FILES_INTACT'),
			empty($changed) ? $this->l('HC_FILES_INTACT_ALL') : $this->l('HC_FILES_CHANGED', count($changed)) . ': ' . $this->short_list($changed)];

		// file in piu' (per esempio rimasti da versioni vecchie): di solito innocui
		$extra = [];
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

		foreach ($it as $file)
		{
			$path = str_replace('\\', '/', substr($file->getPathname(), strlen($dir)));

			if ($file->isFile() && $path !== 'checksums.json' && !isset($manifest['files'][$path]) && strpos($path, '.git') !== 0)
			{
				$extra[] = $path;
			}
		}

		$rows[] = ['info', $this->l('HC_FILES_EXTRA'), empty($extra) ? $this->l('HC_NONE') : count($extra) . ': ' . $this->short_list($extra)];

		return $rows;
	}

	/**
	 * Impronta di un file. Nei file di testo i fine riga non contano: i programmi FTP in modalita'
	 * testo li convertono e il file risulterebbe modificato pur essendo quello giusto. Il testo si
	 * riconosce dal contenuto, non dall'estensione, cosi' vale anche per file come LICENSE.
	 */
	public static function file_hash($file)
	{
		$data = (string) @file_get_contents($file);

		if (strpos($data, "\0") === false && preg_match('//u', $data))
		{
			$data = str_replace("\r\n", "\n", $data);
		}

		return md5($data);
	}

	/* ------------------------------------------------------------------
	 * 3. Cartella dei file scaricati
	 * ---------------------------------------------------------------- */

	protected function check_store()
	{
		$dir = $this->root_path . 'store/radioglobe/';
		$rows = [['info', $this->l('HC_STORE_PATH'), $this->short_path(is_dir($dir) ? realpath($dir) . '/' : $dir)]];

		if (!is_dir($dir))
		{
			$rows[] = ['warn', $this->l('HC_STORE_EXISTS'), $this->l('HC_STORE_NOT_YET')];
			return $rows;
		}

		$rows[] = [is_writable($dir) ? 'ok' : 'error', $this->l('HC_STORE_WRITABLE'), $this->l(is_writable($dir) ? 'HC_YES' : 'HC_STORE_READONLY')];
		$rows[] = [is_file($dir . '.htaccess') ? 'ok' : 'warn', $this->l('HC_STORE_PROTECTED'), $this->l(is_file($dir . '.htaccess') ? 'HC_STORE_HTACCESS' : 'HC_STORE_OPEN')];

		$free = function_exists('disk_free_space') ? @disk_free_space($dir) : false;

		if ($free !== false)
		{
			$rows[] = [$free < 50 * 1048576 ? 'warn' : 'ok', $this->l('HC_DISK_FREE'), $this->size($free)];
		}

		$running = $this->sync->is_running() || $this->cities->is_running();
		$files = [];

		foreach (scandir($dir) ?: [] as $name)
		{
			if ($name === '.' || $name === '..' || $name === 'index.htm' || $name === '.htaccess' || !is_file($dir . $name))
			{
				continue;
			}

			$files[] = $name;
			$leftover = !$running && in_array($name, self::LEFTOVERS, true);
			$rows[] = [$leftover ? 'warn' : 'info', $name,
				$this->size(filesize($dir . $name)) . ' · ' . $this->user->format_date(filemtime($dir . $name)) . ($leftover ? ' — ' . $this->l('HC_LEFTOVER') : '')];
		}

		if (empty($files))
		{
			$rows[] = ['info', $this->l('HC_STORE_FILES'), $this->l('HC_NONE')];
		}

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 4. Database
	 * ---------------------------------------------------------------- */

	protected function check_database()
	{
		$rows = [];

		foreach ($this->tables as $key => $table)
		{
			$count = $this->count_rows($table);
			$rows[] = [$count === null ? 'error' : 'ok', $this->l('HC_TABLE') . ' ' . $table,
				$count === null ? $this->l('HC_TABLE_MISSING') : $this->l('HC_ROWS', number_format($count, 0, ',', '.'))];
		}

		$missing = [];

		foreach (self::MIGRATION_KEYS as $migration => $key)
		{
			if (!isset($this->config[$key]))
			{
				$missing[] = $migration;
			}
		}

		$rows[] = [empty($missing) ? 'ok' : 'error', $this->l('HC_MIGRATIONS'),
			empty($missing) ? $this->l('HC_MIGRATIONS_OK', count(self::MIGRATION_KEYS)) : $this->l('HC_MIGRATIONS_MISSING', implode(', ', $missing))];

		// permessi dell'estensione
		$sql = 'SELECT auth_option FROM ' . ACL_OPTIONS_TABLE . ' WHERE ' . $this->db->sql_in_set('auth_option', self::PERMISSIONS);
		$result = $this->db->sql_query($sql);
		$found = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$found[] = $row['auth_option'];
		}
		$this->db->sql_freeresult($result);

		$lost = array_diff(self::PERMISSIONS, $found);
		$rows[] = [empty($lost) ? 'ok' : 'error', $this->l('HC_PERMISSIONS'),
			empty($lost) ? $this->l('HC_PERMISSIONS_OK', count(self::PERMISSIONS)) : $this->l('HC_PERMISSIONS_MISSING', implode(', ', $lost))];

		// voci del menu ACP
		$sql = 'SELECT module_mode FROM ' . MODULES_TABLE . "
			WHERE module_class = 'acp'
				AND module_basename = '" . $this->db->sql_escape('\salvocortesiano\radioglobe\acp\main_module') . "'";
		$result = $this->db->sql_query($sql);
		$modes = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$modes[] = $row['module_mode'];
		}
		$this->db->sql_freeresult($result);

		$lost = array_diff(self::ACP_MODES, $modes);
		$rows[] = [empty($lost) ? 'ok' : 'error', $this->l('HC_MODULES'),
			empty($lost) ? $this->l('HC_MODULES_OK', count(self::ACP_MODES)) : $this->l('HC_MODULES_MISSING', implode(', ', $lost))];

		// commenti e preferiti di stazioni che non esistono piu'
		$orphans = 0;

		foreach (['comments', 'favorites'] as $key)
		{
			$result = $this->db->sql_query('SELECT COUNT(*) AS n FROM ' . $this->tables[$key] . ' x
				LEFT JOIN ' . $this->tables['stations'] . ' s ON (s.station_id = x.station_id)
				WHERE s.station_id IS NULL');
			$orphans += (int) $this->db->sql_fetchfield('n');
			$this->db->sql_freeresult($result);
		}

		$rows[] = [$orphans ? 'warn' : 'ok', $this->l('HC_ORPHANS'), $orphans ? $this->l('HC_ORPHANS_FOUND', $orphans) : $this->l('HC_NONE')];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 5. Indirizzi e integrazione con phpBB
	 * ---------------------------------------------------------------- */

	protected function check_routes()
	{
		$names = [
			'salvocortesiano_radioglobe_page' => [], 'salvocortesiano_radioglobe_places' => [],
			'salvocortesiano_radioglobe_place' => ['place_key' => 'IT_C'], 'salvocortesiano_radioglobe_search' => [],
			'salvocortesiano_radioglobe_station' => ['station_id' => 1], 'salvocortesiano_radioglobe_nowplaying' => ['station_id' => 1],
			'salvocortesiano_radioglobe_favorites' => [], 'salvocortesiano_radioglobe_favorite_toggle' => [],
			'salvocortesiano_radioglobe_comments' => ['station_id' => 1], 'salvocortesiano_radioglobe_comment_add' => [],
			'salvocortesiano_radioglobe_comment_delete' => [], 'salvocortesiano_radioglobe_listen' => [],
			'salvocortesiano_radioglobe_listening' => [],
		];

		$bad = [];
		$page = '';

		foreach ($names as $name => $params)
		{
			try
			{
				$url = (string) $this->helper->route($name, $params, false);

				if ($name === 'salvocortesiano_radioglobe_page')
				{
					$page = $url;
				}
			}
			catch (\Exception $e)
			{
				$bad[] = str_replace('salvocortesiano_radioglobe_', '', $name);
			}
		}

		$rows = [];
		$rows[] = [empty($bad) ? 'ok' : 'error', $this->l('HC_ROUTES'),
			empty($bad) ? $this->l('HC_ROUTES_OK', count($names)) : $this->l('HC_ROUTES_BAD', count($bad), count($names)) . ': ' . implode(', ', $bad)];

		if ($page !== '')
		{
			$rows[] = ['info', $this->l('HC_PAGE_URL'), preg_replace('#[?&]sid=[0-9a-f]+#', '', $page)];
		}

		$rows[] = ['info', $this->l('HC_PLAYER_EVERYWHERE'), $this->l(!empty($this->config['radioglobe_player_everywhere']) ? 'HC_YES' : 'HC_NO')];
		$rows[] = ['info', $this->l('HC_NAV_LINK'), $this->l(!empty($this->config['radioglobe_nav_link']) ? 'HC_YES' : 'HC_NO')];
		$rows[] = ['info', $this->l('HC_TOAST'), $this->l(!empty($this->config['radioglobe_toast_enabled']) ? 'HC_YES' : 'HC_NO')];
		$rows[] = ['info', $this->l('HC_COMMENTS'), $this->l(!empty($this->config['radioglobe_comments_enabled']) ? 'HC_YES' : 'HC_NO')];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 6. Stazioni radio
	 * ---------------------------------------------------------------- */

	protected function check_stations()
	{
		$rows = [];
		$last = (int) $this->config['radioglobe_sync_last'];
		$cron = !empty($this->config['radioglobe_cron_enabled']);

		if (!$last)
		{
			$rows[] = ['warn', $this->l('HC_SYNC_LAST'), $this->l('HC_SYNC_NEVER')];
		}
		else
		{
			$old = time() - $last > 3 * 86400;
			$rows[] = [$old && $cron ? 'warn' : 'ok', $this->l('HC_SYNC_LAST'), $this->user->format_date($last) . ' (' . $this->ago($last) . ')'];
		}

		$active = $this->stations->count_active();
		$rows[] = [$active > 0 ? 'ok' : 'warn', $this->l('HC_STATIONS_ACTIVE'), number_format($active, 0, ',', '.')];
		$rows[] = ['info', $this->l('HC_PLACES'), number_format((int) $this->config['radioglobe_sync_places'], 0, ',', '.')];

		$max = (int) $this->config['radioglobe_max_stations'];
		$limit = $max > 0 && (int) $this->config['radioglobe_sync_count'] >= $max * 0.98;
		$rows[] = [$limit ? 'warn' : 'info', $this->l('HC_MAX_STATIONS'), number_format($max, 0, ',', '.') . ($limit ? ' — ' . $this->l('HC_MAX_REACHED') : '')];

		$state = $this->sync->get_state();

		if ($state !== null)
		{
			$stale = (int) $this->config['radioglobe_sync_lock'] < time() - 600 && (int) $state['started'] < time() - 3600;
			$rows[] = [$stale ? 'warn' : 'info', $this->l('HC_SYNC_RUNNING'), $this->sync->get_percent($state) . '%' . ($stale ? ' — ' . $this->l('HC_SYNC_STALE') : '')];
		}

		$error = (string) $this->config['radioglobe_sync_error'];
		$rows[] = [$error === '' ? 'ok' : 'warn', $this->l('HC_LAST_ERROR'), $error === '' ? $this->l('HC_NONE') : $error];

		$next = $this->sync->next_scheduled_time();
		$rows[] = ['info', $this->l('HC_SYNC_CRON'), $cron && $next ? $this->l('HC_SYNC_NEXT', $this->user->format_date($next)) : $this->l('HC_OFF')];
		$cron_last = (int) $this->config['radioglobe_cron_last'];
		$rows[] = ['info', $this->l('HC_SYNC_CRON_LAST'), $cron_last ? $this->user->format_date($cron_last) . ' (' . $this->ago($cron_last) . ')' : $this->l('HC_NEVER')];
		$rows[] = ['info', $this->l('HC_NOGEO'), $this->l(!isset($this->config['radioglobe_nogeo']) || !empty($this->config['radioglobe_nogeo']) ? 'HC_YES' : 'HC_NO')];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 7. Citta' (GeoNames)
	 * ---------------------------------------------------------------- */

	protected function check_cities()
	{
		$rows = [];
		$info = $this->cities->info();

		$rows[] = ['info', $this->l('HC_CITIES_SOURCE'), $this->l($info['downloaded'] ? 'HC_CITIES_DOWNLOADED' : 'HC_CITIES_BUNDLED')];
		$rows[] = [$info['cities'] >= city_update::MIN_CITIES ? 'ok' : 'error', $this->l('HC_CITIES_COUNT'), number_format($info['cities'], 0, ',', '.')];

		$age = $this->cities->age_days();
		$outdated = $this->cities->is_outdated();
		$rows[] = [$outdated ? 'warn' : 'ok', $this->l('HC_CITIES_DATE'),
			$info['date'] !== '' ? $info['date'] . ' (' . $this->l('HC_DAYS', (int) $age) . ')' . ($outdated ? ' — ' . $this->l('HC_CITIES_OLD') : '') : $this->l('HC_UNKNOWN')];

		$rows[] = ['info', $this->l('HC_CITIES_AUTO'), $this->l(!empty($this->config['radioglobe_cities_auto']) ? 'HC_YES' : 'HC_NO')];

		$error = (string) $this->config['radioglobe_cities_error'];
		$rows[] = [$error === '' ? 'ok' : 'warn', $this->l('HC_LAST_ERROR'), $error === '' ? $this->l('HC_NONE') : $error];

		// prova del riconoscimento con l'elenco in uso
		$index = new city_index(__DIR__ . '/data/', $this->cities->downloaded_file());
		$tests = [['FR', ['NRJ Lyon'], true, '', 'Lyon'], ['AR', ['Radio Mitre Mendoza'], true, '', 'Mendoza'], ['US', ['Salem NH'], false, 'NH', 'Salem']];
		$ok = 0;
		$fails = [];

		foreach ($tests as $t)
		{
			$city = $index->find($t[0], $t[1], $t[2], $t[3]);

			if ($city !== null && $city['name'] === $t[4] && ($t[3] !== 'NH' || $city['lat'] > 42))
			{
				$ok++;
			}
			else
			{
				$fails[] = $t[1][0];
			}
		}

		$rows[] = [empty($fails) ? 'ok' : 'error', $this->l('HC_CITIES_TEST'),
			empty($fails) ? $this->l('HC_CITIES_TEST_OK', $ok) : $this->l('HC_CITIES_TEST_FAIL', implode(', ', $fails))];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 8. Connettivita'
	 * ---------------------------------------------------------------- */

	protected function check_network()
	{
		$rows = [];

		$t = microtime(true);
		$servers = $this->sync->discover_servers();
		$rows[] = ['info', $this->l('HC_RB_SERVERS'), implode(', ', array_slice($servers, 0, 4)) . ' (' . $this->ms($t) . ')'];

		$server = $servers[0];
		$t = microtime(true);
		$r = $this->http->get('https://' . $server . '/json/stats', ['Accept: application/json'], 8);
		$stats = ($r['status'] === 200) ? json_decode($r['body'], true) : null;
		$rows[] = [is_array($stats) ? 'ok' : 'error', 'Radio Browser (' . $server . ')',
			is_array($stats) ? $this->ms($t) . ' · ' . $this->l('HC_RB_STATIONS', number_format(isset($stats['stations']) ? (int) $stats['stations'] : 0, 0, ',', '.')) : $this->http_error($r)];

		$t = microtime(true);
		$r = $this->http->get_range(city_update::URL, 0, 0, 8);
		$rows[] = [in_array($r['status'], [200, 206], true) ? 'ok' : 'warn', 'GeoNames',
			in_array($r['status'], [200, 206], true) ? $this->ms($t) . ($r['total'] ? ' · ' . $this->size($r['total']) : '') . ($r['modified'] !== '' ? ' · ' . $this->l('HC_MODIFIED', gmdate('Y-m-d', strtotime($r['modified']))) : '') : $this->http_error($r)];

		if (!empty($this->config['radioglobe_covers']) && !empty($this->config['radioglobe_nowplaying']))
		{
			$t = microtime(true);
			$r = $this->http->get('https://itunes.apple.com/search?media=music&entity=song&limit=1&term=' . rawurlencode('Queen Bohemian Rhapsody'), [], 6);
			$rows[] = [$r['status'] === 200 ? 'ok' : 'warn', $this->l('HC_ITUNES'), $r['status'] === 200 ? $this->ms($t) : $this->http_error($r)];
		}

		if ((string) $this->config['radioglobe_texture'] === 'satellite')
		{
			$t = microtime(true);
			$r = $this->http->get('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/2/1/2', [], 6);
			$rows[] = [$r['status'] === 200 ? 'ok' : 'warn', $this->l('HC_ESRI'), $r['status'] === 200 ? $this->ms($t) : $this->http_error($r)];
		}

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * 9. Prove reali
	 * ---------------------------------------------------------------- */

	protected function check_live()
	{
		$rows = [];

		$t = microtime(true);
		$places = $this->stations->get_places_compact();
		$rows[] = [count($places) ? 'ok' : 'warn', $this->l('HC_LIVE_PLACES'), $this->l('HC_LIVE_RESULTS', number_format(count($places), 0, ',', '.'), $this->ms($t))];

		$t = microtime(true);
		$found = $this->stations->search('radio', 60);
		$rows[] = [count($found) ? 'ok' : 'warn', $this->l('HC_LIVE_SEARCH'), '"radio" → ' . $this->l('HC_LIVE_RESULTS', count($found), $this->ms($t))];

		$t = microtime(true);
		$near = $this->stations->search_near(41.9, 12.5, 10);
		$rows[] = [count($near) ? 'ok' : 'warn', $this->l('HC_LIVE_NEAR'), '41.9, 12.5 → ' . $this->l('HC_LIVE_RESULTS', count($near), $this->ms($t))];

		// titolo in onda letto dallo stream di una delle stazioni piu' ascoltate
		$sql = 'SELECT station_id, station_name, stream_url
			FROM ' . $this->tables['stations'] . "
			WHERE station_active = 1 AND is_https = 1 AND codec IN ('MP3', 'AAC', 'AAC+')
			ORDER BY clicks DESC";
		$result = $this->db->sql_query_limit($sql, 3);
		$candidates = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if (empty($candidates))
		{
			$rows[] = ['info', $this->l('HC_LIVE_ICY'), $this->l('HC_NONE')];
		}
		else
		{
			$reached = false;

			foreach ($candidates as $station)
			{
				$t = microtime(true);
				$title = $this->http->read_icy_title($station['stream_url'], 4);

				if ($title !== null)
				{
					$rows[] = ['ok', $this->l('HC_LIVE_ICY'), $station['station_name'] . ' → ' . ($title !== '' ? '"' . $title . '"' : $this->l('HC_LIVE_ICY_EMPTY')) . ' (' . $this->ms($t) . ')'];
					$reached = true;
					break;
				}
			}

			if (!$reached)
			{
				$rows[] = ['warn', $this->l('HC_LIVE_ICY'), $this->l('HC_LIVE_ICY_FAIL', count($candidates))];
			}
		}

		$fix = utf8_text::fix("Tamb\xe9m") === 'Também' && utf8_text::fix('DinÃ¡mica') === 'Dinámica';
		$rows[] = [$fix ? 'ok' : 'error', $this->l('HC_LIVE_UTF8'), $this->l($fix ? 'HC_LIVE_UTF8_OK' : 'HC_LIVE_UTF8_FAIL')];

		return $rows;
	}

	/* ------------------------------------------------------------------
	 * Utilita'
	 * ---------------------------------------------------------------- */

	protected function l($key, ...$args)
	{
		return $this->user->lang('RADIOGLOBE_' . $key, ...$args);
	}

	protected function ext_dir()
	{
		return str_replace('\\', '/', dirname(__DIR__)) . '/';
	}

	protected function composer()
	{
		$meta = json_decode((string) @file_get_contents($this->ext_dir() . 'composer.json'), true);

		return is_array($meta) ? $meta : [];
	}

	protected function count_rows($table)
	{
		$this->db->sql_return_on_error(true);
		$result = $this->db->sql_query('SELECT COUNT(*) AS n FROM ' . $table);
		$this->db->sql_return_on_error(false);

		if (!$result)
		{
			return null;
		}

		$n = (int) $this->db->sql_fetchfield('n');
		$this->db->sql_freeresult($result);

		return $n;
	}

	/** Percorso a partire dalla cartella del forum, senza mostrare tutto il disco del server. */
	protected function short_path($path)
	{
		$path = str_replace('\\', '/', (string) $path);
		$root = str_replace('\\', '/', (string) realpath($this->root_path));

		return ($root !== '' && strpos($path, $root) === 0) ? '[phpBB]' . substr($path, strlen($root)) : $path;
	}

	protected function short_list(array $list)
	{
		return implode(', ', array_slice($list, 0, 8)) . (count($list) > 8 ? ' …' : '');
	}

	protected function size($bytes)
	{
		$bytes = (float) $bytes;

		if ($bytes >= 1073741824)
		{
			return number_format($bytes / 1073741824, 1, ',', '.') . ' GB';
		}

		return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.') . ' MB' : number_format($bytes / 1024, 0, ',', '.') . ' KB';
	}

	protected function bytes($value)
	{
		$value = trim((string) $value);

		if ($value === '' || $value === '-1')
		{
			return -1;
		}

		$n = (float) $value;

		switch (strtolower(substr($value, -1)))
		{
			case 'g': $n *= 1024;
			// no break
			case 'm': $n *= 1024;
			// no break
			case 'k': $n *= 1024;
		}

		return (int) $n;
	}

	protected function ms($start)
	{
		return number_format((microtime(true) - $start) * 1000, 0, ',', '.') . ' ms';
	}

	protected function ago($time)
	{
		$s = max(0, time() - (int) $time);

		if ($s < 3600)
		{
			return $this->l('HC_MINUTES_AGO', (int) floor($s / 60));
		}

		return $s < 172800 ? $this->l('HC_HOURS_AGO', (int) floor($s / 3600)) : $this->l('HC_DAYS_AGO', (int) floor($s / 86400));
	}

	protected function http_error(array $r)
	{
		return 'HTTP ' . (int) $r['status'] . ($r['error'] !== '' ? ' · ' . $r['error'] : '');
	}
}
