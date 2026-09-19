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

use salvocortesiano\radioglobe\service\station_sync;

class main_module
{
	public $u_action;
	public $page_title;
	public $tpl_name;

	/** Permessi gestiti dalla scheda "Gruppi autorizzati". */
	const GROUP_PERMISSIONS = ['u_radioglobe_listen', 'u_radioglobe_favorite', 'u_radioglobe_comment', 'u_radioglobe_announce'];

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template, $user, $config;

		$user->add_lang_ext('salvocortesiano/radioglobe', ['info_acp_radioglobe', 'common']);

		$template->assign_vars(array_merge(
			$this->identity_vars($phpbb_container, $config),
			['RADIOGLOBE_ACP_CSS' => generate_board_url() . '/ext/salvocortesiano/radioglobe/adm/style/radioglobe_acp.css?v=' . (int) @filemtime(__DIR__ . '/../adm/style/radioglobe_acp.css')]
		));

		switch ($mode)
		{
			case 'groups':
				$this->page_title = 'ACP_RADIOGLOBE_GROUPS';
				$this->tpl_name = 'radioglobe_groups';
				$this->handle_groups($phpbb_container, $request, $template, $user);
			break;

			case 'sync':
				$this->page_title = 'ACP_RADIOGLOBE_SYNC';
				$this->tpl_name = 'radioglobe_sync';
				$this->handle_sync($phpbb_container, $request, $template, $user, $config);
			break;

			case 'comments':
				$this->page_title = 'ACP_RADIOGLOBE_COMMENTS';
				$this->tpl_name = 'radioglobe_comments';
				$this->handle_comments($phpbb_container, $request, $template, $user);
			break;

			default:
				$this->page_title = 'ACP_RADIOGLOBE_SETTINGS';
				$this->tpl_name = 'radioglobe_settings';
				$this->handle_settings($phpbb_container, $request, $template, $user, $config);
			break;
		}
	}

	/**
	 * Distintivi in cima a ogni scheda: versione dell'estensione, phpBB,
	 * PHP e licenza, letti da composer.json e dal server.
	 */
	protected function identity_vars($phpbb_container, $config)
	{
		$meta = [];

		try
		{
			$meta = $phpbb_container->get('ext.manager')
				->create_extension_metadata_manager('salvocortesiano/radioglobe')
				->get_metadata();
		}
		catch (\Exception $e)
		{
			$meta = [];
		}

		$php_required = isset($meta['require']['php']) ? preg_replace('#[^0-9.]#', '', (string) $meta['require']['php']) : '7.4';
		$phpbb_required = '3.3.0';

		if (isset($meta['extra']['soft-require']['phpbb/phpbb'])
			&& preg_match('#>=\s*([0-9.]+)#', (string) $meta['extra']['soft-require']['phpbb/phpbb'], $m))
		{
			$phpbb_required = $m[1];
		}

		return [
			'RADIOGLOBE_VERSION'		=> isset($meta['version']) ? (string) $meta['version'] : '-',
			'RADIOGLOBE_LICENSE'		=> isset($meta['license']) ? (string) $meta['license'] : 'GPL-2.0-only',
			'RADIOGLOBE_PHPBB_VERSION'	=> (string) $config['version'],
			'RADIOGLOBE_PHP_VERSION'	=> PHP_VERSION,
			'S_RADIOGLOBE_PHP_OK'		=> version_compare(PHP_VERSION, $php_required ?: '7.4', '>='),
			'S_RADIOGLOBE_PHPBB_OK'		=> version_compare($config['version'], $phpbb_required, '>='),
		];
	}

	/* ==================================================================
	 * Impostazioni
	 * ================================================================ */

	protected function handle_settings($phpbb_container, $request, $template, $user, $config)
	{
		$config_text = $phpbb_container->get('config_text');

		add_form_key('radioglobe_settings');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('radioglobe_settings'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$server = trim($request->variable('radioglobe_api_server', ''));
			$server = preg_replace('#^https?://#i', '', rtrim($server, '/'));

			if ($server !== '' && !preg_match('#^[a-z0-9.\-]+(:\d+)?$#i', $server))
			{
				trigger_error($user->lang('RADIOGLOBE_API_SERVER_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$hours = array_map('intval', $request->variable('radioglobe_cron_hours', [0]));
			$hours = station_sync::parse_hours(implode(',', $hours));

			if (!empty($request->variable('radioglobe_cron_enabled', 0)) && empty($hours))
			{
				trigger_error($user->lang('RADIOGLOBE_CRON_HOURS_EMPTY') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$texture = $request->variable('radioglobe_texture', 'dark');

			if (!in_array($texture, ['dark', 'night', 'marble', 'satellite'], true))
			{
				$texture = 'dark';
			}

			$markers = $request->variable('radioglobe_markers', 'dots');

			if (!in_array($markers, ['dots', 'classic'], true))
			{
				$markers = 'dots';
			}

			$dot_color = strtolower(trim($request->variable('radioglobe_dot_color', '#1ed760')));

			if (preg_match('/^#?([0-9a-f]{3})$/', $dot_color, $m))
			{
				$dot_color = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
			}
			else if (preg_match('/^#?([0-9a-f]{6})$/', $dot_color, $m))
			{
				$dot_color = '#' . $m[1];
			}
			else
			{
				$dot_color = '#1ed760';
			}

			$dot_mode = $request->variable('radioglobe_dot_mode', 'shades');

			if (!in_array($dot_mode, ['shades', 'single', 'heat', 'country'], true))
			{
				$dot_mode = 'shades';
			}

			$old_filters = $this->filter_signature($config, $config_text);

			$config->set('radioglobe_api_server', $server);
			$config->set('radioglobe_https_only', $request->variable('radioglobe_https_only', 1));
			$config->set('radioglobe_exclude_hls', $request->variable('radioglobe_exclude_hls', 1));
			$config->set('radioglobe_nogeo', $request->variable('radioglobe_nogeo', 1));
			$config->set('radioglobe_min_bitrate', max(0, min(1024, $request->variable('radioglobe_min_bitrate', 0))));
			$config->set('radioglobe_max_stations', max(500, min(100000, $request->variable('radioglobe_max_stations', 30000))));
			$config->set('radioglobe_cluster_grid', max(5, min(200, $request->variable('radioglobe_cluster_grid', 25))));
			$config_text->set('radioglobe_tags', $request->variable('radioglobe_tags', '', true));

			$config->set('radioglobe_cron_enabled', $request->variable('radioglobe_cron_enabled', 1));
			$config->set('radioglobe_cron_hours', implode(',', $hours));

			$config->set('radioglobe_player_everywhere', $request->variable('radioglobe_player_everywhere', 1));
			$config->set('radioglobe_player_opacity', max(10, min(100, $request->variable('radioglobe_player_opacity', 100))));
			$config->set('radioglobe_nav_link', $request->variable('radioglobe_nav_link', 1));
			$config->set('radioglobe_nowplaying', $request->variable('radioglobe_nowplaying', 1));
			$config->set('radioglobe_covers', $request->variable('radioglobe_covers', 1));
			$config->set('radioglobe_texture', $texture);
			$config->set('radioglobe_markers', $markers);
			$config->set('radioglobe_dot_color', $dot_color);
			$config->set('radioglobe_dot_mode', $dot_mode);
			$config->set('radioglobe_autorotate', $request->variable('radioglobe_autorotate', 1));
			$config->set('radioglobe_toast_enabled', $request->variable('radioglobe_toast_enabled', 1));
			$config->set('radioglobe_toast_seconds', max(2, min(30, $request->variable('radioglobe_toast_seconds', 5))));
			$config->set('radioglobe_toast_repeat', $request->variable('radioglobe_toast_repeat', 1));
			$config->set('radioglobe_toast_repeat_minutes', max(1, min(1440, $request->variable('radioglobe_toast_repeat_minutes', 10))));

			// copertina che ruota: valore + unita' (secondi, minuti, ore), salvato in secondi (da 5 s a 24 ore)
			$spin_units = ['s' => 1, 'm' => 60, 'h' => 3600];
			$spin_unit = $request->variable('radioglobe_cover_spin_unit', 's');
			$spin_unit = isset($spin_units[$spin_unit]) ? $spin_units[$spin_unit] : 1;
			$spin_style = $request->variable('radioglobe_cover_spin_style', 'flip');
			$config->set('radioglobe_cover_spin', $request->variable('radioglobe_cover_spin', 1));
			$config->set('radioglobe_cover_spin_every', max(5, min(86400, $request->variable('radioglobe_cover_spin_value', 10) * $spin_unit)));
			$config->set('radioglobe_cover_spin_style', in_array($spin_style, ['flip', 'flat'], true) ? $spin_style : 'flip');

			$config->set('radioglobe_comments_enabled', $request->variable('radioglobe_comments_enabled', 1));
			$config->set('radioglobe_comment_maxlen', max(50, min(5000, $request->variable('radioglobe_comment_maxlen', 1000))));
			$config->set('radioglobe_comments_per_page', max(5, min(100, $request->variable('radioglobe_comments_per_page', 20))));
			$config->set('radioglobe_comment_flood', max(0, min(3600, $request->variable('radioglobe_comment_flood', 15))));

			$message = $user->lang('RADIOGLOBE_SETTINGS_SAVED');

			if ($old_filters !== $this->filter_signature($config, $config_text))
			{
				$sync_url = str_replace('mode=settings', 'mode=sync', $this->u_action);
				$message .= '<br /><br />' . $user->lang('RADIOGLOBE_FILTERS_CHANGED', '<a href="' . $sync_url . '">', '</a>');
			}

			trigger_error($message . adm_back_link($this->u_action));
		}

		$selected_hours = station_sync::parse_hours($config['radioglobe_cron_hours']);

		for ($h = 0; $h < 24; $h++)
		{
			$template->assign_block_vars('cron_hours', [
				'HOUR'			=> $h,
				'LABEL'			=> sprintf('%02d:00', $h),
				'S_CHECKED'		=> in_array($h, $selected_hours, true),
			]);
		}

		// intervallo della copertina mostrato nell'unita' piu' comoda
		$spin_every = isset($config['radioglobe_cover_spin_every']) ? max(5, (int) $config['radioglobe_cover_spin_every']) : 10;
		$spin_unit = ($spin_every % 3600 === 0) ? 'h' : (($spin_every % 60 === 0) ? 'm' : 's');
		$spin_value = $spin_every / ($spin_unit === 'h' ? 3600 : ($spin_unit === 'm' ? 60 : 1));

		$template->assign_vars([
			'U_ACTION'						=> $this->u_action,
			'RADIOGLOBE_API_SERVER'			=> $config['radioglobe_api_server'],
			'S_RADIOGLOBE_HTTPS_ONLY'		=> (bool) $config['radioglobe_https_only'],
			'S_RADIOGLOBE_EXCLUDE_HLS'		=> (bool) $config['radioglobe_exclude_hls'],
			'S_RADIOGLOBE_NOGEO'			=> !isset($config['radioglobe_nogeo']) || (bool) $config['radioglobe_nogeo'],
			'RADIOGLOBE_MIN_BITRATE'		=> (int) $config['radioglobe_min_bitrate'],
			'RADIOGLOBE_MAX_STATIONS'		=> (int) $config['radioglobe_max_stations'],
			'RADIOGLOBE_CLUSTER_GRID'		=> (int) $config['radioglobe_cluster_grid'],
			'RADIOGLOBE_TAGS'				=> $config_text->get('radioglobe_tags'),
			'S_RADIOGLOBE_CRON_ENABLED'		=> (bool) $config['radioglobe_cron_enabled'],
			'S_RADIOGLOBE_PLAYER_EVERYWHERE'=> (bool) $config['radioglobe_player_everywhere'],
			'RADIOGLOBE_PLAYER_OPACITY'		=> isset($config['radioglobe_player_opacity']) ? (int) $config['radioglobe_player_opacity'] : 100,
			'RADIOGLOBE_PLAYER_CSS'			=> generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/theme/radioglobe.css?v=' . (int) @filemtime(__DIR__ . '/../styles/all/theme/radioglobe.css'),
			'RADIOGLOBE_PREVIEW_IMAGE'		=> generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/theme/images/earth-blue-marble.jpg',
			'S_RADIOGLOBE_NAV_LINK'			=> (bool) $config['radioglobe_nav_link'],
			'S_RADIOGLOBE_NOWPLAYING'		=> (bool) $config['radioglobe_nowplaying'],
			'S_RADIOGLOBE_COVERS'			=> (bool) $config['radioglobe_covers'],
			'RADIOGLOBE_TEXTURE'			=> $config['radioglobe_texture'],
			'RADIOGLOBE_MARKERS'			=> isset($config['radioglobe_markers']) ? $config['radioglobe_markers'] : 'dots',
			'RADIOGLOBE_DOT_COLOR'			=> isset($config['radioglobe_dot_color']) ? $config['radioglobe_dot_color'] : '#1ed760',
			'RADIOGLOBE_DOT_MODE'			=> isset($config['radioglobe_dot_mode']) ? $config['radioglobe_dot_mode'] : 'shades',
			'S_RADIOGLOBE_AUTOROTATE'		=> (bool) $config['radioglobe_autorotate'],
			'S_RADIOGLOBE_TOAST_ENABLED'	=> !isset($config['radioglobe_toast_enabled']) || (bool) $config['radioglobe_toast_enabled'],
			'RADIOGLOBE_TOAST_SECONDS'		=> isset($config['radioglobe_toast_seconds']) ? (int) $config['radioglobe_toast_seconds'] : 5,
			'S_RADIOGLOBE_TOAST_REPEAT'		=> !isset($config['radioglobe_toast_repeat']) || (bool) $config['radioglobe_toast_repeat'],
			'RADIOGLOBE_TOAST_REPEAT_MINUTES'	=> isset($config['radioglobe_toast_repeat_minutes']) ? (int) $config['radioglobe_toast_repeat_minutes'] : 10,
			'S_RADIOGLOBE_COVER_SPIN'		=> !isset($config['radioglobe_cover_spin']) || (bool) $config['radioglobe_cover_spin'],
			'RADIOGLOBE_COVER_SPIN_VALUE'	=> (int) $spin_value,
			'S_RADIOGLOBE_SPIN_UNIT_S'		=> $spin_unit === 's',
			'S_RADIOGLOBE_SPIN_UNIT_M'		=> $spin_unit === 'm',
			'S_RADIOGLOBE_SPIN_UNIT_H'		=> $spin_unit === 'h',
			'S_RADIOGLOBE_SPIN_FLAT'		=> isset($config['radioglobe_cover_spin_style']) && $config['radioglobe_cover_spin_style'] === 'flat',
			'S_RADIOGLOBE_COMMENTS_ENABLED'	=> (bool) $config['radioglobe_comments_enabled'],
			'RADIOGLOBE_COMMENT_MAXLEN'		=> (int) $config['radioglobe_comment_maxlen'],
			'RADIOGLOBE_COMMENTS_PER_PAGE'	=> (int) $config['radioglobe_comments_per_page'],
			'RADIOGLOBE_COMMENT_FLOOD'		=> (int) $config['radioglobe_comment_flood'],
			'S_RADIOGLOBE_NO_CURL'			=> !function_exists('curl_init'),
			'RADIOGLOBE_BOARD_TZ'			=> $config['board_timezone'],
		]);
	}

	/**
	 * Impronta dei filtri che cambiano l'elenco delle stazioni.
	 */
	protected function filter_signature($config, $config_text)
	{
		return md5(implode('|', [
			$config['radioglobe_https_only'],
			$config['radioglobe_exclude_hls'],
			isset($config['radioglobe_nogeo']) ? $config['radioglobe_nogeo'] : 1,
			$config['radioglobe_min_bitrate'],
			$config['radioglobe_max_stations'],
			$config['radioglobe_cluster_grid'],
			$config_text->get('radioglobe_tags'),
		]));
	}

	/* ==================================================================
	 * Sincronizzazione
	 * ================================================================ */

	protected function handle_sync($phpbb_container, $request, $template, $user, $config)
	{
		/** @var station_sync $sync */
		$sync = $phpbb_container->get('salvocortesiano.radioglobe.station_sync');
		$action = $request->variable('action', '');
		$form_key = 'radioglobe_sync';

		add_form_key($form_key);

		// Passo della sincronizzazione: risponde in JSON e termina qui
		if ($action === 'sync_step' && $request->is_ajax())
		{
			$this->sync_step($sync, $request, $user, $config, $form_key);
		}

		if ($request->is_set_post('cancel'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$sync->abort($user->lang('RADIOGLOBE_SYNC_CANCELLED'));
			trigger_error($user->lang('RADIOGLOBE_SYNC_CANCELLED') . adm_back_link($this->u_action));
		}

		$state = $sync->get_state();
		$next = $sync->next_scheduled_time();
		$percent = ($state !== null) ? $sync->get_percent($state) : 0;

		$template->assign_vars([
			'U_ACTION'				=> $this->u_action,
			'U_SYNC_STEP'			=> $this->u_action . '&amp;action=sync_step',
			'S_SYNC_RUNNING'		=> $state !== null,
			'SYNC_PERCENT'			=> $percent,
			'SYNC_PROGRESS'			=> ($state !== null) ? $this->progress_text($user, $state) : '',
			'SYNC_LAST'				=> $config['radioglobe_sync_last'] ? $user->format_date((int) $config['radioglobe_sync_last']) : $user->lang('RADIOGLOBE_NEVER'),
			'SYNC_COUNT'			=> (int) $config['radioglobe_sync_count'],
			'SYNC_PLACES'			=> (int) $config['radioglobe_sync_places'],
			'SYNC_DURATION'			=> (int) $config['radioglobe_sync_duration'],
			'SYNC_ERROR'			=> ($state === null) ? $config['radioglobe_sync_error'] : '',
			'SYNC_NEXT'				=> $next ? $user->format_date($next) : $user->lang('RADIOGLOBE_CRON_OFF'),
			'SYNC_HOURS'			=> implode(', ', array_map(function ($h) {
				return sprintf('%02d:00', $h);
			}, station_sync::parse_hours($config['radioglobe_cron_hours']))),
			'S_STORE_WRITABLE'		=> $sync->is_store_writable(),
			'S_NO_CURL'				=> !function_exists('curl_init'),
		]);
	}

	/**
	 * Un passo di lavoro per ogni chiamata della barra di avanzamento:
	 * una pagina scaricata o un blocco di stazioni importato. Cosi' la
	 * percentuale si muove a ogni risposta.
	 */
	protected function sync_step($sync, $request, $user, $config, $form_key)
	{
		$json = new \phpbb\json_response();

		if (!check_form_key($form_key))
		{
			$json->send(['error' => $user->lang('FORM_INVALID')]);
		}

		if ($request->variable('op', '') === 'start')
		{
			$sync->release_stale_lock();

			if (!$sync->is_running())
			{
				if (!$sync->start('manual'))
				{
					$json->send(['error' => $user->lang('RADIOGLOBE_STORE_NOT_WRITABLE')]);
				}

				$this->log_action($user, 'LOG_RADIOGLOBE_SYNC_STARTED');
			}
		}

		$state = $sync->get_state();

		if ($state !== null)
		{
			$state = $sync->run_for(60, 1);
		}

		if ($state !== null)
		{
			$json->send([
				'done'		=> false,
				'percent'	=> $sync->get_percent($state),
				'status'	=> $this->progress_text($user, $state),
			]);
		}

		if ($config['radioglobe_sync_error'] !== '')
		{
			$json->send(['error' => $config['radioglobe_sync_error']]);
		}

		$json->send([
			'done'		=> true,
			'percent'	=> 100,
			'status'	=> $user->lang('RADIOGLOBE_SYNC_DONE', (int) $config['radioglobe_sync_count'], (int) $config['radioglobe_sync_places']),
		]);
	}

	protected function progress_text($user, array $state)
	{
		$text = $user->lang('RADIOGLOBE_PHASE_' . strtoupper($state['phase']), (int) $state['collected'], (int) $state['imported']);

		if (!empty($state['skipped']))
		{
			$text .= ' ' . $user->lang('RADIOGLOBE_SYNC_SKIPPED', (int) $state['skipped']);
		}

		return $text;
	}

	protected function log_action($user, $key)
	{
		global $phpbb_log;

		$phpbb_log->add('admin', $user->data['user_id'], $user->ip, $key, time());
	}

	/* ==================================================================
	 * Gruppi autorizzati
	 * Stesso sistema di Music Share: si scrive negli ACL di phpBB e, se
	 * il gruppo usa un ruolo, la modifica va sul ruolo per non
	 * cancellarne l'assegnazione.
	 * ================================================================ */

	protected function get_group_permissions($db, array $permissions)
	{
		$sql = 'SELECT ao.auth_option, ag.group_id, ag.auth_setting
			FROM ' . ACL_OPTIONS_TABLE . ' ao, ' . ACL_GROUPS_TABLE . ' ag
			WHERE ag.auth_option_id = ao.auth_option_id
				AND ag.forum_id = 0
				AND ' . $db->sql_in_set('ao.auth_option', $permissions);
		$result = $db->sql_query($sql);

		$out = [];

		while ($row = $db->sql_fetchrow($result))
		{
			$out[(int) $row['group_id']][$row['auth_option']] = (int) $row['auth_setting'];
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT ag.group_id, ao.auth_option, ard.auth_setting
			FROM ' . ACL_GROUPS_TABLE . ' ag, ' . ACL_ROLES_DATA_TABLE . ' ard, ' . ACL_OPTIONS_TABLE . ' ao
			WHERE ag.auth_role_id <> 0
				AND ag.forum_id = 0
				AND ard.role_id = ag.auth_role_id
				AND ao.auth_option_id = ard.auth_option_id
				AND ' . $db->sql_in_set('ao.auth_option', $permissions);
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$gid = (int) $row['group_id'];

			if (!isset($out[$gid][$row['auth_option']]))
			{
				$out[$gid][$row['auth_option']] = (int) $row['auth_setting'];
			}
		}
		$db->sql_freeresult($result);

		return $out;
	}

	protected function get_user_role_id($db, $group_id)
	{
		$sql = 'SELECT ag.auth_role_id
			FROM ' . ACL_GROUPS_TABLE . ' ag, ' . ACL_ROLES_TABLE . ' ar
			WHERE ag.group_id = ' . (int) $group_id . '
				AND ag.forum_id = 0
				AND ag.auth_role_id <> 0
				AND ar.role_id = ag.auth_role_id
				AND ar.role_type = ' . "'u_'";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row ? (int) $row['auth_role_id'] : 0;
	}

	protected function get_role_names($db, $user, array $role_ids)
	{
		if (empty($role_ids))
		{
			return '';
		}

		$sql = 'SELECT role_name FROM ' . ACL_ROLES_TABLE . '
			WHERE ' . $db->sql_in_set('role_id', array_map('intval', $role_ids));
		$result = $db->sql_query($sql);

		$names = [];

		while ($row = $db->sql_fetchrow($result))
		{
			$names[] = isset($user->lang[$row['role_name']]) ? $user->lang[$row['role_name']] : $row['role_name'];
		}
		$db->sql_freeresult($result);

		return implode(', ', $names);
	}

	protected function handle_groups($phpbb_container, $request, $template, $user)
	{
		global $db, $auth, $phpbb_root_path, $phpEx;

		if (!class_exists('auth_admin'))
		{
			include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
		}

		$auth_admin = new \auth_admin();
		$permissions = self::GROUP_PERMISSIONS;
		$fields = [
			'u_radioglobe_listen'	=> 'can_listen',
			'u_radioglobe_favorite'	=> 'can_favorite',
			'u_radioglobe_comment'	=> 'can_comment',
			'u_radioglobe_announce'	=> 'can_announce',
		];

		add_form_key('radioglobe_groups');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('radioglobe_groups'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$posted = [];

			foreach ($fields as $option => $field)
			{
				$posted[$option] = array_map('intval', $request->variable($field, [0]));
			}

			$current = $this->get_group_permissions($db, $permissions);
			$changed = 0;
			$roles_touched = [];

			$sql = 'SELECT group_id FROM ' . GROUPS_TABLE;
			$result = $db->sql_query($sql);
			$groups = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);

			foreach ($groups as $row)
			{
				$group_id = (int) $row['group_id'];
				$wanted = [];

				foreach ($permissions as $option)
				{
					$wanted[$option] = in_array($group_id, $posted[$option], true) ? ACL_YES : ACL_NO;
				}

				$differs = false;

				foreach ($wanted as $option => $value)
				{
					$before = isset($current[$group_id][$option]) ? (int) $current[$group_id][$option] : ACL_NO;

					if ($before !== (int) $value)
					{
						$differs = true;
						break;
					}
				}

				if (!$differs)
				{
					continue;
				}

				$changed++;
				$role_id = $this->get_user_role_id($db, $group_id);

				if ($role_id)
				{
					$auth_admin->acl_set_role($role_id, $wanted);
					$roles_touched[$role_id] = true;
				}
				else
				{
					$auth_admin->acl_set('group', 0, $group_id, $wanted);
				}
			}

			$auth->acl_clear_prefetch();
			$phpbb_container->get('cache.driver')->purge();

			if ($changed === 0)
			{
				$message = $user->lang('RADIOGLOBE_GROUPS_UNCHANGED');
			}
			else
			{
				$message = $user->lang('RADIOGLOBE_GROUPS_UPDATED');

				if (!empty($roles_touched))
				{
					$message .= '<br /><br />' . $user->lang('RADIOGLOBE_GROUPS_ROLES_TOUCHED', $this->get_role_names($db, $user, array_keys($roles_touched)));
				}
			}

			trigger_error($message . adm_back_link($this->u_action));
		}

		$current = $this->get_group_permissions($db, $permissions);

		$sql = 'SELECT group_id, group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
			ORDER BY group_type DESC, group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$group_id = (int) $row['group_id'];
			$settings = isset($current[$group_id]) ? $current[$group_id] : [];
			$special = ((int) $row['group_type'] === GROUP_SPECIAL);

			$template->assign_block_vars('groups', [
				'GROUP_ID'		=> $group_id,
				'GROUP_NAME'	=> $special ? $user->lang('G_' . $row['group_name']) : $row['group_name'],
				'GROUP_COLOUR'	=> $row['group_colour'] ? '#' . $row['group_colour'] : '',
				'S_SPECIAL'		=> $special,
				'S_ROLE'		=> (bool) $this->get_user_role_id($db, $group_id),
				'S_CAN_LISTEN'	=> isset($settings['u_radioglobe_listen']) && (int) $settings['u_radioglobe_listen'] === ACL_YES,
				'S_CAN_FAVORITE'=> isset($settings['u_radioglobe_favorite']) && (int) $settings['u_radioglobe_favorite'] === ACL_YES,
				'S_CAN_COMMENT'	=> isset($settings['u_radioglobe_comment']) && (int) $settings['u_radioglobe_comment'] === ACL_YES,
				'S_CAN_ANNOUNCE'=> isset($settings['u_radioglobe_announce']) && (int) $settings['u_radioglobe_announce'] === ACL_YES,
			]);
		}
		$db->sql_freeresult($result);

		$template->assign_var('U_ACTION', $this->u_action);
	}

	/* ==================================================================
	 * Moderazione commenti
	 * ================================================================ */

	protected function handle_comments($phpbb_container, $request, $template, $user)
	{
		$comments = $phpbb_container->get('salvocortesiano.radioglobe.comment_repository');
		$pagination = $phpbb_container->get('pagination');

		add_form_key('radioglobe_comments');

		if ($request->is_set_post('delete_marked'))
		{
			if (!check_form_key('radioglobe_comments'))
			{
				trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$ids = array_map('intval', $request->variable('mark', [0]));

			if (!empty($ids))
			{
				$comments->delete_many($ids);
			}

			trigger_error($user->lang('RADIOGLOBE_COMMENTS_DELETED', count($ids)) . adm_back_link($this->u_action));
		}

		$per_page = 30;
		$start = max(0, $request->variable('start', 0));
		$total = $comments->count_all();
		$start = $pagination->validate_start($start, $per_page, $total);

		foreach ($comments->get_latest($per_page, $start) as $row)
		{
			$template->assign_block_vars('comments', [
				'ID'		=> (int) $row['comment_id'],
				'USER'		=> get_username_string('full', (int) $row['user_id'], $row['username'] !== null ? $row['username'] : $user->lang('GUEST'), $row['user_colour']),
				'STATION'	=> $row['station_name'] !== null ? $row['station_name'] : '#' . (int) $row['station_id'],
				'COUNTRY'	=> (string) $row['country'],
				'TIME'		=> $user->format_date((int) $row['comment_time']),
				'TEXT'		=> nl2br($row['comment_text']),
				'IP'		=> $row['comment_ip'],
			]);
		}

		$pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $total, $per_page, $start);

		$template->assign_vars([
			'U_ACTION'			=> $this->u_action,
			'TOTAL_COMMENTS'	=> $total,
		]);
	}
}
