/**
 * Radio Globe - avviso "Utente sta ascoltando: stazione" in alto a destra.
 *
 * Ogni 15 secondi (solo con la scheda in primo piano) chiede al forum gli
 * ascolti iniziati dagli altri utenti dopo l'ultimo gia' visto. Il numero
 * dell'ultimo evento e' condiviso fra le schede, cosi' lo stesso avviso non
 * compare in ogni scheda aperta ne' di nuovo cambiando pagina.
 */
(function (window, document) {
	'use strict';

	var cfg = window.RadioGlobeToastConfig;
	if (!cfg || !cfg.listeningUrl || !window.fetch) { return; }

	var L = cfg.lang || {};
	var SECONDS = Math.max(2, Math.min(30, parseInt(cfg.seconds, 10) || 5));
	var POLL = 15000;
	var MAX_VISIBLE = 3;
	var FADE = 450;
	var LAST_KEY = 'radioglobe.toast.last';

	var stack = null;
	var pending = [];
	var timer = null;
	var busy = false;

	/* ------------------------------------------------------------------
	 * Ultimo evento visto (condiviso fra le schede)
	 * ---------------------------------------------------------------- */
	// -1 = mai letto: il forum risponde solo con il punto di partenza
	function readLast() {
		try {
			var v = window.localStorage.getItem(LAST_KEY);
			return v === null ? -1 : (parseInt(v, 10) || 0);
		} catch (e) {
			return memLast;
		}
	}

	function writeLast(id) {
		memLast = id;
		try { window.localStorage.setItem(LAST_KEY, String(id)); } catch (e) { /* navigazione privata */ }
	}

	var memLast = -1;

	/* ------------------------------------------------------------------
	 * Richiesta degli ascolti
	 * ---------------------------------------------------------------- */
	function poll() {
		if (busy || document.hidden) { return; }
		busy = true;

		var since = readLast();
		var url = cfg.listeningUrl + (cfg.listeningUrl.indexOf('?') > -1 ? '&' : '?') + 'since=' + since;

		fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || !data.success) { return; }

				// un'altra scheda potrebbe averli gia' mostrati nel frattempo
				var seen = readLast();
				var last = parseInt(data.last, 10) || 0;
				var top = parseInt(data.top, 10);

				// tabella svuotata (estensione reinstallata): la numerazione riparte da capo
				if (!isNaN(top) && top < seen) {
					writeLast(top);
					return;
				}

				(data.events || []).forEach(function (ev) {
					if (ev.id > seen) { pending.push(ev); }
				});

				if (last > seen || seen < 0) { writeLast(Math.max(0, last)); }
				drain();
			})
			.catch(function () { /* rete assente: si riprova al prossimo giro */ })
			.then(function () { busy = false; });
	}

	function schedule() {
		clearInterval(timer);
		timer = setInterval(poll, POLL);
	}

	/* ------------------------------------------------------------------
	 * Avvisi
	 * ---------------------------------------------------------------- */
	function el(tag, cls, text) {
		var node = document.createElement(tag);
		if (cls) { node.className = cls; }
		if (text) { node.textContent = text; }
		return node;
	}

	function initials(name) {
		var parts = String(name || '').replace(/[-_.,:;|\/()[\]"'!?]+/g, ' ').trim().split(/\s+/);
		return ((parts[0] || '?').charAt(0) + (parts[1] ? parts[1].charAt(0) : '')).toUpperCase();
	}

	function hue(name) {
		var h = 0;
		for (var i = 0; i < name.length; i++) { h = (h * 31 + name.charCodeAt(i)) % 360; }
		return h;
	}

	function getStack() {
		if (!stack) {
			stack = el('div', 'rg-lt-stack');
			stack.setAttribute('aria-live', 'polite');
			document.body.appendChild(stack);
		}
		return stack;
	}

	function drain() {
		var box = getStack();
		while (pending.length && box.querySelectorAll('.rg-lt:not(.rg-lt-out)').length < MAX_VISIBLE) {
			show(pending.shift());
		}
	}

	function playEvent(ev) {
		var s = ev.station || {};
		var RG = window.RadioGlobe;

		// player presente nella pagina: la stazione parte subito (gli stream HTTP
		// su un forum HTTPS li segnala il player stesso)
		if (RG && typeof RG.play === 'function') {
			RG.play(s, [s], s.name);
			return;
		}

		if (cfg.pageUrl) {
			var hash = s.place ? '#place=' + encodeURIComponent(s.place) + '&station=' + encodeURIComponent(s.id) : '';
			window.location.href = cfg.pageUrl + hash;
		}
	}

	function show(ev) {
		var s = ev.station || {};
		var toast = el('div', 'rg-lt');
		toast.setAttribute('role', 'status');

		// copertina del brano, altrimenti logo della stazione, altrimenti iniziali
		var art = el('span', 'rg-lt-art');
		var tile = el('span', 'rg-lt-tile', initials(s.name));
		tile.style.setProperty('--rg-lt-hue', hue(s.name || ''));
		art.appendChild(tile);
		var src = ev.cover || s.icon;
		if (src && /^https:\/\//i.test(src)) {
			var img = el('img');
			img.alt = '';
			img.loading = 'lazy';
			img.referrerPolicy = 'no-referrer';
			img.onload = function () { tile.hidden = true; };
			img.onerror = function () { img.remove(); };
			img.src = src;
			art.appendChild(img);
		}

		var body = el('div', 'rg-lt-body');
		var head = el('div', 'rg-lt-head');
		var user = el('a', 'rg-lt-user', ev.user);
		if (ev.profile) { user.href = ev.profile; }
		if (/^#[0-9a-f]{6}$/i.test(ev.colour || '')) { user.style.color = ev.colour; }
		user.addEventListener('click', function (e) { e.stopPropagation(); });

		// "%s sta ascoltando:" -> il nome colorato al posto di %s
		var parts = String(L.listening || '%s').split('%s');
		head.appendChild(document.createTextNode(parts[0] || ''));
		head.appendChild(user);
		head.appendChild(document.createTextNode(parts.slice(1).join('%s')));
		body.appendChild(head);

		body.appendChild(el('div', 'rg-lt-station', s.name || ''));
		if (ev.title) {
			var song = el('div', 'rg-lt-song');
			song.appendChild(el('span', 'rg-lt-note', '♪'));
			song.appendChild(document.createTextNode(' ' + ev.title));
			body.appendChild(song);
		}

		var close = el('button', 'rg-lt-close', '×');
		close.type = 'button';
		close.title = L.close || '';
		close.setAttribute('aria-label', L.close || 'Close');

		toast.title = L.play || '';
		toast.appendChild(art);
		toast.appendChild(body);
		toast.appendChild(close);
		getStack().appendChild(toast);

		// entrata in dissolvenza: la classe va aggiunta dopo il primo disegno
		requestAnimationFrame(function () {
			requestAnimationFrame(function () { toast.classList.add('rg-lt-in'); });
		});

		var left = SECONDS * 1000;
		var started = Date.now();
		var hideTimer = setTimeout(hide, left);

		function hide() {
			if (toast.classList.contains('rg-lt-out')) { return; }
			clearTimeout(hideTimer);
			toast.classList.remove('rg-lt-in');
			toast.classList.add('rg-lt-out');
			setTimeout(function () {
				toast.remove();
				drain();
			}, FADE);
		}

		// con il mouse sopra l'avviso resta aperto, poi riprende il conto
		toast.addEventListener('mouseenter', function () {
			clearTimeout(hideTimer);
			left -= Date.now() - started;
		});
		toast.addEventListener('mouseleave', function () {
			started = Date.now();
			hideTimer = setTimeout(hide, Math.max(1200, left));
		});

		close.addEventListener('click', function (e) { e.stopPropagation(); hide(); });
		toast.addEventListener('click', function () { hide(); playEvent(ev); });
	}

	/* ------------------------------------------------------------------
	 * Avvio
	 * ---------------------------------------------------------------- */
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) { poll(); }
	});

	schedule();
	poll();
})(window, document);
