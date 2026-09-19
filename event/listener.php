<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use salvocortesiano\radioglobe\repository\favorite_repository;
use salvocortesiano\radioglobe\repository\comment_repository;
use salvocortesiano\radioglobe\repository\listen_repository;

/**
 * Lingua, permessi, voce di menu e player in fondo a ogni pagina.
 */
class listener implements EventSubscriberInterface
{
	protected $template;
	protected $user;
	protected $auth;
	protected $helper;
	protected $config;
	protected $favorites;
	protected $comments;
	protected $listens;
	protected $root_path;

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'			=> 'load_language',
			'core.permissions'			=> 'add_permissions',
			'core.page_header_after'	=> 'add_page_vars',
			'core.delete_user_after'	=> 'users_deleted',
		];
	}

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\controller\helper $helper,
		\phpbb\config\config $config,
		favorite_repository $favorites,
		comment_repository $comments,
		listen_repository $listens,
		$root_path
	)
	{
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->config = $config;
		$this->favorites = $favorites;
		$this->comments = $comments;
		$this->listens = $listens;
		$this->root_path = $root_path;
	}

	public function load_language($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name'	=> 'salvocortesiano/radioglobe',
			'lang_set'	=> 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Rende i permessi visibili e assegnabili in ACP -> Permessi.
	 */
	public function add_permissions($event)
	{
		$categories = $event['categories'];
		$categories['radioglobe'] = 'ACL_CAT_RADIOGLOBE';
		$event['categories'] = $categories;

		$permissions = $event['permissions'];
		$permissions['u_radioglobe_listen'] = ['lang' => 'ACL_U_RADIOGLOBE_LISTEN', 'cat' => 'radioglobe'];
		$permissions['u_radioglobe_favorite'] = ['lang' => 'ACL_U_RADIOGLOBE_FAVORITE', 'cat' => 'radioglobe'];
		$permissions['u_radioglobe_comment'] = ['lang' => 'ACL_U_RADIOGLOBE_COMMENT', 'cat' => 'radioglobe'];
		$permissions['u_radioglobe_announce'] = ['lang' => 'ACL_U_RADIOGLOBE_ANNOUNCE', 'cat' => 'radioglobe'];
		$permissions['m_radioglobe_comments'] = ['lang' => 'ACL_M_RADIOGLOBE_COMMENTS', 'cat' => 'radioglobe'];
		$event['permissions'] = $permissions;
	}

	/**
	 * Variabili del player. Il template del player viene incluso solo se
	 * l'utente puo' ascoltare e (sulla pagina del globo oppure con il
	 * player attivo in tutto il forum).
	 */
	public function add_page_vars($event)
	{
		$can_listen = (bool) $this->auth->acl_get('u_radioglobe_listen');

		if (!$can_listen)
		{
			return;
		}

		$is_page = (bool) $this->template->retrieve_var('S_RADIOGLOBE_PAGE');
		$show_player = $is_page || !empty($this->config['radioglobe_player_everywhere']);

		$guest = (int) $this->user->data['user_id'] === ANONYMOUS || !empty($this->user->data['is_bot']);
		$can_fav = !$guest && $this->auth->acl_get('u_radioglobe_favorite');
		$can_comment = !$guest && !empty($this->config['radioglobe_comments_enabled']) && $this->auth->acl_get('u_radioglobe_comment');

		$urls = [
			'pageUrl'			=> $this->safe_route('salvocortesiano_radioglobe_page', [], false),
			'stationUrl'		=> $this->safe_route('salvocortesiano_radioglobe_station', ['station_id' => 0], false),
			'nowPlayingUrl'		=> $this->safe_route('salvocortesiano_radioglobe_nowplaying', ['station_id' => 0], false),
			'favoritesUrl'		=> $this->safe_route('salvocortesiano_radioglobe_favorites', [], false),
			'favoriteUrl'		=> $this->safe_route('salvocortesiano_radioglobe_favorite_toggle', [], false),
			'commentsUrl'		=> $this->safe_route('salvocortesiano_radioglobe_comments', ['station_id' => 0], false),
			'commentAddUrl'		=> $this->safe_route('salvocortesiano_radioglobe_comment_add', [], false),
			'commentDeleteUrl'	=> $this->safe_route('salvocortesiano_radioglobe_comment_delete', [], false),
			'listenUrl'			=> $this->safe_route('salvocortesiano_radioglobe_listen', [], false),
			'listeningUrl'		=> $this->safe_route('salvocortesiano_radioglobe_listening', [], false),
		];

		// Cache del router non allineata (file appena caricati, cache non
		// svuotata): niente player ne' voce di menu, ma il forum resta vivo.
		if (in_array('', $urls, true))
		{
			return;
		}

		$toast = !empty($this->config['radioglobe_toast_enabled']);
		$toast_config = [
			'listeningUrl'	=> $urls['listeningUrl'],
			'pageUrl'		=> $urls['pageUrl'],
			'seconds'		=> max(2, min(30, isset($this->config['radioglobe_toast_seconds']) ? (int) $this->config['radioglobe_toast_seconds'] : 5)),
			'lang'			=> [
				'listening'	=> $this->user->lang('RADIOGLOBE_TOAST_LISTENING'),
				'play'		=> $this->user->lang('RADIOGLOBE_TOAST_PLAY'),
				'close'		=> $this->user->lang('RADIOGLOBE_CLOSE'),
			],
		];

		$player_config = $urls + [
			'hash'				=> generate_link_hash('radioglobe_ajax'),
			'canFavorite'		=> $can_fav,
			'canComment'		=> $can_comment,
			'announce'			=> $toast && !$guest && $this->auth->acl_get('u_radioglobe_announce'),
			'commentsEnabled'	=> !empty($this->config['radioglobe_comments_enabled']),
			'nowPlaying'		=> !empty($this->config['radioglobe_nowplaying']),
			'isRadioPage'		=> $is_page,
			'userId'			=> (int) $this->user->data['user_id'],
			'cssUrl'			=> generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/theme/radioglobe.css?v=' . $this->asset_version(),
			'lang'				=> $this->js_lang(),
		];

		$this->template->assign_vars([
			'U_RADIOGLOBE_PAGE'			=> !empty($this->config['radioglobe_nav_link']) ? $this->safe_route('salvocortesiano_radioglobe_page') : '',
			'S_RADIOGLOBE_PLAYER'		=> $show_player,
			'S_RADIOGLOBE_TOAST'		=> $toast,
			'RADIOGLOBE_TOAST_CONFIG'	=> json_encode($toast_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			'RADIOGLOBE_PLAYER_ALPHA'	=> number_format($this->player_opacity() / 100, 2, '.', ''),
			'S_RADIOGLOBE_TRANSLUCENT'	=> $this->player_opacity() < 100,
			'RADIOGLOBE_PLAYER_CONFIG'	=> json_encode($player_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			'RADIOGLOBE_ASSET_PATH'		=> generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/template/radioglobe/',
			'RADIOGLOBE_THEME_PATH'		=> generate_board_url() . '/ext/salvocortesiano/radioglobe/styles/all/theme/',
			'RADIOGLOBE_ASSET_VER'		=> $this->asset_version(),
		]);
	}

	/**
	 * Elimina preferiti e commenti degli utenti cancellati.
	 */
	public function users_deleted($event)
	{
		$user_ids = isset($event['user_ids']) ? (array) $event['user_ids'] : [];

		$this->favorites->delete_users($user_ids);
		$this->comments->delete_users($user_ids);
		$this->listens->delete_users($user_ids);
	}

	/**
	 * Come helper->route(), ma restituisce '' invece di far cadere tutto il
	 * forum quando la rotta manca dalla cache del router
	 * (RouteNotFoundException da cache/production/url_generator.php).
	 */
	protected function safe_route($name, array $params = [], $is_amp = true)
	{
		try
		{
			return (string) $this->helper->route($name, $params, $is_amp);
		}
		catch (\Exception $e)
		{
			return '';
		}
	}

	protected function js_lang()
	{
		$keys = [
			'play'				=> 'RADIOGLOBE_PLAY',
			'pause'				=> 'RADIOGLOBE_PAUSE',
			'prev'				=> 'RADIOGLOBE_PREV',
			'next'				=> 'RADIOGLOBE_NEXT',
			'shuffle'			=> 'RADIOGLOBE_SHUFFLE',
			'repeat'			=> 'RADIOGLOBE_REPEAT',
			'live'				=> 'RADIOGLOBE_LIVE',
			'queue'				=> 'RADIOGLOBE_QUEUE',
			'nowListening'		=> 'RADIOGLOBE_NOW_LISTENING',
			'nextFrom'			=> 'RADIOGLOBE_NEXT_FROM',
			'queueEmpty'		=> 'RADIOGLOBE_QUEUE_EMPTY',
			'favorites'			=> 'RADIOGLOBE_FAVORITES',
			'favoritesEmpty'	=> 'RADIOGLOBE_FAVORITES_EMPTY',
			'favAdd'			=> 'RADIOGLOBE_FAV_ADD',
			'favRemove'			=> 'RADIOGLOBE_FAV_REMOVE',
			'comments'			=> 'RADIOGLOBE_COMMENTS',
			'commentsEmpty'		=> 'RADIOGLOBE_COMMENTS_EMPTY',
			'commentsOff'		=> 'RADIOGLOBE_COMMENTS_OFF',
			'commentPlaceholder'=> 'RADIOGLOBE_COMMENT_PLACEHOLDER',
			'commentSend'		=> 'RADIOGLOBE_COMMENT_SEND',
			'commentLogin'		=> 'RADIOGLOBE_COMMENT_LOGIN',
			'commentDelete'		=> 'RADIOGLOBE_COMMENT_DELETE',
			'commentDeleteAsk'	=> 'RADIOGLOBE_COMMENT_DELETE_ASK',
			'loadMore'			=> 'RADIOGLOBE_LOAD_MORE',
			'volume'			=> 'RADIOGLOBE_VOLUME',
			'mute'				=> 'RADIOGLOBE_MUTE',
			'miniPlayer'		=> 'RADIOGLOBE_MINI_PLAYER',
			'fullscreen'		=> 'RADIOGLOBE_FULLSCREEN',
			'openGlobe'			=> 'RADIOGLOBE_OPEN_GLOBE',
			'connecting'		=> 'RADIOGLOBE_CONNECTING',
			'streamError'		=> 'RADIOGLOBE_STREAM_ERROR',
			'mixedContent'		=> 'RADIOGLOBE_MIXED_CONTENT',
			'resume'			=> 'RADIOGLOBE_RESUME',
			'website'			=> 'RADIOGLOBE_WEBSITE',
			'close'				=> 'RADIOGLOBE_CLOSE',
			'closePlayer'		=> 'RADIOGLOBE_CLOSE_PLAYER',
			'inactive'			=> 'RADIOGLOBE_INACTIVE',
			'stations'			=> 'RADIOGLOBE_STATIONS',
			'noStations'		=> 'RADIOGLOBE_NO_STATIONS',
			'searchResults'		=> 'RADIOGLOBE_SEARCH_RESULTS',
			'nearCoords'		=> 'RADIOGLOBE_NEAR_COORDS',
			'placeRegion'		=> 'RADIOGLOBE_PLACE_REGION',
			'placeCountry'		=> 'RADIOGLOBE_PLACE_COUNTRY',
			'searching'			=> 'RADIOGLOBE_SEARCHING',
			'error'				=> 'RADIOGLOBE_ERROR',
			'webglMissing'		=> 'RADIOGLOBE_WEBGL_MISSING',
			'history'			=> 'RADIOGLOBE_HISTORY',
			'historyEmpty'		=> 'RADIOGLOBE_HISTORY_EMPTY',
			'loginToFav'		=> 'RADIOGLOBE_LOGIN_TO_FAV',
		];

		$out = [];

		foreach ($keys as $js => $lang)
		{
			$out[$js] = $this->user->lang($lang);
		}

		return $out;
	}

	/**
	 * Opacita' della barra del player scelta in ACP (10-100).
	 */
	protected function player_opacity()
	{
		$value = isset($this->config['radioglobe_player_opacity']) ? (int) $this->config['radioglobe_player_opacity'] : 100;

		return max(10, min(100, $value));
	}

	protected function asset_version()
	{
		$base = $this->root_path . 'ext/salvocortesiano/radioglobe/styles/all/';
		$latest = 0;

		foreach (['theme/radioglobe.css', 'template/radioglobe/radioglobe-player.js', 'template/radioglobe/radioglobe-globe.js', 'template/radioglobe/radioglobe-toast.js'] as $file)
		{
			$time = @filemtime($base . $file);

			if ($time && $time > $latest)
			{
				$latest = $time;
			}
		}

		return $latest ?: 1;
	}
}
