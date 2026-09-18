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

class add_dot_color extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_dot_color']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_nogeo_stations'];
	}

	public function update_data()
	{
		return [
			// colore dei puntini sul globo e modo di colorarli (shades, single, heat, country)
			['config.add', ['radioglobe_dot_color', '#1ed760']],
			['config.add', ['radioglobe_dot_mode', 'shades']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_dot_color']],
			['config.remove', ['radioglobe_dot_mode']],
		];
	}
}
