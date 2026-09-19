/**
 * Radio Globe - player in stile Spotify.
 *
 * Player globale: barra in basso, coda, preferiti, cronologia, commenti,
 * mini player (Document Picture-in-Picture) e vista a schermo intero.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
(function (window, document) {
	'use strict';

	var cfg = window.RadioGlobeConfig;
	if (!cfg || window.RadioGlobe) {
		return;
	}

	var L = cfg.lang || {};
	var STORE_KEY = 'radioglobe.state.v1';
	var HISTORY_KEY = 'radioglobe.history.v1';
	var NP_INTERVAL = 20000;

	/* ------------------------------------------------------------------
	 * Icone (SVG disegnati per l'estensione)
	 * ---------------------------------------------------------------- */
	var S = 'fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"';
	var ICONS = {
		shuffle: '<svg viewBox="0 0 16 16" ' + S + '><path d="M1.5 4.5h2.2c1.2 0 2 .5 2.7 1.5l2.2 3.5c.6 1 1.5 1.5 2.7 1.5h2.2"/><path d="M1.5 11h2.2c.9 0 1.6-.3 2.2-.9"/><path d="M9.4 5.4c.6-.6 1.3-.9 2.2-.9h2.2"/><path d="M12.3 3l1.7 1.5-1.7 1.5"/><path d="M12.3 9.5l1.7 1.5-1.7 1.5"/></svg>',
		prev: '<svg viewBox="0 0 16 16"><rect x="2.5" y="2.5" width="1.6" height="11" rx=".6" fill="currentColor"/><path d="M13.2 3.3v9.4c0 .5-.6.8-1 .5L5.3 8.5a.6.6 0 0 1 0-1l6.9-4.7c.4-.3 1-.1 1 .5z" fill="currentColor"/></svg>',
		next: '<svg viewBox="0 0 16 16"><rect x="11.9" y="2.5" width="1.6" height="11" rx=".6" fill="currentColor"/><path d="M2.8 3.3v9.4c0 .5.6.8 1 .5l6.9-4.7a.6.6 0 0 0 0-1L3.8 2.8c-.4-.3-1-.1-1 .5z" fill="currentColor"/></svg>',
		play: '<svg viewBox="0 0 16 16"><path d="M5 3.1v9.8c0 .5.6.8 1 .5l7.6-4.9c.4-.3.4-.8 0-1L6 2.6c-.4-.3-1 0-1 .5z" fill="currentColor"/></svg>',
		pause: '<svg viewBox="0 0 16 16"><rect x="3.5" y="2.5" width="3" height="11" rx=".8" fill="currentColor"/><rect x="9.5" y="2.5" width="3" height="11" rx=".8" fill="currentColor"/></svg>',
		repeat: '<svg viewBox="0 0 16 16" ' + S + '><path d="M2.5 8V6.5A2.5 2.5 0 0 1 5 4h8"/><path d="M11 2l2 2-2 2"/><path d="M13.5 8v1.5A2.5 2.5 0 0 1 11 12H3"/><path d="M5 14l-2-2 2-2"/></svg>',
		plus: '<svg viewBox="0 0 16 16" ' + S + '><circle cx="8" cy="8" r="6.5"/><path d="M8 5v6M5 8h6"/></svg>',
		check: '<svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="7" fill="currentColor"/><path d="M4.9 8.2l2 2 4.2-4.3" fill="none" stroke="#000" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		comments: '<svg viewBox="0 0 16 16" ' + S + '><path d="M2.5 3h11a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8l-3.5 2.5V11h-2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M5 6h6M5 8.2h3.5"/></svg>',
		queue: '<svg viewBox="0 0 16 16" ' + S + '><path d="M2 3.5h12M2 7.5h12M2 11.5h6"/><path d="M10.5 10v3.4l2.9-1.7z" fill="currentColor"/></svg>',
		globe: '<svg viewBox="0 0 16 16" ' + S + '><circle cx="8" cy="8" r="6.5"/><path d="M1.5 8h13"/><path d="M8 1.5c1.9 1.9 2.8 4.1 2.8 6.5S9.9 12.6 8 14.5C6.1 12.6 5.2 10.4 5.2 8S6.1 3.4 8 1.5z"/></svg>',
		vol3: '<svg viewBox="0 0 16 16" ' + S + '><path d="M1.8 6h2.4L7.8 3v10L4.2 10H1.8z"/><path d="M10.4 5.6a3.4 3.4 0 0 1 0 4.8"/><path d="M12.4 3.6a6.2 6.2 0 0 1 0 8.8"/></svg>',
		vol1: '<svg viewBox="0 0 16 16" ' + S + '><path d="M1.8 6h2.4L7.8 3v10L4.2 10H1.8z"/><path d="M10.4 5.6a3.4 3.4 0 0 1 0 4.8"/></svg>',
		vol0: '<svg viewBox="0 0 16 16" ' + S + '><path d="M1.8 6h2.4L7.8 3v10L4.2 10H1.8z"/><path d="M10.5 6l4 4M14.5 6l-4 4"/></svg>',
		mini: '<svg viewBox="0 0 16 16" ' + S + '><rect x="1.5" y="3" width="13" height="10" rx="1.5"/><rect x="8.2" y="8" width="4.3" height="3" rx=".5" fill="currentColor" stroke="none"/></svg>',
		full: '<svg viewBox="0 0 16 16" ' + S + '><path d="M1.5 5.5v-4h4M10.5 1.5h4v4M14.5 10.5v4h-4M5.5 14.5h-4v-4"/></svg>',
		close: '<svg viewBox="0 0 16 16" ' + S + '><path d="M3.5 3.5l9 9M12.5 3.5l-9 9"/></svg>',
		link: '<svg viewBox="0 0 16 16" ' + S + '><path d="M9 2.5h4.5V7"/><path d="M13.5 2.5L7 9"/><path d="M11.5 9.5v3a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3"/></svg>',
		trash: '<svg viewBox="0 0 16 16" ' + S + '><path d="M2.5 4h11M6 4V2.5h4V4M4 4l.7 9.5h6.6L12 4"/></svg>',
		eq: '<svg viewBox="0 0 16 16" class="rg-eq"><rect x="2" y="6" width="2.4" height="8" rx=".6"/><rect x="6.8" y="3" width="2.4" height="11" rx=".6"/><rect x="11.6" y="8" width="2.4" height="6" rx=".6"/></svg>'
	};

	/* ------------------------------------------------------------------
	 * Utilita'
	 * ---------------------------------------------------------------- */
	function $(id) { return document.getElementById(id); }

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (text !== undefined && text !== null) { n.textContent = text; }
		return n;
	}

	function fmtTime(sec) {
		sec = Math.max(0, Math.floor(sec));
		var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
		var mm = (h > 0 && m < 10 ? '0' : '') + m;
		return (h > 0 ? h + ':' : '') + mm + ':' + (s < 10 ? '0' : '') + s;
	}

	function hashHue(text) {
		var h = 0;
		for (var i = 0; i < text.length; i++) { h = (h * 31 + text.charCodeAt(i)) % 360; }
		return h;
	}

	function initials(name) {
		var words = String(name || '?').replace(/[^\p{L}\p{N} ]/gu, ' ').trim().split(/\s+/);
		return ((words[0] || '?').charAt(0) + (words[1] ? words[1].charAt(0) : '')).toUpperCase();
	}

	function safeIcon(url) {
		if (!url) { return ''; }
		// su una pagina https un'immagine http verrebbe bloccata o segnalata
		return location.protocol === 'https:' ? url.replace(/^http:\/\//i, 'https://') : url;
	}

	function urlWithId(base, id) {
		return base.replace(/\/0(\?|$)/, '/' + id + '$1');
	}

	function storage() {
		try { var k = '__rg'; localStorage.setItem(k, 1); localStorage.removeItem(k); return localStorage; }
		catch (e) { return null; }
	}

	var store = storage();

	function readJSON(key, fallback) {
		if (!store) { return fallback; }
		try { var v = JSON.parse(store.getItem(key)); return v === null ? fallback : v; }
		catch (e) { return fallback; }
	}

	function writeJSON(key, value) {
		if (!store) { return; }
		try { store.setItem(key, JSON.stringify(value)); } catch (e) { /* quota */ }
	}

	function request(url, opts) {
		opts = opts || {};
		var init = { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
		if (opts.post) {
			var body = new URLSearchParams();
			body.append('hash', cfg.hash);
			Object.keys(opts.post).forEach(function (k) { body.append(k, opts.post[k]); });
			init.method = 'POST';
			init.body = body;
		}
		return fetch(url, init).then(function (r) {
			return r.json().catch(function () { return { success: false, message: L.error }; });
		});
	}

	/** Copertina: immagine se disponibile, altrimenti tessera colorata. */
	function paintArt(img, tile, src, name) {
		tile.textContent = initials(name);
		tile.style.setProperty('--rg-hue', hashHue(name || ''));
		tile.hidden = false;
		img.onload = function () { img.hidden = false; tile.hidden = true; };
		img.onerror = function () { img.hidden = true; tile.hidden = false; };
		if (src) {
			if (img.getAttribute('src') !== src) { img.hidden = true; img.src = src; }
			else if (img.complete && img.naturalWidth) { img.hidden = false; tile.hidden = true; }
		} else {
			img.removeAttribute('src');
			img.hidden = true;
		}
	}

	/* ------------------------------------------------------------------
	 * Stato
	 * ---------------------------------------------------------------- */
	var saved = readJSON(STORE_KEY, {});
	var state = {
		station: saved.station || null,
		queue: Array.isArray(saved.queue) ? saved.queue : [],
		queueName: saved.queueName || '',
		shuffle: !!saved.shuffle,
		repeat: saved.repeat !== false,
		volume: typeof saved.volume === 'number' ? saved.volume : 0.8,
		muted: !!saved.muted,
		wantPlay: !!saved.wantPlay,
		np: null,
		startedAt: 0,
		pausedAt: 0,
		loading: false,
		retries: 0,
		listenId: saved.listenId || 0,			// stazione di cui si conta il tempo di ascolto
		listenMs: typeof saved.listenMs === 'number' ? saved.listenMs : 0	// ascolto dall'ultimo avviso
	};

	var favIds = null;			// Set degli id preferiti (caricato su richiesta)
	var favList = null;
	var history = readJSON(HISTORY_KEY, []);
	var listeners = {};
	var npTimer = null;
	var tickTimer = null;
	var drawerView = null;
	var miniWin = null;
	var commentState = { stationId: 0, next: null };

	function save() {
		writeJSON(STORE_KEY, {
			station: state.station,
			queue: state.queue.slice(0, 200),
			queueName: state.queueName,
			shuffle: state.shuffle,
			repeat: state.repeat,
			volume: state.volume,
			muted: state.muted,
			wantPlay: state.wantPlay,
			listenId: state.listenId,
			listenMs: state.listenMs
		});
	}

	function emit(name, data) {
		(listeners[name] || []).forEach(function (fn) {
			try { fn(data); } catch (e) { if (window.console) { console.error(e); } }
		});
	}

	/* ------------------------------------------------------------------
	 * Elementi
	 * ---------------------------------------------------------------- */
	var ui = {
		player: $('rg-player'), audio: $('rg-audio'),
		cover: $('rg-cover'), coverTile: $('rg-cover-tile'), coverBtn: $('rg-cover-btn'),
		title: $('rg-title'), sub: $('rg-sub'), fav: $('rg-fav'),
		shuffle: $('rg-shuffle'), prev: $('rg-prev'), play: $('rg-play'), next: $('rg-next'), repeat: $('rg-repeat'),
		elapsed: $('rg-elapsed'), bar: $('rg-bar'),
		btnComments: $('rg-btn-comments'), btnQueue: $('rg-btn-queue'), btnGlobe: $('rg-btn-globe'),
		btnMute: $('rg-btn-mute'), volume: $('rg-volume'), btnMini: $('rg-btn-mini'), btnFull: $('rg-btn-full'),
		close: $('rg-close'),
		drawer: $('rg-drawer'), drawerTitle: $('rg-drawer-title'), drawerBody: $('rg-drawer-body'),
		drawerClose: $('rg-drawer-close'), drawerTabs: $('rg-drawer-tabs'),
		mini: $('rg-mini'),
		full: $('rg-full'), fullBg: $('rg-full-bg'), fullCover: $('rg-full-cover'), fullTile: $('rg-full-tile'),
		fullStation: $('rg-full-station'), fullTitle: $('rg-full-title'), fullSub: $('rg-full-sub'),
		fullControls: $('rg-full-controls'), fullClose: $('rg-full-close'),
		toast: $('rg-toast')
	};

	if (!ui.player || !ui.audio) {
		return;
	}

	var audio = ui.audio;

	ui.shuffle.innerHTML = ICONS.shuffle;
	ui.prev.innerHTML = ICONS.prev;
	ui.next.innerHTML = ICONS.next;
	ui.repeat.innerHTML = ICONS.repeat;
	ui.btnComments.innerHTML = ICONS.comments;
	ui.btnQueue.innerHTML = ICONS.queue;
	ui.btnGlobe.innerHTML = ICONS.globe;
	ui.btnMini.innerHTML = ICONS.mini;
	ui.btnFull.innerHTML = ICONS.full;
	ui.close.innerHTML = ICONS.close;
	ui.drawerClose.innerHTML = ICONS.close;
	ui.fullClose.innerHTML = ICONS.close;

	if (!cfg.commentsEnabled) {
		ui.btnComments.hidden = true;
		$('rg-tab-comments').hidden = true;
	}
	if (!cfg.canFavorite) {
		$('rg-tab-favorites').hidden = true;
	}

	/* ------------------------------------------------------------------
	 * Toast
	 * ---------------------------------------------------------------- */
	var toastTimer = null;
	function toast(msg) {
		if (!msg) { return; }
		ui.toast.textContent = msg;
		ui.toast.hidden = false;
		ui.toast.classList.remove('rg-in');
		void ui.toast.offsetWidth;
		ui.toast.classList.add('rg-in');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { ui.toast.classList.remove('rg-in'); ui.toast.hidden = true; }, 3200);
	}

	/* ------------------------------------------------------------------
	 * Slider (volume)
	 * ---------------------------------------------------------------- */
	function makeSlider(node, getValue, setValue) {
		var fill = node.querySelector('.rg-slider-fill');
		var knob = node.querySelector('.rg-slider-knob');

		function paint() {
			var v = getValue();
			fill.style.width = (v * 100) + '%';
			knob.style.left = (v * 100) + '%';
			node.setAttribute('aria-valuenow', Math.round(v * 100));
		}

		function fromEvent(e) {
			var r = node.getBoundingClientRect();
			return Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
		}

		node.addEventListener('pointerdown', function (e) {
			node.setPointerCapture(e.pointerId);
			node.classList.add('rg-drag');
			setValue(fromEvent(e));
			paint();
		});
		node.addEventListener('pointermove', function (e) {
			if (node.classList.contains('rg-drag')) { setValue(fromEvent(e)); paint(); }
		});
		node.addEventListener('pointerup', function () { node.classList.remove('rg-drag'); });
		node.addEventListener('keydown', function (e) {
			var step = e.key === 'ArrowRight' || e.key === 'ArrowUp' ? 0.05 : (e.key === 'ArrowLeft' || e.key === 'ArrowDown' ? -0.05 : 0);
			if (step) { e.preventDefault(); setValue(Math.min(1, Math.max(0, getValue() + step))); paint(); }
		});
		node.addEventListener('wheel', function (e) {
			e.preventDefault();
			setValue(Math.min(1, Math.max(0, getValue() + (e.deltaY < 0 ? 0.05 : -0.05))));
			paint();
		}, { passive: false });

		return paint;
	}

	var paintVolume = makeSlider(ui.volume, function () {
		return state.muted ? 0 : state.volume;
	}, function (v) {
		state.volume = v;
		state.muted = v === 0;
		applyVolume();
	});

	function applyVolume() {
		audio.volume = state.volume;
		audio.muted = state.muted;
		var v = state.muted ? 0 : state.volume;
		ui.btnMute.innerHTML = v === 0 ? ICONS.vol0 : (v < 0.5 ? ICONS.vol1 : ICONS.vol3);
		save();
	}

	/* ------------------------------------------------------------------
	 * Riproduzione
	 * ---------------------------------------------------------------- */
	function canPlayHere(station) {
		return !(location.protocol === 'https:' && station && station.url && /^http:\/\//i.test(station.url));
	}

	function play(station, list, listName) {
		if (!station) { return; }

		if (!canPlayHere(station)) {
			toast(L.mixedContent);
			return;
		}

		if (Array.isArray(list) && list.length) {
			state.queue = list.map(slim);
			state.queueName = listName || '';
		} else if (findIndex(station.id) === -1) {
			state.queue = [slim(station)];
			state.queueName = listName || station.name;
		}

		var changed = !state.station || state.station.id !== station.id;
		state.station = slim(station);
		state.np = null;
		state.retries = 0;

		if (changed || audio.paused) {
			startStream();
		}

		addHistory(state.station);
		broadcast();
		render();
		emit('station', state.station);

		if (changed) {
			// il promemoria "sta ancora ascoltando" riparte da zero con la nuova stazione
			state.listenId = state.station.id;
			state.listenMs = 0;
		}

		// avviso "sta ascoltando" per gli altri utenti (il forum ignora le ripetizioni)
		if (changed && cfg.announce && cfg.listenUrl) {
			request(cfg.listenUrl, { post: { station_id: state.station.id } }).catch(function () {});
		}
	}

	function slim(s) {
		return {
			id: s.id, uuid: s.uuid, name: s.name, url: s.url, home: s.home, icon: s.icon,
			tags: s.tags || [], country: s.country, cc: s.cc, state: s.state,
			codec: s.codec, bitrate: s.bitrate, https: s.https, lat: s.lat, lng: s.lng, place: s.place
		};
	}

	function findIndex(id) {
		for (var i = 0; i < state.queue.length; i++) {
			if (state.queue[i].id === id) { return i; }
		}
		return -1;
	}

	function startStream() {
		state.wantPlay = true;
		state.loading = true;
		state.startedAt = Date.now();
		state.pausedAt = 0;
		audio.src = state.station.url;
		applyVolume();
		var p = audio.play();
		if (p && p.catch) {
			p.catch(function (err) {
				state.loading = false;
				if (err && err.name === 'NotAllowedError') {
					// il browser vuole un clic prima di far partire l'audio
					ui.play.classList.add('rg-resume');
					ui.play.title = L.resume;
				}
				render();
			});
		}
		save();
		startNowPlaying();
		startTick();
		render();
	}

	function togglePlay() {
		if (!state.station) { return; }
		ui.play.classList.remove('rg-resume');

		if (audio.paused || !audio.src) {
			// dopo una pausa lunga si riparte dalla diretta, non dal buffer
			if (!audio.src || (state.pausedAt && Date.now() - state.pausedAt > 10000) || audio.error) {
				startStream();
			} else {
				state.wantPlay = true;
				audio.play().catch(function () { startStream(); });
				save();
			}
			broadcast();
		} else {
			pause();
		}
		render();
	}

	function pause() {
		state.wantPlay = false;
		state.pausedAt = Date.now();
		audio.pause();
		save();
		render();
	}

	function step(dir) {
		if (!state.queue.length) { return; }
		var idx = state.station ? findIndex(state.station.id) : -1;
		var n = state.queue.length;
		var nextIdx;

		if (state.shuffle && n > 1) {
			do { nextIdx = Math.floor(Math.random() * n); } while (nextIdx === idx);
		} else {
			nextIdx = idx < 0 ? 0 : (idx + dir + n) % n;
		}

		var tries = 0;
		while (!canPlayHere(state.queue[nextIdx]) && tries < n) {
			nextIdx = (nextIdx + dir + n) % n;
			tries++;
		}

		play(state.queue[nextIdx]);
	}

	function closePlayer() {
		pause();
		audio.removeAttribute('src');
		audio.load();
		state.station = null;
		state.np = null;
		stopNowPlaying();
		closeDrawer();
		closeMini();
		save();
		render();
		emit('station', null);
	}

	/*
	 * iPhone/iPad: l'audio puo' partire senza un clic (mirino verde sul globo) solo se l'elemento
	 * e' gia' stato avviato una volta durante un tocco. Al primo tocco/tasto lo si "sblocca"
	 * avviando e fermando subito un attimo di silenzio; sugli altri browser non cambia nulla.
	 */
	var SILENCE = 'data:audio/wav;base64,UklGRnQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YVAAAACAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgA==';
	var UNLOCK_EVENTS = ['touchend', 'pointerup', 'keydown'];
	function unlockAudio() {
		UNLOCK_EVENTS.forEach(function (t) { document.removeEventListener(t, unlockAudio, true); });
		if (!audio.paused || state.wantPlay) { return; }
		audio.src = SILENCE;
		var p = audio.play();
		if (p && p.catch) { p.catch(function () {}); }
		audio.pause();
		audio.removeAttribute('src');
		audio.load();
	}
	UNLOCK_EVENTS.forEach(function (t) { document.addEventListener(t, unlockAudio, true); });

	/*
	 * Copertina che ruota (ACP > Player e globo): circa 3 secondi a intervalli regolari,
	 * solo mentre la radio suona e la pagina e' visibile.
	 */
	var SPIN_EVERY = Math.max(0, parseInt(cfg.coverSpin, 10) || 0);
	var SPIN_CLASS = cfg.coverSpinStyle === 'flat' ? 'rg-spin-flat' : 'rg-spin-flip';

	function spinCover() {
		if (audio.paused || document.hidden || ui.player.hidden) { return; }
		ui.coverBtn.classList.remove(SPIN_CLASS);
		void ui.coverBtn.offsetWidth;	// fa ripartire l'animazione
		ui.coverBtn.classList.add(SPIN_CLASS);
	}

	if (SPIN_EVERY) {
		ui.coverBtn.addEventListener('animationend', function () { ui.coverBtn.classList.remove(SPIN_CLASS); });
		setInterval(spinCover, Math.max(5, SPIN_EVERY) * 1000);
	}

	audio.addEventListener('playing', function () {
		state.loading = false;
		state.retries = 0;
		ui.play.classList.remove('rg-resume');
		render();
	});
	audio.addEventListener('waiting', function () { state.loading = true; render(); });
	audio.addEventListener('pause', render);
	audio.addEventListener('play', render);
	audio.addEventListener('error', function () {
		if (!state.station || !audio.getAttribute('src')) { return; }
		state.loading = false;

		// ripetizione attiva = riconnessione automatica
		if (state.repeat && state.wantPlay && state.retries < 3) {
			state.retries++;
			setTimeout(function () { if (state.wantPlay && state.station) { startStream(); } }, 2500 * state.retries);
			return;
		}

		toast(L.streamError);
		state.wantPlay = false;
		save();
		render();
	});
	audio.addEventListener('ended', function () {
		if (state.repeat && state.wantPlay) { startStream(); }
	});

	/* Un solo tab alla volta: quando parte un altro tab, questo si ferma. */
	var channel = ('BroadcastChannel' in window) ? new BroadcastChannel('radioglobe') : null;
	var tabId = Math.random().toString(36).slice(2);
	function broadcast() {
		if (channel) { channel.postMessage({ type: 'playing', tab: tabId }); }
	}
	if (channel) {
		channel.onmessage = function (e) {
			if (e.data && e.data.type === 'playing' && e.data.tab !== tabId && !audio.paused) {
				pause();
			}
		};
	}

	/* ------------------------------------------------------------------
	 * Tempo trascorso
	 * ---------------------------------------------------------------- */
	function startTick() {
		clearInterval(tickTimer);
		tickTimer = setInterval(function () {
			remindListening();
			if (!state.station) { return; }
			var t = audio.paused ? (state.pausedAt ? (state.pausedAt - state.startedAt) / 1000 : 0) : (Date.now() - state.startedAt) / 1000;
			ui.elapsed.textContent = fmtTime(t);
		}, 1000);
	}

	/*
	 * Avviso ripetuto (ACP > Avviso «sta ascoltando» > Ripeti se ascolta ancora): conta solo il
	 * tempo di ascolto vero della stessa stazione, anche cambiando pagina, e allo scadere chiede
	 * al forum di mostrare di nuovo l'avviso agli altri. Il cambio di stazione resta in play().
	 */
	var LISTEN_REPEAT = cfg.announce && cfg.listenUrl ? Math.max(0, parseInt(cfg.listenRepeat, 10) || 0) : 0;
	var listenTick = Date.now();
	var listenSaved = 0;

	function remindListening() {
		var now = Date.now();
		// timer rallentati nelle schede in secondo piano: al massimo un minuto per giro
		var delta = Math.max(0, Math.min(now - listenTick, 65000));
		listenTick = now;

		if (!LISTEN_REPEAT || !state.station || audio.paused || state.loading) { return; }

		if (state.listenId !== state.station.id) {
			state.listenId = state.station.id;
			state.listenMs = 0;
		}

		state.listenMs += delta;

		if (state.listenMs < LISTEN_REPEAT * 1000) {
			if (now - listenSaved > 10000) { listenSaved = now; save(); }
			return;
		}

		state.listenMs = 0;
		listenSaved = now;
		save();
		request(cfg.listenUrl, { post: { station_id: state.station.id, repeat: 1 } }).catch(function () {});
	}

	window.addEventListener('pagehide', save);

	/* ------------------------------------------------------------------
	 * In onda adesso
	 * ---------------------------------------------------------------- */
	function startNowPlaying() {
		stopNowPlaying();
		if (!cfg.nowPlaying || !state.station) { return; }
		fetchNowPlaying();
		npTimer = setInterval(function () {
			if (!audio.paused && document.visibilityState === 'visible') { fetchNowPlaying(); }
		}, NP_INTERVAL);
	}

	function stopNowPlaying() {
		clearInterval(npTimer);
		npTimer = null;
	}

	function fetchNowPlaying() {
		var id = state.station && state.station.id;
		if (!id) { return; }
		request(urlWithId(cfg.nowPlayingUrl, id)).then(function (data) {
			if (!state.station || state.station.id !== id || !data || !data.success) { return; }
			var old = state.np ? state.np.title : '';
			state.np = data.title ? data : null;
			if ((state.np ? state.np.title : '') !== old) {
				render();
				emit('nowplaying', state.np);
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Preferiti
	 * ---------------------------------------------------------------- */
	function loadFavorites(force) {
		if (!cfg.canFavorite) { return Promise.resolve([]); }
		if (favList && !force) { return Promise.resolve(favList); }
		return request(cfg.favoritesUrl).then(function (data) {
			favList = (data && data.stations) || [];
			favIds = new Set(favList.map(function (s) { return s.id; }));
			render();
			return favList;
		});
	}

	function isFavorite(id) {
		return !!(favIds && favIds.has(id));
	}

	function toggleFavorite(station) {
		station = station || state.station;
		if (!station) { return Promise.resolve(false); }
		if (!cfg.canFavorite) { toast(L.loginToFav); return Promise.resolve(false); }

		return request(cfg.favoriteUrl, { post: { station_id: station.id } }).then(function (data) {
			if (!data.success) { toast(data.message || L.error); return false; }
			if (!favIds) { favIds = new Set(); }
			if (data.favorite) {
				favIds.add(station.id);
				if (favList) { favList.unshift(slim(station)); }
			} else {
				favIds.delete(station.id);
				if (favList) { favList = favList.filter(function (s) { return s.id !== station.id; }); }
			}
			toast(data.message);
			render();
			if (drawerView === 'favorites') { renderDrawer(); }
			emit('favorites', favList);
			return data.favorite;
		});
	}

	/* ------------------------------------------------------------------
	 * Cronologia (nel browser)
	 * ---------------------------------------------------------------- */
	function addHistory(station) {
		history = history.filter(function (s) { return s.id !== station.id; });
		history.unshift(station);
		history = history.slice(0, 30);
		writeJSON(HISTORY_KEY, history);
		if (drawerView === 'history') { renderDrawer(); }
	}

	/* ------------------------------------------------------------------
	 * Righe delle stazioni (usate anche dalla pagina del globo)
	 * ---------------------------------------------------------------- */
	function stationRow(station, opts) {
		opts = opts || {};
		var row = el('div', 'rg-row');
		row.setAttribute('role', 'button');
		row.tabIndex = 0;
		row.dataset.id = station.id;

		var art = el('span', 'rg-row-art');
		var img = el('img');
		img.alt = '';
		img.loading = 'lazy';
		img.hidden = true;
		var tile = el('span', 'rg-tile');
		art.appendChild(img);
		art.appendChild(tile);
		paintArt(img, tile, safeIcon(station.icon), station.name);

		var eq = el('span', 'rg-row-eq');
		eq.innerHTML = ICONS.eq;
		art.appendChild(eq);

		var text = el('span', 'rg-row-text');
		var name = el('span', 'rg-row-name', station.name);
		var subParts = [];
		// ricerca per coordinate: distanza dal punto cercato
		if (typeof station.distance === 'number') {
			subParts.push(station.distance < 1 ? '< 1 km' : station.distance.toLocaleString() + ' km');
		}
		if (opts.showPlace !== false) {
			subParts.push([station.state, station.country].filter(Boolean).join(', '));
		}
		if (station.tags && station.tags.length) { subParts.push(station.tags.slice(0, 3).join(', ')); }
		var sub = el('span', 'rg-row-sub', subParts.filter(Boolean).join(' \u00b7 '));
		text.appendChild(name);
		text.appendChild(sub);

		row.appendChild(art);
		row.appendChild(text);

		var blocked = !canPlayHere(station) || station.active === false;
		if (blocked) {
			row.classList.add('rg-row-blocked');
			row.title = station.active === false ? L.inactive : L.mixedContent;
		}

		if (station.bitrate) {
			row.appendChild(el('span', 'rg-row-meta', (station.codec ? station.codec + ' ' : '') + station.bitrate + 'k'));
		}

		if (cfg.canFavorite) {
			var fav = el('button', 'rg-icon rg-row-fav');
			fav.type = 'button';
			var on = isFavorite(station.id);
			fav.innerHTML = on ? ICONS.check : ICONS.plus;
			fav.classList.toggle('rg-on', on);
			fav.title = on ? L.favRemove : L.favAdd;
			fav.addEventListener('click', function (e) {
				e.stopPropagation();
				toggleFavorite(station).then(function (now) {
					fav.innerHTML = now ? ICONS.check : ICONS.plus;
					fav.classList.toggle('rg-on', !!now);
				});
			});
			row.appendChild(fav);
		}

		function activate() {
			if (blocked) { toast(row.title); return; }
			if (opts.onPlay) { opts.onPlay(station); } else { play(station, opts.list, opts.listName); }
		}

		row.addEventListener('click', activate);
		row.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
		});

		if (state.station && state.station.id === station.id) {
			row.classList.add('rg-row-current');
		}

		return row;
	}

	function markCurrentRows(root) {
		var id = state.station ? String(state.station.id) : '';
		(root || document).querySelectorAll('.rg-row').forEach(function (r) {
			r.classList.toggle('rg-row-current', r.dataset.id === id);
			r.classList.toggle('rg-row-playing', r.dataset.id === id && !audio.paused);
		});
	}

	/* ------------------------------------------------------------------
	 * Pannello laterale: coda / preferiti / cronologia / commenti
	 * ---------------------------------------------------------------- */
	function openDrawer(view) {
		if (drawerView === view && !ui.drawer.hidden) { closeDrawer(); return; }
		drawerView = view;
		ui.drawer.hidden = false;
		document.body.classList.add('rg-drawer-open');
		renderDrawer();
		render();
	}

	function closeDrawer() {
		drawerView = null;
		ui.drawer.hidden = true;
		document.body.classList.remove('rg-drawer-open');
		render();
	}

	function sectionTitle(text) {
		return el('h4', 'rg-section', text);
	}

	function emptyNote(text) {
		return el('p', 'rg-empty', text);
	}

	function renderDrawer() {
		if (!drawerView) { return; }
		var body = ui.drawerBody;
		body.innerHTML = '';

		ui.drawerTabs.querySelectorAll('button').forEach(function (b) {
			b.classList.toggle('rg-on', b.dataset.view === drawerView);
		});

		if (drawerView === 'queue') {
			ui.drawerTitle.textContent = L.queue;
			if (!state.station) { body.appendChild(emptyNote(L.queueEmpty)); return; }

			body.appendChild(sectionTitle(L.nowListening));
			body.appendChild(stationRow(state.station));

			var idx = findIndex(state.station.id);
			var rest = [];
			for (var i = 1; i < state.queue.length; i++) {
				rest.push(state.queue[(Math.max(idx, 0) + i) % state.queue.length]);
			}
			if (rest.length) {
				body.appendChild(sectionTitle(L.nextFrom.replace('%s', state.queueName || '')));
				rest.forEach(function (s) { body.appendChild(stationRow(s)); });
			}
		} else if (drawerView === 'favorites') {
			ui.drawerTitle.textContent = L.favorites;
			body.appendChild(emptyNote('\u2026'));
			loadFavorites().then(function (list) {
				if (drawerView !== 'favorites') { return; }
				body.innerHTML = '';
				if (!list.length) { body.appendChild(emptyNote(L.favoritesEmpty)); return; }
				list.forEach(function (s) {
					body.appendChild(stationRow(s, { list: list.filter(function (x) { return x.active !== false; }), listName: L.favorites }));
				});
			});
		} else if (drawerView === 'history') {
			ui.drawerTitle.textContent = L.history;
			if (!history.length) { body.appendChild(emptyNote(L.historyEmpty)); return; }
			history.forEach(function (s) {
				body.appendChild(stationRow(s, { list: history, listName: L.history }));
			});
		} else if (drawerView === 'comments') {
			ui.drawerTitle.textContent = L.comments;
			renderComments(body);
		}
	}

	function renderComments(body) {
		var station = state.station;
		if (!station) { body.appendChild(emptyNote(L.queueEmpty)); return; }

		var head = el('div', 'rg-station-card');
		var art = el('span', 'rg-row-art rg-card-art');
		var img = el('img'); img.alt = ''; img.hidden = true;
		var tile = el('span', 'rg-tile');
		art.appendChild(img); art.appendChild(tile);
		paintArt(img, tile, safeIcon(station.icon), station.name);
		var txt = el('div', 'rg-card-text');
		txt.appendChild(el('strong', '', station.name));
		txt.appendChild(el('span', 'rg-row-sub', [station.state, station.country].filter(Boolean).join(', ')));
		if (station.home) {
			var a = el('a', 'rg-card-link');
			a.href = station.home;
			a.target = '_blank';
			a.rel = 'noopener nofollow';
			a.innerHTML = ICONS.link;
			a.appendChild(document.createTextNode(' ' + L.website));
			txt.appendChild(a);
		}
		head.appendChild(art);
		head.appendChild(txt);
		body.appendChild(head);

		var list = el('div', 'rg-comments');
		var more = el('button', 'rg-more', L.loadMore);
		more.type = 'button';
		more.hidden = true;

		if (cfg.canComment) {
			var form = el('form', 'rg-comment-form');
			var ta = el('textarea');
			ta.rows = 2;
			ta.placeholder = L.commentPlaceholder;
			var send = el('button', 'rg-pill-btn', L.commentSend);
			send.type = 'submit';
			form.appendChild(ta);
			form.appendChild(send);
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var text = ta.value.trim();
				if (text.length < 2) { return; }
				send.disabled = true;
				request(cfg.commentAddUrl, { post: { station_id: station.id, text: text } }).then(function (data) {
					send.disabled = false;
					if (!data.success) { toast(data.message || L.error); return; }
					ta.value = '';
					var empty = list.querySelector('.rg-empty');
					if (empty) { empty.remove(); }
					list.insertBefore(commentNode(data.comment), list.firstChild);
				});
			});
			ta.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { form.requestSubmit(); }
			});
			body.appendChild(form);
		} else {
			body.appendChild(emptyNote(L.commentLogin));
		}

		body.appendChild(list);
		body.appendChild(more);

		commentState = { stationId: station.id, next: 0 };

		function load() {
			var sid = station.id;
			var url = urlWithId(cfg.commentsUrl, sid) + (cfg.commentsUrl.indexOf('?') > -1 ? '&' : '?') + 'start=' + commentState.next;
			more.disabled = true;
			request(url).then(function (data) {
				more.disabled = false;
				if (commentState.stationId !== sid || drawerView !== 'comments') { return; }
				if (!data.enabled) { list.appendChild(emptyNote(L.commentsOff)); return; }
				if (!data.comments.length && commentState.next === 0) { list.appendChild(emptyNote(L.commentsEmpty)); }
				data.comments.forEach(function (c) { list.appendChild(commentNode(c)); });
				commentState.next = data.next_start;
				more.hidden = data.next_start === null;
			});
		}

		more.addEventListener('click', load);
		load();
	}

	function commentNode(c) {
		var item = el('article', 'rg-comment');
		var av = el('span', 'rg-comment-avatar');
		if (c.avatar_html) {
			av.innerHTML = c.avatar_html;
		} else {
			av.textContent = c.initial;
			av.style.setProperty('--rg-hue', hashHue(c.initial + c.id));
		}
		var main = el('div', 'rg-comment-main');
		var head = el('div', 'rg-comment-head');
		var who = el('span', 'rg-comment-user');
		who.innerHTML = c.user_html;
		head.appendChild(who);
		head.appendChild(el('span', 'rg-comment-time', c.time));
		var text = el('div', 'rg-comment-text');
		text.innerHTML = c.text_html;
		main.appendChild(head);
		main.appendChild(text);
		item.appendChild(av);
		item.appendChild(main);

		if (c.can_delete) {
			var del = el('button', 'rg-icon rg-comment-del');
			del.type = 'button';
			del.title = L.commentDelete;
			del.innerHTML = ICONS.trash;
			del.addEventListener('click', function () {
				if (!window.confirm(L.commentDeleteAsk)) { return; }
				request(cfg.commentDeleteUrl, { post: { comment_id: c.id } }).then(function (data) {
					if (data.success) { item.remove(); } else { toast(data.message || L.error); }
				});
			});
			item.appendChild(del);
		}

		return item;
	}

	/* ------------------------------------------------------------------
	 * Mini player
	 * Document Picture-in-Picture (Chrome/Edge): una finestrella sempre
	 * in primo piano, come quella di Spotify. Altrove: riquadro flottante.
	 * ---------------------------------------------------------------- */
	function miniMarkup(doc) {
		var box = doc.createElement('div');
		box.className = 'rg-mini-card';
		box.innerHTML =
			'<div class="rg-mini-art"><img alt="" hidden><span class="rg-tile"></span>' +
			'<div class="rg-mini-over">' +
				'<button type="button" class="rg-icon" data-act="prev">' + ICONS.prev + '</button>' +
				'<button type="button" class="rg-play" data-act="play"></button>' +
				'<button type="button" class="rg-icon" data-act="next">' + ICONS.next + '</button>' +
			'</div></div>' +
			'<div class="rg-mini-bottom"><div class="rg-mini-meta"><strong></strong><span></span></div>' +
			'<button type="button" class="rg-icon rg-fav" data-act="fav"></button></div>' +
			'<button type="button" class="rg-icon rg-mini-close" data-act="close">' + ICONS.close + '</button>';
		box.addEventListener('click', function (e) {
			var b = e.target.closest('[data-act]');
			if (!b) { return; }
			var act = b.getAttribute('data-act');
			if (act === 'play') { togglePlay(); }
			else if (act === 'prev') { step(-1); }
			else if (act === 'next') { step(1); }
			else if (act === 'fav') { toggleFavorite(); }
			else if (act === 'close') { closeMini(); }
		});
		return box;
	}

	function openMini() {
		if (!state.station) { return; }

		if (miniWin || !ui.mini.hidden) { closeMini(); return; }

		var pip = window.documentPictureInPicture;
		var request = null;

		if (pip && typeof pip.requestWindow === 'function') {
			try {
				request = pip.requestWindow({ width: 320, height: 380 });
			} catch (e) {
				request = null;
			}
		}

		if (request && typeof request.then === 'function') {
			request.then(function (win) {
				miniWin = win;
				var link = win.document.createElement('link');
				link.rel = 'stylesheet';
				link.href = cfg.cssUrl;
				win.document.head.appendChild(link);
				win.document.title = state.station.name;
				win.document.body.className = 'rg-pip';
				win.document.body.appendChild(miniMarkup(win.document));
				win.addEventListener('pagehide', function () { miniWin = null; render(); });
				render();
			}).catch(function () { openFloatingMini(); });
		} else {
			openFloatingMini();
		}
	}

	function openFloatingMini() {
		ui.mini.innerHTML = '';
		ui.mini.appendChild(miniMarkup(document));
		ui.mini.hidden = false;
		render();
	}

	function closeMini() {
		if (miniWin) { miniWin.close(); miniWin = null; }
		ui.mini.hidden = true;
		ui.mini.innerHTML = '';
		render();
	}

	function paintMini(root) {
		var card = root && root.querySelector('.rg-mini-card');
		if (!card || !state.station) { return; }
		var info = displayInfo();
		paintArt(card.querySelector('.rg-mini-art img'), card.querySelector('.rg-mini-art .rg-tile'), info.bigArt, state.station.name);
		card.querySelector('.rg-mini-meta strong').textContent = info.title;
		card.querySelector('.rg-mini-meta span').textContent = info.sub;
		var pb = card.querySelector('[data-act=play]');
		pb.innerHTML = audio.paused ? ICONS.play : ICONS.pause;
		pb.classList.toggle('rg-loading', state.loading && !audio.paused);
		var fb = card.querySelector('[data-act=fav]');
		fb.hidden = !cfg.canFavorite;
		fb.innerHTML = isFavorite(state.station.id) ? ICONS.check : ICONS.plus;
		fb.classList.toggle('rg-on', isFavorite(state.station.id));
	}

	/* ------------------------------------------------------------------
	 * Schermo intero
	 * ---------------------------------------------------------------- */
	function openFull() {
		if (!state.station) { return; }
		ui.full.hidden = false;
		document.body.classList.add('rg-full-open');
		if (ui.full.requestFullscreen) {
			ui.full.requestFullscreen().catch(function () { /* resta come sovrapposizione */ });
		}
		render();
	}

	function closeFull() {
		if (document.fullscreenElement === ui.full && document.exitFullscreen) {
			document.exitFullscreen().catch(function () {});
		}
		ui.full.hidden = true;
		document.body.classList.remove('rg-full-open');
	}

	document.addEventListener('fullscreenchange', function () {
		if (!document.fullscreenElement && !ui.full.hidden) { closeFull(); }
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (!ui.full.hidden) { closeFull(); }
			else if (!ui.drawer.hidden) { closeDrawer(); }
		}
	});

	ui.fullControls.innerHTML =
		'<button type="button" class="rg-icon" data-act="prev">' + ICONS.prev + '</button>' +
		'<button type="button" class="rg-play" data-act="play"></button>' +
		'<button type="button" class="rg-icon" data-act="next">' + ICONS.next + '</button>' +
		'<button type="button" class="rg-icon rg-fav" data-act="fav"></button>';
	ui.fullControls.addEventListener('click', function (e) {
		var b = e.target.closest('[data-act]');
		if (!b) { return; }
		var act = b.getAttribute('data-act');
		if (act === 'play') { togglePlay(); }
		else if (act === 'prev') { step(-1); }
		else if (act === 'next') { step(1); }
		else if (act === 'fav') { toggleFavorite(); }
	});

	/* ------------------------------------------------------------------
	 * Disegno dell'interfaccia
	 * ---------------------------------------------------------------- */
	function displayInfo() {
		var s = state.station;
		var np = state.np;
		var place = [s.state, s.country].filter(Boolean).join(', ');

		if (np && np.title) {
			return {
				title: np.track || np.title,
				sub: (np.artist ? np.artist + ' \u00b7 ' : '') + s.name,
				art: np.cover || safeIcon(s.icon),
				bigArt: np.cover_big || np.cover || safeIcon(s.icon)
			};
		}

		return {
			title: s.name,
			sub: place || (s.tags || []).slice(0, 3).join(', '),
			art: safeIcon(s.icon),
			bigArt: safeIcon(s.icon)
		};
	}

	function marquee(node) {
		var wrap = node.parentNode;
		node.classList.remove('rg-marquee');
		wrap.classList.remove('rg-overflow');
		node.style.removeProperty('--rg-shift');
		requestAnimationFrame(function () {
			var over = node.scrollWidth - wrap.clientWidth;
			if (over > 4) {
				node.style.setProperty('--rg-shift', (-over - 16) + 'px');
				node.classList.add('rg-marquee');
				wrap.classList.add('rg-overflow');
			}
		});
	}

	// cambiando la larghezza della finestra il testo puo' entrare o non
	// entrare piu': lo scorrimento va ricalcolato
	var marqueeTimer = null;
	window.addEventListener('resize', function () {
		clearTimeout(marqueeTimer);
		marqueeTimer = setTimeout(function () {
			if (state.station) { marquee(ui.title); marquee(ui.sub); }
		}, 200);
	});

	var lastTitle = '';

	function render() {
		var s = state.station;
		ui.player.hidden = !s;
		document.body.classList.toggle('rg-has-player', !!s);

		if (!s) {
			if (miniWin) { closeMini(); }
			return;
		}

		var info = displayInfo();
		paintArt(ui.cover, ui.coverTile, info.art, s.name);

		if (ui.title.textContent !== info.title) {
			ui.title.textContent = info.title;
		}
		ui.sub.textContent = info.sub;
		if (lastTitle !== info.title + info.sub) {
			lastTitle = info.title + info.sub;
			marquee(ui.title);
			marquee(ui.sub);
		}

		var playing = !audio.paused;
		ui.play.innerHTML = playing ? ICONS.pause : ICONS.play;
		ui.play.title = playing ? L.pause : L.play;
		ui.play.classList.toggle('rg-loading', state.loading && state.wantPlay);
		ui.player.classList.toggle('rg-is-playing', playing);

		ui.shuffle.classList.toggle('rg-on', state.shuffle);
		ui.repeat.classList.toggle('rg-on', state.repeat);
		var many = state.queue.length > 1;
		ui.prev.disabled = !many;
		ui.next.disabled = !many;
		ui.shuffle.disabled = !many;

		if (cfg.canFavorite) {
			ui.fav.hidden = false;
			var fav = isFavorite(s.id);
			ui.fav.innerHTML = fav ? ICONS.check : ICONS.plus;
			ui.fav.classList.toggle('rg-on', fav);
			ui.fav.title = fav ? L.favRemove : L.favAdd;
		}

		ui.btnQueue.classList.toggle('rg-on', drawerView === 'queue' || drawerView === 'favorites' || drawerView === 'history');
		ui.btnComments.classList.toggle('rg-on', drawerView === 'comments');
		ui.btnMini.classList.toggle('rg-on', !!miniWin || !ui.mini.hidden);

		ui.btnGlobe.href = cfg.pageUrl + (s.place ? '#place=' + encodeURIComponent(s.place) + '&station=' + s.id : '');

		paintVolume();

		// schermo intero
		if (!ui.full.hidden) {
			paintArt(ui.fullCover, ui.fullTile, info.bigArt, s.name);
			ui.fullBg.style.backgroundImage = info.bigArt ? 'url("' + info.bigArt.replace(/"/g, '%22') + '")' : '';
			ui.fullStation.textContent = s.name + (s.country ? ' \u00b7 ' + s.country : '');
			ui.fullTitle.textContent = info.title;
			ui.fullSub.textContent = info.sub;
			var fp = ui.fullControls.querySelector('[data-act=play]');
			fp.innerHTML = playing ? ICONS.pause : ICONS.play;
			var ff = ui.fullControls.querySelector('[data-act=fav]');
			ff.hidden = !cfg.canFavorite;
			ff.innerHTML = isFavorite(s.id) ? ICONS.check : ICONS.plus;
			ff.classList.toggle('rg-on', isFavorite(s.id));
		}

		if (miniWin) {
			paintMini(miniWin.document);
			miniWin.document.title = info.title;
		}
		if (!ui.mini.hidden) { paintMini(ui.mini); }

		if (drawerView === 'queue') { markCurrentRows(ui.drawerBody); }
		markCurrentRows(document.getElementById('rg-side'));

		updateMediaSession(info);
	}

	/* Controlli multimediali del sistema operativo / tastiera */
	function updateMediaSession(info) {
		if (!('mediaSession' in navigator) || !window.MediaMetadata) { return; }
		try {
			navigator.mediaSession.metadata = new MediaMetadata({
				title: info.title,
				artist: info.sub,
				album: state.station.name,
				artwork: info.bigArt ? [{ src: info.bigArt, sizes: '512x512' }] : []
			});
			navigator.mediaSession.playbackState = audio.paused ? 'paused' : 'playing';
		} catch (e) { /* artwork non valido */ }
	}

	if ('mediaSession' in navigator) {
		try {
			navigator.mediaSession.setActionHandler('play', function () { togglePlay(); });
			navigator.mediaSession.setActionHandler('pause', function () { pause(); });
			navigator.mediaSession.setActionHandler('previoustrack', function () { step(-1); });
			navigator.mediaSession.setActionHandler('nexttrack', function () { step(1); });
		} catch (e) { /* non supportato */ }
	}

	/* ------------------------------------------------------------------
	 * Eventi dei pulsanti
	 * ---------------------------------------------------------------- */
	ui.play.addEventListener('click', togglePlay);
	ui.prev.addEventListener('click', function () { step(-1); });
	ui.next.addEventListener('click', function () { step(1); });
	ui.shuffle.addEventListener('click', function () { state.shuffle = !state.shuffle; save(); render(); });
	ui.repeat.addEventListener('click', function () { state.repeat = !state.repeat; save(); render(); });
	ui.fav.addEventListener('click', function () { toggleFavorite(); });
	ui.btnQueue.addEventListener('click', function () { openDrawer('queue'); });
	ui.btnComments.addEventListener('click', function () { openDrawer('comments'); });
	ui.btnMute.addEventListener('click', function () {
		if (state.muted || state.volume === 0) {
			state.muted = false;
			if (state.volume === 0) { state.volume = 0.5; }
		} else {
			state.muted = true;
		}
		applyVolume();
		paintVolume();
	});
	ui.btnMini.addEventListener('click', openMini);
	ui.btnFull.addEventListener('click', openFull);
	ui.coverBtn.addEventListener('click', openFull);
	ui.fullClose.addEventListener('click', closeFull);
	ui.close.addEventListener('click', closePlayer);
	ui.drawerClose.addEventListener('click', closeDrawer);
	ui.drawerTabs.addEventListener('click', function (e) {
		var b = e.target.closest('button[data-view]');
		if (b) { drawerView = null; openDrawer(b.dataset.view); }
	});
	ui.btnGlobe.addEventListener('click', function (e) {
		if (cfg.isRadioPage && state.station) {
			e.preventDefault();
			emit('locate', state.station);
		}
	});

	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'visible' && !audio.paused) { fetchNowPlaying(); }
	});

	/* ------------------------------------------------------------------
	 * Avvio
	 * ---------------------------------------------------------------- */
	applyVolume();
	startTick();

	if (state.station) {
		if (cfg.canFavorite) { loadFavorites(); }
		render();

		if (state.wantPlay && canPlayHere(state.station)) {
			// si riprende la stazione della pagina precedente
			startStream();
		} else {
			state.wantPlay = false;
			render();
			if (cfg.nowPlaying) { fetchNowPlaying(); }
		}
	}

	/* ------------------------------------------------------------------
	 * API pubblica per la pagina del globo
	 * ---------------------------------------------------------------- */
	window.RadioGlobe = {
		config: cfg,
		icons: ICONS,
		play: play,
		togglePlay: togglePlay,
		pause: pause,
		current: function () { return state.station; },
		isPlaying: function () { return !audio.paused; },
		nowPlaying: function () { return state.np; },
		stationRow: stationRow,
		markCurrentRows: markCurrentRows,
		loadFavorites: loadFavorites,
		isFavorite: isFavorite,
		toggleFavorite: toggleFavorite,
		history: function () { return history.slice(); },
		openDrawer: openDrawer,
		toast: toast,
		request: request,
		on: function (name, fn) { (listeners[name] = listeners[name] || []).push(fn); }
	};
})(window, document);
