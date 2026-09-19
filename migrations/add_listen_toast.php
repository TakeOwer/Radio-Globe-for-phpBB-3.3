<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\migrations;

/**
 * Avviso "Utente sta ascoltando: stazione" in alto a destra su tutte le pagine.
 */
class add_listen_toast extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'radioglobe_listening');
	}

	public static function depends_on()
	{
		return [
			'\salvocortesiano\radioglobe\migrations\add_dot_color',
			'\salvocortesiano\radioglobe\migrations\add_permissions',
		];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				// Ascolti recenti: una riga quando un utente fa partire una stazione.
				// Le righe piu' vecchie di un'ora vengono eliminate da sole.
				$this->table_prefix . 'radioglobe_listening'	=> [
					'COLUMNS'		=> [
						'event_id'		=> ['UINT', null, 'auto_increment'],
						'user_id'		=> ['UINT', 0],
						'station_id'	=> ['UINT', 0],
						'event_time'	=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'event_id',
					'KEYS'			=> [
						'rg_time'		=> ['INDEX', 'event_time'],
						'rg_user'		=> ['INDEX', 'user_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'radioglobe_listening',
			],
		];
	}

	public function update_data()
	{
		return [
			// avviso attivo e durata in secondi
			['config.add', ['radioglobe_toast_enabled', 1]],
			['config.add', ['radioglobe_toast_seconds', 5]],

			// il proprio ascolto viene mostrato agli altri
			['permission.add', ['u_radioglobe_announce']],
			['permission.permission_set', ['REGISTERED', 'u_radioglobe_announce', 'group']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_toast_enabled']],
			['config.remove', ['radioglobe_toast_seconds']],
			['permission.remove', ['u_radioglobe_announce']],
		];
	}
}
