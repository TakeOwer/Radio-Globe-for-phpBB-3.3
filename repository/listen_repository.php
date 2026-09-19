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
 * Ascolti recenti per l'avviso "Utente sta ascoltando: stazione".
 */
class listen_repository
{
	/** Gli eventi piu' vecchi non vengono piu' mostrati. */
	const MAX_AGE = 90;
	/** Dopo un'ora le righe vengono eliminate. */
	const KEEP = 3600;
	/** La stessa stazione non viene riannunciata prima di mezz'ora. */
	const SAME_STATION = 1800;
	/** Cambi di stazione ravvicinati (avanti, avanti...) aggiornano l'ultimo evento invece di crearne altri. */
	const MERGE = 20;

	protected $db;
	protected $table;
	protected $stations_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table, $stations_table)
	{
		$this->db = $db;
		$this->table = $table;
		$this->stations_table = $stations_table;
	}

	/**
	 * Registra l'ascolto di una stazione.
	 *
	 * @return bool true se e' stato creato o aggiornato un evento
	 */
	public function add($user_id, $station_id)
	{
		$user_id = (int) $user_id;
		$station_id = (int) $station_id;
		$now = time();

		$this->db->sql_query('DELETE FROM ' . $this->table . ' WHERE event_time < ' . ($now - self::KEEP));

		$sql = 'SELECT event_id, station_id, event_time
			FROM ' . $this->table . '
			WHERE user_id = ' . $user_id . '
			ORDER BY event_id DESC';
		$result = $this->db->sql_query_limit($sql, 1);
		$last = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($last)
		{
			if ((int) $last['station_id'] === $station_id && $now - (int) $last['event_time'] < self::SAME_STATION)
			{
				return false;
			}

			if ($now - (int) $last['event_time'] < self::MERGE)
			{
				// nuovo numero di evento, cosi' chi ha gia' visto il precedente vede anche questo
				$this->db->sql_query('DELETE FROM ' . $this->table . ' WHERE event_id = ' . (int) $last['event_id']);
			}
		}

		$this->db->sql_query('INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', [
			'user_id'		=> $user_id,
			'station_id'	=> $station_id,
			'event_time'	=> $now,
		]));

		return true;
	}

	/**
	 * Ripete l'avviso di chi ascolta ancora la stessa stazione (ACP: "Ripeti se ascolta ancora").
	 * Il cambio di stazione resta affidato ad add().
	 *
	 * @param int $interval secondi minimi dall'ultimo avviso della stessa stazione
	 * @return bool true se e' stato creato un nuovo evento
	 */
	public function repeat($user_id, $station_id, $interval)
	{
		$user_id = (int) $user_id;
		$station_id = (int) $station_id;
		$now = time();

		$this->db->sql_query('DELETE FROM ' . $this->table . ' WHERE event_time < ' . ($now - self::KEEP));

		$sql = 'SELECT station_id, event_time
			FROM ' . $this->table . '
			WHERE user_id = ' . $user_id . '
			ORDER BY event_id DESC';
		$result = $this->db->sql_query_limit($sql, 1);
		$last = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// ultimo avviso di un'altra stazione, oppure troppo recente (30 s di tolleranza per il timer del browser)
		if ($last && ((int) $last['station_id'] !== $station_id || $now - (int) $last['event_time'] < max(30, (int) $interval - 30)))
		{
			return false;
		}

		$this->db->sql_query('INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', [
			'user_id'		=> $user_id,
			'station_id'	=> $station_id,
			'event_time'	=> $now,
		]));

		return true;
	}

	/**
	 * Numero dell'ultimo evento: punto di partenza per chi apre il forum.
	 */
	public function last_id()
	{
		$result = $this->db->sql_query('SELECT MAX(event_id) AS last_id FROM ' . $this->table);
		$last = (int) $this->db->sql_fetchfield('last_id');
		$this->db->sql_freeresult($result);

		return $last;
	}

	/**
	 * Eventi successivi a $since_id, dal piu' vecchio, esclusi quelli di $exclude_user.
	 *
	 * @return array righe con utente (nome e colore) e stazione
	 */
	public function recent($since_id, $exclude_user, $limit = 5)
	{
		$sql = 'SELECT l.event_id, l.user_id, l.event_time, u.username, u.user_colour, s.*
			FROM ' . $this->table . ' l, ' . USERS_TABLE . ' u, ' . $this->stations_table . ' s
			WHERE l.event_id > ' . (int) $since_id . '
				AND l.event_time > ' . (time() - self::MAX_AGE) . '
				AND l.user_id <> ' . (int) $exclude_user . '
				AND u.user_id = l.user_id
				AND s.station_id = l.station_id
				AND s.station_active = 1
			ORDER BY l.event_id ASC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	public function delete_users(array $user_ids)
	{
		$user_ids = array_map('intval', $user_ids);

		if ($user_ids)
		{
			$this->db->sql_query('DELETE FROM ' . $this->table . ' WHERE ' . $this->db->sql_in_set('user_id', $user_ids));
		}
	}
}
