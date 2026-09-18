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

class add_permissions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\add_config'];
	}

	public function update_data()
	{
		return [
			// Ascoltare la radio (pagina del globo e player)
			['permission.add', ['u_radioglobe_listen']],
			// Aggiungere le stazioni ascoltate ai preferiti / playlist
			['permission.add', ['u_radioglobe_favorite']],
			// Commentare le stazioni
			['permission.add', ['u_radioglobe_comment']],
			// Moderare (eliminare) i commenti di tutti
			['permission.add', ['m_radioglobe_comments']],

			// Valori iniziali: l'amministratore li cambia dalla scheda
			// "Gruppi autorizzati" o da ACP -> Permessi.
			['permission.permission_set', ['REGISTERED', 'u_radioglobe_listen', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_radioglobe_favorite', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_radioglobe_comment', 'group']],
			['permission.permission_set', ['GUESTS', 'u_radioglobe_listen', 'group']],

			['permission.permission_set', ['ROLE_ADMIN_FULL', 'm_radioglobe_comments']],
			['permission.permission_set', ['ROLE_MOD_FULL', 'm_radioglobe_comments']],
		];
	}

	public function revert_data()
	{
		return [
			['permission.remove', ['u_radioglobe_listen']],
			['permission.remove', ['u_radioglobe_favorite']],
			['permission.remove', ['u_radioglobe_comment']],
			['permission.remove', ['m_radioglobe_comments']],
		];
	}
}
