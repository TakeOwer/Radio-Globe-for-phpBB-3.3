<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Citta': GeoNames (https://www.geonames.org), licenza CC BY 4.0.
 *
 */

namespace salvocortesiano\radioglobe\service;

/**
 * Aggiornamento dell'elenco delle citta' da GeoNames (ACP > Citta').
 *
 * Come l'aggiornamento delle stazioni, il lavoro e' diviso in passi brevi e lo stato resta su file:
 *  1. download  cities15000.zip (circa 3,4 MB) a pezzi di 512 KB;
 *  2. extract   si estrae cities15000.txt (anche senza l'estensione zip di PHP);
 *  3. parse     le righe diventano chiavi di ricerca, qualche migliaio per passo;
 *  4. build     ordinamento, una riga per nome e paese, controllo e sostituzione del file in uso.
 * Il file nuovo (store/radioglobe/cities.tsv) sostituisce quello incluso nell'estensione solo se
 * il controllo finale va a buon fine; in caso di errore resta in uso quello di prima.
 */
class city_update
{
	const URL = 'https://download.geonames.org/export/dump/cities15000.zip';
	const ENTRY = 'cities15000.txt';
	const CHUNK = 524288;
	const PARSE_LINES = 6000;
	/** Sotto questo numero di citta' il file scaricato e' considerato rovinato. */
	const MIN_CITIES = 20000;
	/** I nomi alternativi ("Milano", "München") si tengono per le citta' grandi. */
	const ALT_MIN_POP = 100000;
	const MAX_RETRIES = 3;

	protected $config;
	protected $http;
	protected $root_path;
	protected $words = null;

	public function __construct(\phpbb\config\config $config, http_client $http, $root_path)
	{
		$this->config = $config;
		$this->http = $http;
		$this->root_path = $root_path;
	}

	/* ------------------------------------------------------------------
	 * File
	 * ---------------------------------------------------------------- */

	public function store_dir()
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

	/** Elenco scaricato, usato al posto di quello incluso nell'estensione. */
	public function downloaded_file()
	{
		return $this->store_dir() . 'cities.tsv';
	}

	public static function bundled_file()
	{
		return __DIR__ . '/data/cities.tsv';
	}

	protected function state_file()	{ return $this->store_dir() . 'cities_state.json'; }
	protected function zip_file()	{ return $this->store_dir() . 'cities15000.zip.part'; }
	protected function txt_file()	{ return $this->store_dir() . 'cities15000.txt'; }
	protected function rows_file()	{ return $this->store_dir() . 'cities_rows.tmp'; }

	public function is_store_writable()
	{
		$dir = $this->store_dir();

		return is_dir($dir) && is_writable($dir);
	}

	/* ------------------------------------------------------------------
	 * Stato
	 * ---------------------------------------------------------------- */

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

	/**
	 * @param string $source 'manual' (ACP) o 'cron' (aggiornamento automatico)
	 */
	public function start($source = 'manual')
	{
		if (!$this->is_store_writable())
		{
			return false;
		}

		$this->cleanup();
		$this->config->set('radioglobe_cities_error', '');
		$this->save_state([
			'phase'		=> 'download',
			'pos'		=> 0,
			'total'		=> 0,
			'modified'	=> '',
			'retries'	=> 0,
			'txt_pos'	=> 0,
			'txt_size'	=> 0,
			'lines'		=> 0,
			'started'	=> time(),
			'source'	=> $source === 'cron' ? 'cron' : 'manual',
		]);

		return true;
	}

	public function abort($error = '')
	{
		$this->cleanup();
		@unlink($this->state_file());
		$this->config->set('radioglobe_cities_error', utf8_text::fix(substr((string) $error, 0, 250)));
	}

	protected function cleanup()
	{
		foreach ([$this->zip_file(), $this->txt_file(), $this->rows_file(), $this->downloaded_file() . '.new'] as $file)
		{
			@unlink($file);
		}
	}

	/** Torna all'elenco incluso nell'estensione. */
	public function use_bundled()
	{
		@unlink($this->downloaded_file());
		$this->config->set('radioglobe_cities_updated', 0);
		$this->config->set('radioglobe_cities_count', 0);
	}

