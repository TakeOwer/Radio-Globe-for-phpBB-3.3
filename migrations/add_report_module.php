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
 * Scheda ACP "Rapporto di verifica": controllo completo dell'estensione.
 */
class add_report_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_basename = '" . $this->db->sql_escape('\salvocortesiano\radioglobe\acp\main_module') . "'
				AND module_mode = 'report'";
		$result = $this->db->sql_query($sql);
		$id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return (bool) $id;
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_cities_auto'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_RADIOGLOBE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\radioglobe\acp\main_module',
					'modes'				=> ['report'],
				],
			]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', [
				'acp',
				'ACP_RADIOGLOBE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\radioglobe\acp\main_module',
					'modes'				=> ['report'],
				],
			]],
		];
	}
}
