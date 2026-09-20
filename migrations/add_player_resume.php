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
 * Opzione "Riprendi l'ascolto al cambio pagina".
 */
class add_player_resume extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_player_resume']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_station_remove'];
	}

	public function update_data()
	{
		return [
			['config.add', ['radioglobe_player_resume', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_player_resume']],
		];
	}
}