	public function get_percent($state)
	{
		if (!is_array($state))
		{
			return 100;
		}

		switch ($state['phase'])
		{
			case 'download':
				return $state['total'] > 0 ? (int) floor(60 * $state['pos'] / $state['total']) : 0;
			case 'extract':
				return 62;
			case 'parse':
				return 65 + ($state['txt_size'] > 0 ? (int) floor(30 * $state['txt_pos'] / $state['txt_size']) : 0);
			default:
				return 97;
		}
	}

	/**
	 * Esegue passi finche' c'e' tempo.
	 *
	 * @return array|null stato, null quando e' finito (bene o male: vedi radioglobe_cities_error)
	 */
	public function run_for($seconds, $max_steps = 0)
	{
		$state = $this->get_state();

		// un altro processo (il cron o un'altra scheda dell'ACP) sta gia' lavorando: si aspetta il turno
		if ($state === null || !$this->lock())
		{
			return $state;
		}

		$end = microtime(true) + $seconds;
		$steps = 0;

		while ($state !== null && microtime(true) < $end && (!$max_steps || $steps < $max_steps))
		{
			$state = $this->step($state);
			$steps++;
		}

		$this->config->set('radioglobe_cities_lock', 0, false);

		return $state;
	}

	/** Un passo dura al massimo un minuto: un blocco piu' vecchio e' di un processo interrotto. */
	protected function lock()
	{
		$old = (int) $this->config['radioglobe_cities_lock'];

		if ($old > time() - 90)
		{
			return false;
		}

		return $this->config->set_atomic('radioglobe_cities_lock', $old, time(), false);
	}

	public function is_locked()
	{
		return (int) $this->config['radioglobe_cities_lock'] > time() - 90;
	}

	protected function step(array $state)
	{
		@set_time_limit(120);

		switch ($state['phase'])
		{
			case 'download':
				$state = $this->step_download($state);
			break;

			case 'extract':
				$state = $this->step_extract($state);
			break;

			case 'parse':
				$state = $this->step_parse($state);
			break;

			case 'build':
				$this->step_build($state);
				return null;

			default:
				$this->abort('Stato sconosciuto');
				return null;
		}

		if ($state !== null)
		{
			$this->save_state($state);
		}

		return $state;
	}

	/* ------------------------------------------------------------------
	 * 1. Download a pezzi
	 * ---------------------------------------------------------------- */

	protected function step_download(array $state)
	{
		$from = (int) $state['pos'];
		$r = $this->http->get_range(self::URL, $from, $from + self::CHUNK - 1, 60);

		if ($r['status'] === 206 && $r['body'] !== '' && $r['total'] > 0)
		{
			// il file e' cambiato sul server a meta' download: si ricomincia
			if ($state['total'] && ($r['total'] !== (int) $state['total'] || $r['modified'] !== $state['modified']))
			{
				@unlink($this->zip_file());
				$state['pos'] = 0;
				$state['total'] = 0;
				return $state;
			}

			if (@file_put_contents($this->zip_file(), $r['body'], FILE_APPEND | LOCK_EX) === false)
			{
				$this->abort('Impossibile scrivere in store/radioglobe/');
				return null;
			}

			$state['pos'] = $from + strlen($r['body']);
			$state['total'] = $r['total'];
			$state['modified'] = $r['modified'];
			$state['retries'] = 0;

			if ($state['pos'] >= $state['total'])
			{
				$state['phase'] = 'extract';
			}

			return $state;
		}

		if ($r['status'] === 200 && $r['body'] !== '')
		{
			// server senza Range: e' arrivato tutto il file in una volta
			@unlink($this->zip_file());
			file_put_contents($this->zip_file(), $r['body']);
			$state['pos'] = $state['total'] = strlen($r['body']);
			$state['modified'] = $r['modified'];
			$state['phase'] = 'extract';

			return $state;
		}

		if (++$state['retries'] > self::MAX_RETRIES)
		{
			$this->abort('GeoNames non raggiungibile: HTTP ' . $r['status'] . ' ' . $r['error']);
			return null;
		}

		return $state;
	}

	/* ------------------------------------------------------------------
	 * 2. Estrazione
	 * ---------------------------------------------------------------- */

	protected function step_extract(array $state)
	{
		$data = self::read_zip_entry($this->zip_file(), self::ENTRY, $error);

		if ($data === null)
		{
			$this->abort($error);
			return null;
		}

		if (@file_put_contents($this->txt_file(), $data) === false)
		{
			$this->abort('Impossibile scrivere in store/radioglobe/');
			return null;
		}

		@unlink($this->zip_file());
		@unlink($this->rows_file());

		$state['phase'] = 'parse';
		$state['txt_pos'] = 0;
		$state['txt_size'] = strlen($data);

		return $state;
	}

