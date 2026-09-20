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
 * Avviso quando l'elenco delle citta' e' vecchio e aggiornamento automatico dal cron.
 */
class add_cities_auto extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_cities_max_months']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_cities_module'];
	}

	public function update_data()
	{
		return [
			// avviso attivo, dopo quanti mesi l'elenco e' "vecchio", aggiornamento automatico (spento)
			['config.add', ['radioglobe_cities_notice', 1]],
			['config.add', ['radioglobe_cities_max_months', 6]],
			['config.add', ['radioglobe_cities_auto', 0]],
			// ultimo tentativo automatico e blocco fra cron e ACP
			['config.add', ['radioglobe_cities_auto_last', 0, true]],
			['config.add', ['radioglobe_cities_lock', 0, true]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_cities_notice']],
			['config.remove', ['radioglobe_cities_max_months']],
			['config.remove', ['radioglobe_cities_auto']],
			['config.remove', ['radioglobe_cities_auto_last']],
			['config.remove', ['radioglobe_cities_lock']],
		];
	}
}
