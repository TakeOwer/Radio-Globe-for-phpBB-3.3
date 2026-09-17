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

class add_player_opacity extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_player_opacity']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_modules'];
	}

	public function update_data()
	{
		return [
			// opacita' dello sfondo della barra del player, in percentuale
			['config.add', ['radioglobe_player_opacity', 100]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_player_opacity']],
		];
	}
}
