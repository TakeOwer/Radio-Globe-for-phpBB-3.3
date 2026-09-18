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

class add_modules extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_permissions'];
	}

	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_RADIOGLOBE_TITLE',
			]],
			['module.add', [
				'acp',
				'ACP_RADIOGLOBE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\radioglobe\acp\main_module',
					'modes'				=> ['settings', 'groups', 'sync', 'comments'],
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
					'modes'				=> ['settings', 'groups', 'sync', 'comments'],
				],
			]],
			['module.remove', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_RADIOGLOBE_TITLE',
			]],
		];
	}
}
