<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\repository;

/**
 * Accesso alle tabelle delle stazioni e dei luoghi.
 */
class station_repository
{
	/** Fattore delle coordinate salvate come intero. */
	const GEO_SCALE = 1000000;

	protected $db;
	protected $stations_table;
	protected $places_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $stations_table, $places_table)
	{
		$this->db = $db;
		$this->stations_table = $stations_table;
		$this->places_table = $places_table;
	}

	public function get_stations_table()
	{
		return $this->stations_table;
	}

	public function get_places_table()
	{
		return $this->places_table;
	}

	/**
	 * Tutti i luoghi, in forma compatta per il globo.
	 *
	 * @return array
	 */
	public function get_places_compact()
	{
		$sql = 'SELECT place_key, place_title, country, countrycode, geo_lat, geo_long, station_count
			FROM ' . $this->places_table . '
			WHERE station_count > 0';
		$result = $this->db->sql_query($sql);

		$out = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[] = [
				$row['place_key'],
				round($row['geo_lat'] / self::GEO_SCALE, 4),
				round($row['geo_long'] / self::GEO_SCALE, 4),
				(int) $row['station_count'],
				$row['place_title'],
				$row['country'],
				$row['countrycode'],
			];
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * @param string $place_key
	 * @return array|false
	 */
	public function get_place($place_key)
	{
		$sql = 'SELECT *
			FROM ' . $this->places_table . "
			WHERE place_key = '" . $this->db->sql_escape($place_key) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Stazioni attive di un luogo, le piu' ascoltate per prime.
	 *
	 * @param string $place_key
	 * @param int $limit
	 * @return array
	 */
	public function get_by_place($place_key, $limit = 300)
	{
		$sql = 'SELECT *
			FROM ' . $this->stations_table . "
			WHERE place_key = '" . $this->db->sql_escape($place_key) . "'
				AND station_active = 1
			ORDER BY clicks DESC, votes DESC, station_name ASC";
		$result = $this->db->sql_query_limit($sql, (int) $limit);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * @param int $station_id
	 * @param bool $only_active
	 * @return array|false
	 */
	public function get_station($station_id, $only_active = true)
	{
		$sql = 'SELECT *
			FROM ' . $this->stations_table . '
			WHERE station_id = ' . (int) $station_id .
			($only_active ? ' AND station_active = 1' : '');
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Ricerca per nome, genere, paese o regione.
	 *
	 * @param string $term
	 * @param int $limit
	 * @return array
	 */
	public function search($term, $limit = 60)
	{
		$term = trim($term);

		if (utf8_strlen($term) < 2)
		{
			return [];
		}

		$like = $this->db->sql_like_expression($this->db->get_any_char() . $term . $this->db->get_any_char());

		$sql = 'SELECT *
			FROM ' . $this->stations_table . '
			WHERE station_active = 1
				AND (station_name ' . $like . '
					OR tags ' . $like . '
					OR country ' . $like . '
					OR state ' . $like . ')
			ORDER BY clicks DESC, votes DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Mappa uuid => [station_id, station_hash] per un gruppo di uuid.
	 *
	 * @param array $uuids
	 * @return array
	 */
	public function get_hashes(array $uuids)
	{
		if (empty($uuids))
		{
			return [];
		}

		$sql = 'SELECT station_id, station_uuid, station_hash, station_active
			FROM ' . $this->stations_table . '
			WHERE ' . $this->db->sql_in_set('station_uuid', $uuids);
		$result = $this->db->sql_query($sql);

		$out = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[$row['station_uuid']] = $row;
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	public function insert_many(array $rows)
	{
		if (!empty($rows))
		{
			$this->db->sql_multi_insert($this->stations_table, $rows);
		}
	}

	public function update_station($station_id, array $data)
	{
		$sql = 'UPDATE ' . $this->stations_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE station_id = ' . (int) $station_id;
		$this->db->sql_query($sql);
	}

	/**
	 * Aggiorna in un colpo solo la data di ultimo avvistamento delle
	 * stazioni rimaste invariate.
	 */
	public function touch_many(array $station_ids, $time)
	{
		foreach (array_chunk($station_ids, 500) as $chunk)
		{
			$sql = 'UPDATE ' . $this->stations_table . '
				SET last_seen = ' . (int) $time . ', station_active = 1
				WHERE ' . $this->db->sql_in_set('station_id', array_map('intval', $chunk));
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Le stazioni non piu' presenti nell'ultimo aggiornamento vengono
	 * disattivate; quelle senza preferiti ne' commenti, e disattivate da
	 * oltre 30 giorni, vengono eliminate.
	 *
	 * @return int stazioni disattivate
	 */
	public function retire_missing($sync_start, $favorites_table, $comments_table)
	{
		$sql = 'UPDATE ' . $this->stations_table . '
			SET station_active = 0
			WHERE last_seen < ' . (int) $sync_start . '
				AND station_active = 1';
		$this->db->sql_query($sql);
		$retired = (int) $this->db->sql_affectedrows();

		$limit = (int) $sync_start - 30 * 86400;

		$sql = 'SELECT s.station_id
			FROM ' . $this->stations_table . ' s
			LEFT JOIN ' . $favorites_table . ' f ON (f.station_id = s.station_id)
			LEFT JOIN ' . $comments_table . ' c ON (c.station_id = s.station_id)
			WHERE s.station_active = 0
				AND s.last_seen < ' . $limit . '
				AND f.station_id IS NULL
				AND c.station_id IS NULL';
		$result = $this->db->sql_query_limit($sql, 5000);
		$ids = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['station_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($ids))
		{
			$sql = 'DELETE FROM ' . $this->stations_table . '
				WHERE ' . $this->db->sql_in_set('station_id', $ids);
			$this->db->sql_query($sql);
		}

		return $retired;
	}

	/**
	 * Ricostruisce la tabella dei luoghi dalle stazioni attive.
	 *
	 * @return int numero di luoghi
	 */
	public function rebuild_places()
	{
		$sql = 'SELECT place_key, geo_lat, geo_long, country, countrycode, state
			FROM ' . $this->stations_table . '
			WHERE station_active = 1';
		$result = $this->db->sql_query($sql);

		$places = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$key = $row['place_key'];

			if ($key === '')
			{
				continue;
			}

			if (!isset($places[$key]))
			{
				$places[$key] = [
					'lat'		=> 0,
					'lng'		=> 0,
					'n'			=> 0,
					'country'	=> $row['country'],
					'cc'		=> $row['countrycode'],
					'states'	=> [],
				];
			}

			$p = &$places[$key];
			$p['lat'] += (int) $row['geo_lat'];
			$p['lng'] += (int) $row['geo_long'];
			$p['n']++;

			$state = trim((string) $row['state']);

			if ($state !== '')
			{
				$p['states'][$state] = isset($p['states'][$state]) ? $p['states'][$state] + 1 : 1;
			}

			unset($p);
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');
		$this->db->sql_query('DELETE FROM ' . $this->places_table);

		$batch = [];

		foreach ($places as $key => $p)
		{
			$title = $p['country'];

			if (!empty($p['states']))
			{
				arsort($p['states']);
				$title = (string) key($p['states']);
			}

			$batch[] = [
				'place_key'		=> $key,
				'place_title'	=> utf8_substr($title !== '' ? $title : $p['cc'], 0, 150),
				'country'		=> utf8_substr((string) $p['country'], 0, 100),
				'countrycode'	=> (string) $p['cc'],
				'geo_lat'		=> (int) round($p['lat'] / $p['n']),
				'geo_long'		=> (int) round($p['lng'] / $p['n']),
				'station_count'	=> (int) $p['n'],
			];

			if (count($batch) >= 500)
			{
				$this->db->sql_multi_insert($this->places_table, $batch);
				$batch = [];
			}
		}

		if (!empty($batch))
		{
			$this->db->sql_multi_insert($this->places_table, $batch);
		}

		$this->db->sql_transaction('commit');

		return count($places);
	}

	public function count_active()
	{
		$sql = 'SELECT COUNT(station_id) AS total
			FROM ' . $this->stations_table . '
			WHERE station_active = 1';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	 * Riga del database -> dati per il JavaScript del player.
	 *
	 * @param array $row
	 * @return array
	 */
	public static function to_public(array $row)
	{
		$tags = array_values(array_filter(array_map('trim', explode(',', (string) $row['tags']))));

		return [
			'id'		=> (int) $row['station_id'],
			'uuid'		=> $row['station_uuid'],
			'name'		=> $row['station_name'],
			'url'		=> $row['stream_url'],
			'home'		=> $row['homepage'],
			'icon'		=> $row['favicon'],
			'tags'		=> array_slice($tags, 0, 6),
			'country'	=> $row['country'],
			'cc'		=> $row['countrycode'],
			'state'		=> $row['state'],
			'codec'		=> $row['codec'],
			'bitrate'	=> (int) $row['bitrate'],
			'https'		=> (bool) $row['is_https'],
			'lat'		=> round($row['geo_lat'] / self::GEO_SCALE, 4),
			'lng'		=> round($row['geo_long'] / self::GEO_SCALE, 4),
			'place'		=> $row['place_key'],
		];
	}
}
