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

class add_globe_markers extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_markers']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_player_opacity'];
	}

	public function update_data()
	{
		return [
			// stile dei punti sul globo: 'dots' (dimensione fissa sullo schermo) o 'classic' (cilindri 3D)
			['config.add', ['radioglobe_markers', 'dots']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_markers']],
		];
	}
}
