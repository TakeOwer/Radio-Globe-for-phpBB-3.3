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
use salvocortesiano\radioglobe\repository\favorite_repository;
use salvocortesiano\radioglobe\repository\comment_repository;
use salvocortesiano\radioglobe\repository\listen_repository;
use salvocortesiano\radioglobe\service\nowplaying;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Chiamate AJAX del globo e del player.
 */
class ajax
{
	protected $config;
	protected $user;
	protected $auth;
	protected $request;
	protected $cache;
	protected $stations;
	protected $favorites;
	protected $comments;
	protected $nowplaying;
	protected $listens;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request $request,
		\phpbb\cache\driver\driver_interface $cache,
		station_repository $stations,
		favorite_repository $favorites,
		comment_repository $comments,
		nowplaying $nowplaying,
		listen_repository $listens,
		$root_path,
		$php_ext
	)
	{
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->request = $request;
		$this->cache = $cache;
		$this->stations = $stations;
		$this->favorites = $favorites;
		$this->comments = $comments;
		$this->nowplaying = $nowplaying;
		$this->listens = $listens;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->user->add_lang_ext('salvocortesiano/radioglobe', 'common');
	}

	/* ------------------------------------------------------------------
	 * Utilita'
	 * ---------------------------------------------------------------- */

	protected function error($lang_key, $status = 403)
	{
		return new JsonResponse(['success' => false, 'message' => $this->user->lang($lang_key)], $status);
	}

	protected function can_listen()
	{
		return (bool) $this->auth->acl_get('u_radioglobe_listen');
	}

	protected function is_guest()
	{
		return (int) $this->user->data['user_id'] === ANONYMOUS || !empty($this->user->data['is_bot']);
	}

	protected function can_favorite()
	{
		return !$this->is_guest() && $this->auth->acl_get('u_radioglobe_favorite');
	}

	protected function can_comment()
	{
		return !$this->is_guest()
			&& !empty($this->config['radioglobe_comments_enabled'])
			&& $this->auth->acl_get('u_radioglobe_comment');
	}

	protected function can_moderate()
	{
		return !$this->is_guest() && $this->auth->acl_get('m_radioglobe_comments');
	}

	protected function valid_hash()
	{
		return check_link_hash($this->request->variable('hash', ''), 'radioglobe_ajax');
	}

	protected function public_list(array $rows)
	{
		return array_map([station_repository::class, 'to_public'], $rows);
	}

	/* ------------------------------------------------------------------
	 * Globo
	 * ---------------------------------------------------------------- */

	public function places()
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		$data = $this->cache->get('_radioglobe_places');

		if ($data === false)
		{
			$data = [
				'v'			=> (int) $this->config['radioglobe_data_version'],
				'places'	=> $this->stations->get_places_compact(),
			];
			$this->cache->put('_radioglobe_places', $data, 86400);
		}

		$response = new JsonResponse($data);

		// l'indirizzo contiene la versione dei dati: il browser puo'
		// tenerlo in cache finche' non cambia
		if ((int) $this->request->variable('v', 0) === (int) $data['v'])
		{
			$response->setPrivate();
			$response->setMaxAge(3600);
		}

		return $response;
	}

	public function place($place_key)
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		$place = $this->stations->get_place($place_key);

		if (!$place)
		{
			return $this->error('RADIOGLOBE_PLACE_NOT_FOUND', 404);
		}

		return new JsonResponse([
			'success'	=> true,
			'place'		=> [
				'key'		=> $place['place_key'],
				'title'		=> $place['place_title'],
				'country'	=> $place['country'],
				'cc'		=> $place['countrycode'],
				'lat'		=> round($place['geo_lat'] / station_repository::GEO_SCALE, 4),
				'lng'		=> round($place['geo_long'] / station_repository::GEO_SCALE, 4),
			],
			'stations'	=> $this->public_list($this->stations->get_by_place($place_key)),
		]);
	}

	public function search()
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		$term = html_entity_decode($this->request->variable('q', '', true), ENT_QUOTES, 'UTF-8');

		// coordinate ("45.46, 9.19", "45°27'N 9°11'E"...): le stazioni piu' vicine, dalla piu' vicina
		$coords = station_repository::parse_coordinates($term);

		if ($coords !== null)
		{
			$rows = $this->stations->search_near($coords[0], $coords[1]);
			$list = [];

			foreach ($rows as $row)
			{
				$item = station_repository::to_public($row);
				$item['distance'] = (int) round($row['distance_km']);
				$list[] = $item;
			}

			return new JsonResponse([
				'success'	=> true,
				'coords'	=> ['lat' => round($coords[0], 5), 'lng' => round($coords[1], 5)],
				'stations'	=> $list,
			]);
		}

		return new JsonResponse([
			'success'	=> true,
			'stations'	=> $this->public_list($this->stations->search($term)),
		]);
	}

	public function station($station_id)
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		$row = $this->stations->get_station($station_id, false);

		if (!$row)
		{
			return $this->error('RADIOGLOBE_STATION_NOT_FOUND', 404);
		}

		$data = station_repository::to_public($row);
		$data['active'] = (bool) $row['station_active'];

		return new JsonResponse([
			'success'		=> true,
			'station'		=> $data,
			'favorite'		=> $this->can_favorite() && $this->favorites->is_favorite($this->user->data['user_id'], $station_id),
			'fav_count'		=> $this->favorites->count_for_station($station_id),
			'comment_count'	=> $this->comments->count_for_station($station_id),
		]);
	}

	/**
	 * Titolo in onda e copertina.
	 */
	public function nowplaying($station_id)
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		$row = $this->stations->get_station($station_id, false);

		if (!$row)
		{
			return $this->error('RADIOGLOBE_STATION_NOT_FOUND', 404);
		}

		$info = $this->nowplaying->get($row);
		$info['success'] = true;

		return new JsonResponse($info);
	}

	/* ------------------------------------------------------------------
	 * Preferiti
	 * ---------------------------------------------------------------- */

	public function favorites()
	{
		if (!$this->can_listen() || !$this->can_favorite())
		{
			return new JsonResponse(['success' => true, 'stations' => [], 'enabled' => false]);
		}

		$rows = $this->favorites->get_user_favorites($this->user->data['user_id']);
		$list = [];

		foreach ($rows as $row)
		{
			$item = station_repository::to_public($row);
			$item['active'] = (bool) $row['station_active'];
			$list[] = $item;
		}

		return new JsonResponse(['success' => true, 'enabled' => true, 'stations' => $list]);
	}

	public function favorite_toggle()
	{
		if (!$this->can_listen() || !$this->can_favorite())
		{
			return $this->error('RADIOGLOBE_NO_FAV_PERMISSION');
		}

		if (!$this->valid_hash())
		{
			return $this->error('FORM_INVALID', 400);
		}

		$station_id = $this->request->variable('station_id', 0);

		if (!$this->stations->get_station($station_id, false))
		{
			return $this->error('RADIOGLOBE_STATION_NOT_FOUND', 404);
		}

		$now = $this->favorites->toggle($this->user->data['user_id'], $station_id);

		return new JsonResponse([
			'success'	=> true,
			'favorite'	=> $now,
			'message'	=> $this->user->lang($now ? 'RADIOGLOBE_FAV_ADDED' : 'RADIOGLOBE_FAV_REMOVED'),
		]);
	}

	/* ------------------------------------------------------------------
	 * Avviso "sta ascoltando"
	 * ---------------------------------------------------------------- */

	protected function toast_enabled()
	{
		return !empty($this->config['radioglobe_toast_enabled']);
	}

	/**
	 * Il player segnala che l'utente ha fatto partire una stazione.
	 */
	public function listen()
	{
		if (!$this->toast_enabled() || !$this->can_listen() || $this->is_guest() || !$this->auth->acl_get('u_radioglobe_announce'))
		{
			return new JsonResponse(['success' => true, 'announced' => false]);
		}

		if (!$this->valid_hash())
		{
			return $this->error('FORM_INVALID', 400);
		}

		$station_id = $this->request->variable('station_id', 0);

		if (!$this->stations->get_station($station_id))
		{
			return $this->error('RADIOGLOBE_STATION_NOT_FOUND', 404);
		}

		// promemoria del player: l'utente ascolta ancora la stessa stazione
		if ($this->request->variable('repeat', 0))
		{
			$minutes = !empty($this->config['radioglobe_toast_repeat']) ? max(1, min(1440, (int) $this->config['radioglobe_toast_repeat_minutes'])) : 0;

			return new JsonResponse([
				'success'	=> true,
				'announced'	=> $minutes && $this->listens->repeat($this->user->data['user_id'], $station_id, $minutes * 60),
			]);
		}

		return new JsonResponse([
			'success'	=> true,
			'announced'	=> $this->listens->add($this->user->data['user_id'], $station_id),
		]);
	}

	/**
	 * Ascolti degli altri utenti dopo l'evento "since". Senza "since" (prima
	 * visita) restituisce solo il punto di partenza, per non mostrare ascolti vecchi.
	 */
	public function listening()
	{
		if (!$this->toast_enabled() || !$this->can_listen())
		{
			return new JsonResponse(['success' => false, 'last' => 0, 'events' => []]);
		}

		$since = $this->request->variable('since', -1);

		// prima visita: solo il punto di partenza, niente ascolti gia' passati
		if ($since < 0)
		{
			return $this->no_store(['success' => true, 'last' => $this->listens->last_id(), 'events' => []]);
		}

		$events = [];
		$last = $since;
		$me = $this->is_guest() ? 0 : (int) $this->user->data['user_id'];

		foreach ($this->listens->recent($since, $me) as $row)
		{
			$np = $this->nowplaying->cached($row['station_id']);
			$colour = preg_match('/^[0-9a-f]{6}$/i', (string) $row['user_colour']) ? '#' . $row['user_colour'] : '';

			$events[] = [
				'id'		=> (int) $row['event_id'],
				'user'		=> $row['username'],
				'colour'	=> $colour,
				'profile'	=> append_sid(generate_board_url() . '/memberlist.' . $this->php_ext, 'mode=viewprofile&u=' . (int) $row['user_id'], false),
				'title'		=> $np ? $np['title'] : '',
				'cover'		=> $np ? $np['cover'] : '',
				'station'	=> station_repository::to_public($row),
			];
			$last = max($last, (int) $row['event_id']);
		}

		return $this->no_store(['success' => true, 'last' => $last, 'top' => $this->listens->last_id(), 'events' => $events]);
	}

	protected function no_store(array $data)
	{
		$response = new JsonResponse($data);
		$response->setPrivate();
		$response->headers->addCacheControlDirective('no-store');

		return $response;
	}

	/* ------------------------------------------------------------------
	 * Commenti
	 * ---------------------------------------------------------------- */

	public function comments($station_id)
	{
		if (!$this->can_listen())
		{
			return $this->error('RADIOGLOBE_NO_PERMISSION');
		}

		if (empty($this->config['radioglobe_comments_enabled']))
		{
			return new JsonResponse(['success' => true, 'enabled' => false, 'comments' => [], 'total' => 0]);
		}

		$per_page = max(5, (int) $this->config['radioglobe_comments_per_page']);
		$start = max(0, $this->request->variable('start', 0));

		$rows = $this->comments->get_for_station($station_id, $per_page, $start);
		$total = $this->comments->count_for_station($station_id);

		$list = [];

		foreach ($rows as $row)
		{
			$list[] = $this->format_comment($row);
		}

		return new JsonResponse([
			'success'		=> true,
			'enabled'		=> true,
			'can_comment'	=> $this->can_comment(),
			'max_length'	=> (int) $this->config['radioglobe_comment_maxlen'],
			'comments'		=> $list,
			'total'			=> $total,
			'next_start'	=> ($start + $per_page < $total) ? $start + $per_page : null,
		]);
	}

	protected function format_comment(array $row)
	{
		if (!function_exists('phpbb_get_user_avatar'))
		{
			include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		}

		$user_id = (int) $row['user_id'];
		$username = ($row['username'] !== null) ? $row['username'] : $this->user->lang('GUEST');
		$avatar = '';

		if (!empty($row['user_avatar']))
		{
			$avatar = phpbb_get_user_avatar($row, 'USER_AVATAR', false, true);
		}

		// il testo e' stato salvato gia' codificato da $request->variable()
		$text = censor_text($row['comment_text']);

		return [
			'id'			=> (int) $row['comment_id'],
			'user_html'		=> get_username_string('full', $user_id, $username, $row['user_colour']),
			'avatar_html'	=> $avatar,
			'initial'		=> utf8_strtoupper(utf8_substr(html_entity_decode($username, ENT_QUOTES, 'UTF-8'), 0, 1)),
			'time'			=> $this->user->format_date((int) $row['comment_time']),
			'text_html'		=> nl2br($text),
			'can_delete'	=> $this->can_moderate()
				|| ($user_id === (int) $this->user->data['user_id'] && $this->can_comment()),
		];
	}

	public function comment_add()
	{
		if (!$this->can_listen() || !$this->can_comment())
		{
			return $this->error('RADIOGLOBE_NO_COMMENT_PERMISSION');
		}

		if (!$this->valid_hash())
		{
			return $this->error('FORM_INVALID', 400);
		}

		$station_id = $this->request->variable('station_id', 0);

		if (!$this->stations->get_station($station_id, false))
		{
			return $this->error('RADIOGLOBE_STATION_NOT_FOUND', 404);
		}

		$text = trim(utf8_normalize_nfc($this->request->variable('text', '', true)));
		$plain = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$max = max(50, (int) $this->config['radioglobe_comment_maxlen']);

		if (utf8_strlen(trim($plain)) < 2)
		{
			return $this->error('RADIOGLOBE_COMMENT_EMPTY', 400);
		}

		if (utf8_strlen($plain) > $max)
		{
			return new JsonResponse([
				'success'	=> false,
				'message'	=> $this->user->lang('RADIOGLOBE_COMMENT_TOO_LONG', $max),
			], 400);
		}

		$flood = (int) $this->config['radioglobe_comment_flood'];

		if ($flood > 0 && !$this->can_moderate())
		{
			$last = $this->comments->last_time_for_user($this->user->data['user_id']);

			if ($last > time() - $flood)
			{
				return $this->error('RADIOGLOBE_COMMENT_FLOOD', 429);
			}
		}

		// emoji e caratteri a 4 byte come entita' HTML, come fa phpBB nei
		// messaggi: funziona anche con database in codifica utf8 a 3 byte
		$text = utf8_encode_ucr($text);

		$id = $this->comments->add($station_id, $this->user->data['user_id'], $text, $this->user->ip);

		$row = $this->comments->get_comment($id);
		$row['username'] = $this->user->data['username'];
		$row['user_colour'] = $this->user->data['user_colour'];
		$row['user_avatar'] = $this->user->data['user_avatar'];
		$row['user_avatar_type'] = $this->user->data['user_avatar_type'];
		$row['user_avatar_width'] = $this->user->data['user_avatar_width'];
		$row['user_avatar_height'] = $this->user->data['user_avatar_height'];

		return new JsonResponse([
			'success'	=> true,
			'comment'	=> $this->format_comment($row),
			'total'		=> $this->comments->count_for_station($station_id),
		]);
	}

	public function comment_delete()
	{
		if (!$this->valid_hash())
		{
			return $this->error('FORM_INVALID', 400);
		}

		$comment = $this->comments->get_comment($this->request->variable('comment_id', 0));

		if (!$comment)
		{
			return $this->error('RADIOGLOBE_COMMENT_NOT_FOUND', 404);
		}

		$own = (int) $comment['user_id'] === (int) $this->user->data['user_id'] && $this->can_comment();

		if (!$own && !$this->can_moderate())
		{
			return $this->error('RADIOGLOBE_NO_COMMENT_PERMISSION');
		}

		$this->comments->delete($comment['comment_id']);

		return new JsonResponse([
			'success'	=> true,
			'total'		=> $this->comments->count_for_station($comment['station_id']),
		]);
	}
}
