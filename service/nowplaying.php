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

		$raw = (string) $this->http->read_icy_title($station['stream_url'], 5);
		$info = $empty;

		if ($this->is_meaningful($raw, $station['station_name']))
		{
			$info['title'] = utf8_substr($raw, 0, 200);
			list($info['artist'], $info['track']) = $this->split_title($info['title']);

			if (!empty($this->config['radioglobe_covers']) && $info['artist'] !== '' && $info['track'] !== '')
			{
				$cover = $this->find_cover($info['artist'], $info['track']);
				$info['cover'] = $cover['small'];
				$info['cover_big'] = $cover['big'];
			}
		}

		$this->cache->put($key, $info, self::TTL_TITLE);

		return $info;
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