	/**
	 * Legge un file da un archivio ZIP: con ZipArchive se c'e', altrimenti leggendo l'archivio
	 * direttamente (indice centrale + gzinflate), con controllo del CRC.
	 *
	 * @return string|null contenuto, null in caso di errore (motivo in $error)
	 */
	public static function read_zip_entry($zip_file, $entry, &$error = '')
	{
		$error = '';

		if (class_exists('ZipArchive'))
		{
			$zip = new \ZipArchive();

			if ($zip->open($zip_file) === true)
			{
				$data = $zip->getFromName($entry);
				$zip->close();

				if ($data !== false)
				{
					return $data;
				}
			}
		}

		$zip = @file_get_contents($zip_file);

		if ($zip === false || strlen($zip) < 22)
		{
			$error = 'Archivio ZIP mancante o vuoto';
			return null;
		}

		// fine dell'indice centrale: in coda all'archivio, prima di un eventuale commento
		$eocd = strrpos($zip, "PK\x05\x06", -22);

		if ($eocd === false)
		{
			$error = 'Archivio ZIP non valido';
			return null;
		}

		$head = unpack('vdisk/vcd_disk/ventries_disk/ventries/Vcd_size/Vcd_offset', substr($zip, $eocd + 4, 16));
		$pos = $head['cd_offset'];

		for ($i = 0; $i < $head['entries']; $i++)
		{
			if (substr($zip, $pos, 4) !== "PK\x01\x02")
			{
				break;
			}

			$c = unpack('vmethod', substr($zip, $pos + 10, 2))
				+ unpack('Vcrc/Vcsize/Vusize/vname_len/vextra_len/vcomment_len', substr($zip, $pos + 16, 18))
				+ unpack('Voffset', substr($zip, $pos + 42, 4));
			$name = substr($zip, $pos + 46, $c['name_len']);
			$pos += 46 + $c['name_len'] + $c['extra_len'] + $c['comment_len'];

			if ($name !== $entry)
			{
				continue;
			}

			$local = unpack('vname_len/vextra_len', substr($zip, $c['offset'] + 26, 4));
			$raw = substr($zip, $c['offset'] + 30 + $local['name_len'] + $local['extra_len'], $c['csize']);

			if ($c['method'] === 8)
			{
				$data = function_exists('gzinflate') ? @gzinflate($raw) : false;
			}
			else if ($c['method'] === 0)
			{
				$data = $raw;
			}
			else
			{
				$error = 'Compressione ZIP non supportata (' . $c['method'] . ')';
				return null;
			}

			if ($data === false || strlen($data) !== $c['usize'] || (crc32($data) & 0xFFFFFFFF) !== ($c['crc'] & 0xFFFFFFFF))
			{
				$error = 'Archivio ZIP rovinato (CRC)';
				return null;
			}

			return $data;
		}

		$error = self::ENTRY . ' non trovato nell\'archivio';
		return null;
	}

	/* ------------------------------------------------------------------
	 * 3. Righe di GeoNames -> chiavi di ricerca
	 * ---------------------------------------------------------------- */

	protected function step_parse(array $state)
	{
		$in = @fopen($this->txt_file(), 'rb');

		if (!$in)
		{
			$this->abort('File delle citta\' scomparso durante l\'aggiornamento');
			return null;
		}

		fseek($in, (int) $state['txt_pos']);
		$out = '';
		$n = 0;

		while ($n < self::PARSE_LINES && ($line = fgets($in)) !== false)
		{
			$n++;
			$out .= $this->rows_for_line($line);
		}

		$state['txt_pos'] = ftell($in);
		$state['lines'] += $n;
		$eof = feof($in) || $n < self::PARSE_LINES;
		fclose($in);

		if ($out !== '' && @file_put_contents($this->rows_file(), $out, FILE_APPEND | LOCK_EX) === false)
		{
			$this->abort('Impossibile scrivere in store/radioglobe/');
			return null;
		}

		if ($eof)
		{
			@unlink($this->txt_file());
			$state['phase'] = 'build';
		}

		return $state;
	}

