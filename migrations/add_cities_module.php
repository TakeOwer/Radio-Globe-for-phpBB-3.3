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
 * Scheda ACP "Citta' (GeoNames)": scarica l'elenco aggiornato delle citta' usato per
 * collocare le stazioni senza coordinate.
 */
class add_cities_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_cities_updated']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_cover_spin'];
	}

	public function update_data()
	{
		return [
			// ultimo download (0 = si usa l'elenco incluso nell'estensione), citta' scaricate, ultimo errore
			['config.add', ['radioglobe_cities_updated', 0]],
			['config.add', ['radioglobe_cities_count', 0]],
			['config.add', ['radioglobe_cities_error', '']],

			['module.add', [
				'acp',
				'ACP_RADIOGLOBE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\radioglobe\acp\main_module',
					'modes'				=> ['cities'],
				],
			]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_cities_updated']],
			['config.remove', ['radioglobe_cities_count']],
			['config.remove', ['radioglobe_cities_error']],

			['module.remove', [
				'acp',
				'ACP_RADIOGLOBE_TITLE',
				[
					'module_basename'	=> '\salvocortesiano\radioglobe\acp\main_module',
					'modes'				=> ['cities'],
				],
			]],
		];
	}
}
