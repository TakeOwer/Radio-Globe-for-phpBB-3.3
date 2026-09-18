<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACL_CAT_RADIOGLOBE'			=> 'Radio Globe',
	'ACL_U_RADIOGLOBE_LISTEN'		=> 'Può ascoltare la radio (globo e player)',
	'ACL_U_RADIOGLOBE_FAVORITE'		=> 'Può aggiungere le stazioni ai preferiti / playlist',
	'ACL_U_RADIOGLOBE_COMMENT'		=> 'Può commentare le stazioni radio',
	'ACL_M_RADIOGLOBE_COMMENTS'		=> 'Può eliminare i commenti alle stazioni di tutti',
]);
