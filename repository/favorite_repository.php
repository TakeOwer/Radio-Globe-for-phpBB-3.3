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
 * Preferiti degli utenti (la loro playlist di stazioni).
 */
class favorite_repository
{
	protected $db;
	protected $table;
	protected $stations_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table, $stations_table)
	{
		$this->db = $db;
		$this->table = $table;
		$this->stations_table = $stations_table;
	}

	public function get_table()
	{
		return $this->table;
	}

	/**
	 * Stazioni preferite, dall'ultima aggiunta. Anche quelle non piu'
	 * attive, segnalate come tali: l'utente deve poterle rimuovere.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_user_favorites($user_id, $limit = 500)
	{
		$sql = 'SELECT s.*, f.fav_time
			FROM ' . $this->table . ' f, ' . $this->stations_table . ' s
			WHERE f.user_id = ' . (int) $user_id . '
				AND s.station_id = f.station_id
			ORDER BY f.fav_time DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function get_user_ids($user_id)
	{
		$sql = 'SELECT station_id FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$ids = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['station_id'];
		}
		$this->db->sql_freeresult($result);

		return $ids;
	}

	public function is_favorite($user_id, $station_id)
	{
		$sql = 'SELECT fav_id FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id . '
				AND station_id = ' . (int) $station_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$found = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $found;
	}

	/**
	 * Aggiunge o toglie la stazione dai preferiti.
	 *
	 * @return bool true se ora e' tra i preferiti
	 */
	public function toggle($user_id, $station_id)
	{
		if ($this->is_favorite($user_id, $station_id))
		{
			$sql = 'DELETE FROM ' . $this->table . '
				WHERE user_id = ' . (int) $user_id . '
					AND station_id = ' . (int) $station_id;
			$this->db->sql_query($sql);

			return false;
		}

		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', [
			'user_id'		=> (int) $user_id,
			'station_id'	=> (int) $station_id,
			'fav_time'		=> time(),
		]);
		$this->db->sql_query($sql);

		return true;
	}

	public function count_for_station($station_id)
	{
		$sql = 'SELECT COUNT(fav_id) AS total FROM ' . $this->table . '
			WHERE station_id = ' . (int) $station_id;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	public function delete_users(array $user_ids)
	{
		if (empty($user_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->table . '
			WHERE ' . $this->db->sql_in_set('user_id', array_map('intval', $user_ids));
		$this->db->sql_query($sql);
	}
}
