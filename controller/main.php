<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\controller;

use salvocortesiano\radioglobe\repository\station_repository;

/**
 * Pagina del globo.
 */
class main
{
	protected $config;
	protected $template;
	protected $user;
	protected $auth;
	protected $helper;
	protected $stations;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\controller\helper $helper,
		station_repository $stations
	)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->stations = $stations;
	}

	public function page()
	{
		$this->user->add_lang_ext('salvocortesiano/radioglobe', 'common');

		if (!$this->auth->acl_get('u_radioglobe_listen'))
		{
			if ((int) $this->user->data['user_id'] === ANONYMOUS)
			{
				login_box('', $this->user->lang('RADIOGLOBE_NO_PERMISSION'));
			}

			trigger_error('RADIOGLOBE_NO_PERMISSION', E_USER_WARNING);
		}

		$textures = [
			'dark'		=> 'earth-dark.jpg',
			'night'		=> 'earth-night.jpg',
			'marble'	=> 'earth-blue-marble.jpg',
			// satellite: il globo usa i tasselli Esri; l'immagine resta come riserva
			'satellite'	=> 'earth-blue-marble.jpg',
		];
		$texture = isset($textures[$this->config['radioglobe_texture']]) ? $textures[$this->config['radioglobe_texture']] : $textures['dark'];
		$use_tiles = $this->config['radioglobe_texture'] === 'satellite';
		$markers = (isset($this->config['radioglobe_markers']) && $this->config['radioglobe_markers'] === 'classic') ? 'classic' : 'dots';
		$dot_color = isset($this->config['radioglobe_dot_color']) && preg_match('/^#[0-9a-f]{6}$/i', $this->config['radioglobe_dot_color']) ? strtolower($this->config['radioglobe_dot_color']) : '#1ed760';
		$dot_mode = isset($this->config['radioglobe_dot_mode']) && in_array($this->config['radioglobe_dot_mode'], ['shades', 'single', 'heat', 'country'], true) ? $this->config['radioglobe_dot_mode'] : 'shades';
		$images = generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/theme/images/';

		$page_config = [
			'placesUrl'		=> $this->helper->route('salvocortesiano_radioglobe_places', [], false) ,
			'placeUrl'		=> $this->helper->route('salvocortesiano_radioglobe_place', ['place_key' => '__KEY__'], false),
			'searchUrl'		=> $this->helper->route('salvocortesiano_radioglobe_search', [], false),
			'dataVersion'	=> (int) $this->config['radioglobe_data_version'],
			'texture'		=> $images . $texture,
			'tiles'			=> $use_tiles,
			'markers'		=> $markers,
			'dotColor'		=> $dot_color,
			'dotMode'		=> $dot_mode,
			'sky'			=> $images . 'night-sky.png',
			'autorotate'	=> (bool) $this->config['radioglobe_autorotate'],
			'stationCount'	=> (int) $this->config['radioglobe_sync_count'],
		];

		$this->template->assign_vars([
			'S_RADIOGLOBE_PAGE'			=> true,
			'RADIOGLOBE_PAGE_CONFIG'	=> json_encode($page_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES),
			'RADIOGLOBE_STATION_COUNT'	=> (int) $this->config['radioglobe_sync_count'],
			'RADIOGLOBE_PLACE_COUNT'	=> (int) $this->config['radioglobe_sync_places'],
			'S_RADIOGLOBE_EMPTY'		=> (int) $this->config['radioglobe_sync_count'] === 0,
			'S_RADIOGLOBE_TILES'		=> $use_tiles,
			// citta' GeoNames usate per collocare le stazioni senza coordinate (CC BY 4.0: va citata la fonte)
			'S_RADIOGLOBE_NOGEO'		=> !isset($this->config['radioglobe_nogeo']) || !empty($this->config['radioglobe_nogeo']),
			'S_RADIOGLOBE_CAN_FAV'		=> $this->can_favorite(),
		]);

		$this->template->assign_block_vars('navlinks', [
			'FORUM_NAME'	=> $this->user->lang('RADIOGLOBE_NAV'),
			'U_VIEW_FORUM'	=> $this->helper->route('salvocortesiano_radioglobe_page'),
		]);

		return $this->helper->render('radioglobe_page.html', $this->user->lang('RADIOGLOBE_PAGE_TITLE'));
	}

	protected function can_favorite()
	{
		return (int) $this->user->data['user_id'] !== ANONYMOUS && $this->auth->acl_get('u_radioglobe_favorite');
	}
}
