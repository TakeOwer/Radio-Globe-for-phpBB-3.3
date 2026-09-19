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
	'ACL_U_RADIOGLOBE_LISTEN'		=> 'Can listen to the radio (globe and player)',
	'ACL_U_RADIOGLOBE_FAVORITE'		=> 'Can add stations to favourites / playlist',
	'ACL_U_RADIOGLOBE_COMMENT'		=> 'Can comment on radio stations',
	'ACL_U_RADIOGLOBE_ANNOUNCE'		=> 'Their listening is shown to others (“is listening”)',
	'ACL_M_RADIOGLOBE_COMMENTS'		=> 'Can delete anyone’s station comments',
]);
