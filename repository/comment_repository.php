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
 * Commenti alle stazioni radio.
 */
class comment_repository
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
	 * Commenti di una stazione con i dati dell'autore, dal piu' recente.
	 */
	public function get_for_station($station_id, $limit, $start = 0)
	{
		$sql = 'SELECT c.*, u.username, u.user_colour, u.user_avatar, u.user_avatar_type,
				u.user_avatar_width, u.user_avatar_height
			FROM ' . $this->table . ' c
			LEFT JOIN ' . USERS_TABLE . ' u ON (u.user_id = c.user_id)
			WHERE c.station_id = ' . (int) $station_id . '
			ORDER BY c.comment_time DESC, c.comment_id DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function count_for_station($station_id)
	{
		$sql = 'SELECT COUNT(comment_id) AS total FROM ' . $this->table . '
			WHERE station_id = ' . (int) $station_id;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	public function get_comment($comment_id)
	{
		$sql = 'SELECT * FROM ' . $this->table . '
			WHERE comment_id = ' . (int) $comment_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Momento dell'ultimo commento dell'utente, per il controllo flood.
	 */
	public function last_time_for_user($user_id)
	{
		$sql = 'SELECT MAX(comment_time) AS last_time FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$time = (int) $this->db->sql_fetchfield('last_time');
		$this->db->sql_freeresult($result);

		return $time;
	}

	public function add($station_id, $user_id, $text, $ip)
	{
		$sql = 'INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', [
			'station_id'	=> (int) $station_id,
			'user_id'		=> (int) $user_id,
			'comment_text'	=> $text,
			'comment_time'	=> time(),
			'comment_ip'	=> (string) $ip,
		]);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	public function delete($comment_id)
	{
		$sql = 'DELETE FROM ' . $this->table . '
			WHERE comment_id = ' . (int) $comment_id;
		$this->db->sql_query($sql);
	}

	public function delete_many(array $comment_ids)
	{
		if (empty($comment_ids))
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->table . '
			WHERE ' . $this->db->sql_in_set('comment_id', array_map('intval', $comment_ids));
		$this->db->sql_query($sql);
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

	/**
	 * Ultimi commenti di tutto il forum, per la moderazione in ACP.
	 */
	public function get_latest($limit, $start = 0)
	{
		$sql = 'SELECT c.*, u.username, u.user_colour, s.station_name, s.country
			FROM ' . $this->table . ' c
			LEFT JOIN ' . USERS_TABLE . ' u ON (u.user_id = c.user_id)
			LEFT JOIN ' . $this->stations_table . ' s ON (s.station_id = c.station_id)
			ORDER BY c.comment_time DESC, c.comment_id DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function count_all()
	{
		$sql = 'SELECT COUNT(comment_id) AS total FROM ' . $this->table;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}
}
