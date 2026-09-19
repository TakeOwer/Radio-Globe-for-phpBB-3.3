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

/**
 * "In onda adesso": titolo del brano letto dai metadati ICY dello stream
 * e copertina cercata sull'iTunes Search API.
 *
 * Gli stream radio non contengono copertine: al massimo trasmettono una
 * stringa "Artista - Titolo". La copertina si ricava da quella stringa.
 * Tutto passa dalla cache del forum, cosi' cento utenti sulla stessa
 * stazione producono una sola lettura ogni 25 secondi.
 */
class nowplaying
{
	const TTL_TITLE = 25;
	const TTL_COVER = 604800;
	const TTL_COVER_MISS = 86400;
	const BACKOFF = 600;

	protected $config;
	protected $cache;
	protected $http;

	public function __construct(\phpbb\config\config $config, \phpbb\cache\driver\driver_interface $cache, http_client $http)
	{
		$this->config = $config;
		$this->cache = $cache;
		$this->http = $http;
	}

	/**
	 * @param array $station riga della tabella stazioni
	 * @return array
	 */
	public function get(array $station)
	{
		$empty = ['title' => '', 'artist' => '', 'track' => '', 'cover' => '', 'cover_big' => ''];

		if (empty($this->config['radioglobe_nowplaying']) || !$this->http->has_curl())
		{
			return $empty;
		}

		$key = '_radioglobe_np_' . (int) $station['station_id'];
		$cached = $this->cache->get($key);

		if (is_array($cached))
		{
			return $cached;
		}

		$info = $empty;

		// 1) radio su AzuraCast: la sua API "in onda adesso" e' veloce, separa gia' artista e titolo
		//    e spesso ha la copertina del brano; 2) per tutte le altre, i metadati ICY dello stream
		$song = $this->read_azuracast($station['stream_url']);

		if ($song === null)
		{
			$raw = trim((string) $this->http->read_icy_title($station['stream_url'], 5));
			list($artist, $track) = $this->split_title($raw);
			$song = ['artist' => $artist, 'track' => $track, 'cover' => ''];
		}

		$song = $this->clean_song($song, $station['station_name']);

		if ($song !== null)
		{
			$info['artist'] = utf8_substr($song['artist'], 0, 100);
			$info['track'] = utf8_substr($song['track'], 0, 150);
			$info['title'] = ($info['artist'] !== '') ? $info['artist'] . ' - ' . $info['track'] : $info['track'];

			if (!empty($this->config['radioglobe_covers']))
			{
				if ($song['cover'] !== '')
				{
					$info['cover'] = $info['cover_big'] = $song['cover'];
				}
				else if ($info['artist'] !== '' && $info['track'] !== '')
				{
					$cover = $this->find_cover($info['artist'], $info['track']);
					$info['cover'] = $cover['small'];
					$info['cover_big'] = $cover['big'];
				}
			}
		}

		$this->cache->put($key, $info, self::TTL_TITLE);

		return $info;
	}

	/**
	 * Solo quello che c'e' gia' in cache, senza leggere lo stream: per gli avvisi
	 * "sta ascoltando" basta il titolo che il player di chi ascolta ha appena chiesto.
	 *
	 * @return array|null
	 */
	public function cached($station_id)
	{
		if (empty($this->config['radioglobe_nowplaying']))
		{
			return null;
		}

		$cached = $this->cache->get('_radioglobe_np_' . (int) $station_id);

		return is_array($cached) ? $cached : null;
	}

	/**
	 * Stazioni AzuraCast (indirizzo ".../listen/<nome>/..."): titolo dall'API /api/nowplaying/<nome>.
	 * Se la radio non ha l'API lo si ricorda per un giorno, per non interrogarla a ogni brano.
	 *
	 * @return array|null ['artist', 'track', 'cover'] oppure null se non disponibile
	 */
	protected function read_azuracast($stream_url)
	{
		if (!preg_match('#^(https?://[^/?\#]+)/listen/([A-Za-z0-9_\-]+)/#i', (string) $stream_url, $m))
		{
			return null;
		}

		$api = $m[1] . '/api/nowplaying/' . $m[2];
		$miss_key = '_radioglobe_azura_' . md5(strtolower($api));

		if ($this->cache->get($miss_key))
		{
			return null;
		}

		$response = $this->http->get($api, ['Accept: application/json'], 4);
		$data = ($response['status'] === 200) ? json_decode($response['body'], true) : null;

		if (!is_array($data) || !isset($data['now_playing']['song']) || !is_array($data['now_playing']['song']))
		{
			$this->cache->put($miss_key, 1, self::TTL_COVER_MISS);
			return null;
		}

		$song = $data['now_playing']['song'];
		$artist = isset($song['artist']) ? trim((string) $song['artist']) : '';
		$track = isset($song['title']) ? trim((string) $song['title']) : '';

		// alcuni file hanno solo il campo "text" ("Artista - Titolo")
		if ($track === '' && !empty($song['text']))
		{
			list($artist, $track) = $this->split_title(trim((string) $song['text']));
		}

		// solo copertine https: in una pagina https un'immagine http verrebbe bloccata
		$art = isset($song['art']) ? trim((string) $song['art']) : '';
		if (!preg_match('#^https://#i', $art) || stripos($art, 'generic_song') !== false)
		{
			$art = '';
		}

		return ['artist' => $artist, 'track' => $track, 'cover' => $art];
	}

