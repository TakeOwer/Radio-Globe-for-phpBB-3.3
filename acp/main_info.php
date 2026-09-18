<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\acp;

class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\salvocortesiano\radioglobe\acp\main_module',
			'title'		=> 'ACP_RADIOGLOBE_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'	=> 'ACP_RADIOGLOBE_SETTINGS',
					'auth'	=> 'ext_salvocortesiano/radioglobe && acl_a_board',
					'cat'	=> ['ACP_RADIOGLOBE_TITLE'],
				],
				'groups'	=> [
					'title'	=> 'ACP_RADIOGLOBE_GROUPS',
					'auth'	=> 'ext_salvocortesiano/radioglobe && acl_a_authgroups',
					'cat'	=> ['ACP_RADIOGLOBE_TITLE'],
				],
				'sync'		=> [
					'title'	=> 'ACP_RADIOGLOBE_SYNC',
					'auth'	=> 'ext_salvocortesiano/radioglobe && acl_a_board',
					'cat'	=> ['ACP_RADIOGLOBE_TITLE'],
				],
				'comments'	=> [
					'title'	=> 'ACP_RADIOGLOBE_COMMENTS',
					'auth'	=> 'ext_salvocortesiano/radioglobe && acl_a_board',
					'cat'	=> ['ACP_RADIOGLOBE_TITLE'],
				],
			],
		];
	}
}