	/**
	 * Una riga di cities15000.txt (formato GeoNames, 19 colonne separate da tab) diventa una riga per
	 * ogni nome utile: principale, senza accenti e, per le citta' grandi, i nomi alternativi in alfabeto
	 * latino. Davanti ci sono i campi per l'ordinamento: paese, chiave, principale/alternativo, abitanti.
	 */
	public function rows_for_line($line)
	{
		$f = explode("\t", rtrim($line, "\r\n"));

		if (count($f) < 15 || !preg_match('#^[A-Z]{2}$#', $f[8]) || !is_numeric($f[4]) || !is_numeric($f[5]))
		{
			return '';
		}

		$name = str_replace(["\t", "\r", "\n"], ' ', $f[1]);
		$cc = $f[8];
		$pop = max(0, (int) $f[14]);

		$names = [];
		$names[self::key($name)] = [0, $name];

		if (!isset($names[self::key($f[2])]))
		{
			$names[self::key($f[2])] = [0, $name];
		}

		if ($pop >= self::ALT_MIN_POP)
		{
			foreach (explode(',', $f[3]) as $alt)
			{
				if ($alt === '' || !preg_match('#^[A-Za-z\x{C0}-\x{24F}\' .\-]+$#u', $alt) || self::is_upper($alt))
				{
					continue;
				}

				$k = self::key($alt);

				// a parita' di chiave, la grafia con gli accenti ("München" invece di "Munchen")
				if (!isset($names[$k]) || ($names[$k][0] === 1 && self::non_ascii($alt) > self::non_ascii($names[$k][1])))
				{
					$names[$k] = [1, $alt];
				}
			}
		}

		$out = '';

		foreach ($names as $k => $item)
		{
			$k = (string) $k;

			if ($k === '' || self::length($k) < 4 || $this->words()->is_country_word($k))
			{
				continue;
			}

			$out .= $cc . "\t" . $k . "\t" . $item[0] . "\t" . sprintf('%010d', 9999999999 - $pop) . "\t"
				. ($cc === 'US' ? $f[10] : '') . "\t" . max(1, intdiv($pop, 1000)) . "\t"
				. sprintf('%.3f', (float) $f[4]) . "\t" . sprintf('%.3f', (float) $f[5]) . "\t" . $item[1] . "\n";
		}

		return $out;
	}

	/* ------------------------------------------------------------------
	 * 4. Ordinamento, controllo e sostituzione
	 * ---------------------------------------------------------------- */

