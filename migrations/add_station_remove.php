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
 * Permesso per togliere dall'elenco le stazioni morte, mute o sbagliate.
 */
class add_station_remove extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT auth_option_id FROM ' . ACL_OPTIONS_TABLE . "
			WHERE auth_option = 'm_radioglobe_stations'";
		$result = $this->db->sql_query($sql);
		$id = $this->db->sql_fetchfield('auth_option_id');
		$this->db->sql_freeresult($result);

		return (bool) $id;
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_report_module'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['m_radioglobe_stations']],
			['permission.permission_set', ['ROLE_ADMIN_FULL', 'm_radioglobe_stations']],
			['permission.permission_set', ['ROLE_MOD_FULL', 'm_radioglobe_stations']],
		];
	}

	public function revert_data()
	{
		return [
			['permission.remove', ['m_radioglobe_stations']],
		];
	}
}
