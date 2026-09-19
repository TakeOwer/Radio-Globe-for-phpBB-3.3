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
 * Copertina del player che ruota a intervalli e avviso «sta ascoltando»
 * ripetuto mentre l'utente resta sulla stessa stazione.
 */
class add_cover_spin extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_cover_spin']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_listen_toast'];
	}

	public function update_data()
	{
		return [
			// rotazione della copertina: attiva, ogni quanti secondi, stile (flip = 3D, flat = piatta)
			['config.add', ['radioglobe_cover_spin', 1]],
			['config.add', ['radioglobe_cover_spin_every', 10]],
			['config.add', ['radioglobe_cover_spin_style', 'flip']],

			// avviso ripetuto se l'utente ascolta ancora la stessa stazione, ogni N minuti
			['config.add', ['radioglobe_toast_repeat', 1]],
			['config.add', ['radioglobe_toast_repeat_minutes', 10]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['radioglobe_cover_spin']],
			['config.remove', ['radioglobe_cover_spin_every']],
			['config.remove', ['radioglobe_cover_spin_style']],
			['config.remove', ['radioglobe_toast_repeat']],
			['config.remove', ['radioglobe_toast_repeat_minutes']],
		];
	}
}