	protected function step_build(array $state)
	{
		$rows = @file($this->rows_file(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		@unlink($this->rows_file());

		if (!$rows)
		{
			$this->abort('Nessuna citta\' letta dal file di GeoNames');
			return;
		}

		// paese, chiave, nome principale prima, poi la citta' piu' grande
		sort($rows, SORT_STRING);

		$date = $state['modified'] !== '' && strtotime($state['modified']) ? gmdate('Y-m-d', strtotime($state['modified'])) : gmdate('Y-m-d');
		$out = "# Radio Globe - citta' per collocare le stazioni senza coordinate.\n"
			. "# Dati: GeoNames (https://www.geonames.org), cities15000, licenza CC BY 4.0\n"
			. "# (https://creativecommons.org/licenses/by/4.0/). Scaricato dall'ACP: paese, chiave del nome, 0 = nome\n"
			. "# principale / 1 = nome alternativo, stato USA, migliaia di abitanti, latitudine, longitudine, nome.\n"
			. "# Data: " . $date . "\n";

		$group = '';
		$states = [];
		$cities = [];
		$lines = 0;

		foreach ($rows as $row)
		{
			$f = explode("\t", $row, 9);

			if (count($f) !== 9)
			{
				continue;
			}

			$id = $f[0] . "\t" . $f[1];

			if ($id !== $group)
			{
				$group = $id;
				$states = [];
			}
			else if ($f[0] !== 'US' || isset($states[$f[4]]))
			{
				// fuori dagli USA basta la citta' migliore per ogni nome; negli USA una per stato
				continue;
			}

			$states[$f[4]] = true;
			$cities[$f[0] . $f[6] . $f[7]] = true;
			$out .= $f[0] . "\t" . $f[1] . "\t" . $f[2] . "\t" . $f[4] . "\t" . $f[5] . "\t" . $f[6] . "\t" . $f[7] . "\t" . $f[8] . "\n";
			$lines++;
		}

		if (count($cities) < self::MIN_CITIES)
		{
			$this->abort('Il file di GeoNames contiene solo ' . count($cities) . ' citta\': non usato');
			return;
		}

		$tmp = $this->downloaded_file() . '.new';

		if (@file_put_contents($tmp, $out) === false)
		{
			$this->abort('Impossibile scrivere in store/radioglobe/');
			return;
		}

		@unlink($this->downloaded_file());

		if (!@rename($tmp, $this->downloaded_file()))
		{
			@unlink($tmp);
			$this->abort('Impossibile sostituire store/radioglobe/cities.tsv');
			return;
		}

		@unlink($this->state_file());
		$this->config->set('radioglobe_cities_updated', time());
		$this->config->set('radioglobe_cities_count', count($cities));
		$this->config->set('radioglobe_cities_error', '');
	}

	/* ------------------------------------------------------------------
	 * Informazioni per l'ACP
	 * ---------------------------------------------------------------- */

	/**
	 * Data dei dati GeoNames dell'elenco in uso (riga "# Data:" in testa al file): si leggono solo
	 * le prime righe, cosi' il controllo costa poco anche quando lo fa il cron.
	 *
	 * @return string 'Y-m-d', '' se sconosciuta
	 */
	public function data_date()
	{
		$file = city_index::has_data($this->downloaded_file()) ? $this->downloaded_file() : self::bundled_file();
		$handle = @fopen($file, 'rb');
		$date = '';

		if ($handle)
		{
			for ($i = 0; $i < 10 && ($line = fgets($handle)) !== false && $line[0] === '#'; $i++)
			{
				if (preg_match('#^\# Data: (\d{4}-\d{2}-\d{2})#', $line, $m))
				{
					$date = $m[1];
					break;
				}
			}
			fclose($handle);
		}

		return $date;
	}

	/** Giorni trascorsi dalla data dei dati in uso, null se la data non e' nota. */
	public function age_days()
	{
		$date = $this->data_date();
		$time = ($date !== '') ? strtotime($date . ' 00:00:00 UTC') : false;

		return $time ? max(0, (int) floor((time() - $time) / 86400)) : null;
	}

	/** L'elenco in uso e' piu' vecchio della soglia scelta in ACP (in mesi). */
	public function is_outdated()
	{
		$months = max(1, (int) $this->config['radioglobe_cities_max_months']);
		$age = $this->age_days();

		return $age !== null && $age > $months * 30;
	}

	/**
	 * Elenco in uso: scaricato o incluso, data dei dati GeoNames, numero di citta'.
	 *
	 * @return array ['downloaded' => bool, 'date' => 'Y-m-d' o '', 'cities' => int]
	 */
	public function info()
	{
		$downloaded = city_index::has_data($this->downloaded_file());
		$file = $downloaded ? $this->downloaded_file() : self::bundled_file();
		$date = '';
		$cities = [];
		$handle = @fopen($file, 'rb');

		if ($handle)
		{
			while (($line = fgets($handle)) !== false)
			{
				if ($line[0] === '#')
				{
					if (preg_match('#^\# Data: (\d{4}-\d{2}-\d{2})#', $line, $m))
					{
						$date = $m[1];
					}
					continue;
				}

				$f = explode("\t", $line, 8);

				if (count($f) === 8)
				{
					$cities[$f[0] . $f[5] . $f[6]] = true;
				}
			}
			fclose($handle);
		}

		return ['downloaded' => $downloaded, 'date' => $date, 'cities' => count($cities)];
	}

	/* ------------------------------------------------------------------
	 * Chiavi (stessa normalizzazione della ricerca)
	 * ---------------------------------------------------------------- */

	protected function words()
	{
		if ($this->words === null)
		{
			$this->words = new city_index(__DIR__ . '/data/');
		}

		return $this->words;
	}

	public static function key($text)
	{
		return (string) preg_replace('#[^\p{L}\p{N}]+#u', '', city_index::fold(trim((string) $text)));
	}

	protected static function length($text)
	{
		return function_exists('utf8_strlen') ? utf8_strlen($text) : mb_strlen($text, 'UTF-8');
	}

	protected static function is_upper($text)
	{
		$upper = function_exists('utf8_strtoupper') ? utf8_strtoupper($text) : mb_strtoupper($text, 'UTF-8');
		$lower = function_exists('utf8_strtolower') ? utf8_strtolower($text) : mb_strtolower($text, 'UTF-8');

		return $text === $upper && $text !== $lower;
	}

	protected static function non_ascii($text)
	{
		return preg_match_all('#[^\x00-\x7F]#u', $text);
	}
}