	/**
	 * Toglie cio' che non e' un brano: "Unknown", "Tag1", "Track 03", jingle, pubblicita', nome della
	 * stazione... In quei casi il player mostra il nome della stazione invece di un titolo fasullo.
	 *
	 * @return array|null
	 */
	protected function clean_song(array $song, $station_name)
	{
		$artist = trim(html_entity_decode((string) $song['artist'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$track = trim(html_entity_decode((string) $song['track'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$station = utf8_strtolower(trim((string) $station_name));

		if ($this->is_placeholder($artist) || utf8_strtolower($artist) === $station)
		{
			$artist = '';
		}

		if ($track === '' || $this->is_placeholder($track) || utf8_strtolower($track) === $station)
		{
			return null;
		}

		if (!$this->is_meaningful(($artist !== '' ? $artist . ' - ' : '') . $track, $station_name))
		{
			return null;
		}

		return ['artist' => $artist, 'track' => $track, 'cover' => (string) $song['cover']];
	}

	/** Valori segnaposto che i server e i programmi di messa in onda usano quando manca il tag del brano. */
	protected function is_placeholder($value)
	{
		$v = utf8_strtolower(trim($value));
		$v = trim(preg_replace('#\s+#u', ' ', $v), " \t-_.:|/");

		if ($v === '')
		{
			return true;
		}

		return (bool) preg_match(
			'#^(?:unknown(?: artist| title| track| song)?|untitled|no ?title|no ?name|n/?a|none|null|undefined|'
			. '(?:tag|track|traccia|id|jingle|sweeper|promo|spot|station ?id|liner|bumper)(?:[\s_\-]*\d+)?|'
			. '(?:jingle|sweeper|promo|spot|advert|advertisement|commercial|pubblicit[aà]|werbung|publicidad|publicit[eé])\b.*)$#u',
			$v
		);
	}

	/**
	 * Scarta i titoli vuoti o di servizio (nome della stazione, pubblicita',
	 * indirizzi web) che non corrispondono a un brano.
	 */
	protected function is_meaningful($title, $station_name)
	{
		$title = trim($title);

		if (utf8_strlen($title) < 3)
		{
			return false;
		}

		$lower = utf8_strtolower($title);

		if ($lower === utf8_strtolower(trim($station_name)))
		{
			return false;
		}

		foreach (['http://', 'https://', 'www.', 'advert', 'commercial', 'jingle'] as $junk)
		{
			if (strpos($lower, $junk) !== false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * "Artista - Titolo" -> [artista, titolo]
	 */
	protected function split_title($title)
	{
		$parts = preg_split('#\s+[-–—]\s+#u', $title, 2);

		if (count($parts) === 2)
		{
			return [trim($parts[0]), trim($parts[1])];
		}

		return ['', trim($title)];
	}

	/**
	 * @return array ['small' => url, 'big' => url]
	 */
	protected function find_cover($artist, $track)
	{
		$none = ['small' => '', 'big' => ''];

		// via le aggiunte tipiche tra parentesi, che peggiorano la ricerca
		$clean_track = trim(preg_replace('#\s*[\(\[].*?[\)\]]\s*#u', ' ', $track));
		$term = $artist . ' ' . ($clean_track !== '' ? $clean_track : $track);
		$key = '_radioglobe_cov_' . md5(utf8_strtolower($term));

		$cached = $this->cache->get($key);

		if (is_array($cached))
		{
			return $cached;
		}

		// dopo un rifiuto per troppe richieste si lascia respirare il servizio
		if ($this->cache->get('_radioglobe_itunes_backoff'))
		{
			return $none;
		}

		$url = 'https://itunes.apple.com/search?' . http_build_query([
			'term'		=> $term,
			'media'		=> 'music',
			'entity'	=> 'song',
			'limit'		=> 1,
		]);

		$response = $this->http->get($url, ['Accept: application/json'], 5);

		if (in_array($response['status'], [403, 429], true))
		{
			$this->cache->put('_radioglobe_itunes_backoff', 1, self::BACKOFF);
			return $none;
		}

		$data = json_decode($response['body'], true);
		$result = $none;

		if (!empty($data['results'][0]['artworkUrl100']))
		{
			$art = (string) $data['results'][0]['artworkUrl100'];
			$art = preg_replace('#^http://#i', 'https://', $art);

			$result = [
				'small'	=> preg_replace('#/\d+x\d+(bb)?\.(jpg|png)$#i', '/300x300bb.$2', $art),
				'big'	=> preg_replace('#/\d+x\d+(bb)?\.(jpg|png)$#i', '/640x640bb.$2', $art),
			];
		}

		$this->cache->put($key, $result, ($result['small'] !== '') ? self::TTL_COVER : self::TTL_COVER_MISS);

		return $result;
	}
}
