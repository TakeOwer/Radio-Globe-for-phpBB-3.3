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

class add_nogeo_stations extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_nogeo']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_globe_markers'];
	}

	public function update_data()
	{
		return [
			// stazioni senza coordinate messe sul globo nella loro regione o nel loro paese (1 = si)
			['config.add', ['radioglobe_nogeo', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_nogeo']],
		];
	}
}
