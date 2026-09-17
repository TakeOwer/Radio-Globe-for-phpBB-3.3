/**
 * Radio Globe - globo 3D in stile Radio Garden.
 *
 * Usa globe.gl (MIT, Vasco Asturiano) e l'API del player
 * (window.RadioGlobe) per la riproduzione.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
(function (window, document) {
	'use strict';

	var RG = window.RadioGlobe;
	var cfg = window.RadioGlobePageConfig;

	if (!RG || !cfg) {
		return;
	}

	var L = RG.config.lang;

	var ui = {
		stage: document.getElementById('rg-stage'),
		globe: document.getElementById('rg-globe'),
		status: document.getElementById('rg-globe-status'),
		tooltip: document.getElementById('rg-tooltip'),
		reticle: document.getElementById('rg-reticle'),
		reticleLabel: document.getElementById('rg-reticle-label'),
		search: document.getElementById('rg-search'),
		tabs: document.getElementById('rg-side-tabs'),
		head: document.getElementById('rg-side-head'),
		list: document.getElementById('rg-side-list')
	};

	if (!ui.globe) {
		return;
	}

	var places = [];
	var byKey = {};
	var globe = null;
	var view = 'explore';
	var currentPlace = null;
	var currentStations = [];
	var searchTerm = '';
	var searchTimer = null;
	var userMoved = false;
	var reticleTimer = null;
	var pendingStation = 0;

	/* ------------------------------------------------------------------
	 * Utilita'
	 * ---------------------------------------------------------------- */
	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (text !== undefined && text !== null) { n.textContent = text; }
		return n;
	}

	function hasWebGL() {
		try {
			var c = document.createElement('canvas');
			return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
		} catch (e) {
			return false;
		}
	}

	var RAD = Math.PI / 180;

	/** Distanza angolare in gradi fra due punti della sfera. */
	function angularDistance(lat1, lng1, lat2, lng2) {
		var dLat = (lat2 - lat1) * RAD;
		var dLng = (lng2 - lng1) * RAD;
		var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(lat1 * RAD) * Math.cos(lat2 * RAD) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
		return 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) / RAD;
	}

	function nearest(lat, lng, maxDeg) {
		var best = null;
		var bestD = maxDeg;
		for (var i = 0; i < places.length; i++) {
			var p = places[i];
			// scarto veloce prima del calcolo completo
			if (Math.abs(p.lat - lat) > bestD) { continue; }
			var d = angularDistance(lat, lng, p.lat, p.lng);
			if (d < bestD) { bestD = d; best = p; }
		}
		return best;
	}

	function tolerance() {
		var alt = globe ? globe.pointOfView().altitude : 2.5;
		return Math.max(0.2, Math.min(5, alt * 1.4));
	}

	function setStatus(text) {
		ui.status.textContent = text || '';
		ui.status.hidden = !text;
	}

	/* ------------------------------------------------------------------
	 * Globo
	 * ---------------------------------------------------------------- */
	function initGlobe() {
		if (!hasWebGL() || typeof window.Globe !== 'function') {
			setStatus(L.webglMissing);
			ui.reticle.hidden = true;
			return;
		}

		globe = new window.Globe(ui.globe, { animateIn: true })
			.width(ui.stage.clientWidth)
			.height(ui.stage.clientHeight)
			.backgroundColor('rgba(0,0,0,0)')
			.backgroundImageUrl(cfg.sky)
			.globeImageUrl(cfg.texture)
			.showAtmosphere(true)
			.atmosphereColor('#7fe6a8')
			.atmosphereAltitude(0.16)
			.pointsData([])
			.pointLat('lat')
			.pointLng('lng')
			.pointAltitude(0.003)
			.pointRadius(function (p) { return Math.min(0.5, 0.12 + Math.sqrt(p.n) * 0.04); })
			.pointColor(function (p) { return p.n >= 25 ? '#c8ffd9' : (p.n >= 6 ? '#5cf08f' : '#1ed760'); })
			.pointResolution(8)
			.pointsMerge(true)
			.ringsData([])
			.ringLat('lat')
			.ringLng('lng')
			.ringColor(function (r) {
				return r.kind === 'play'
					? function (t) { return 'rgba(30,215,96,' + (1 - t) + ')'; }
					: function (t) { return 'rgba(255,255,255,' + (0.85 * (1 - t)) + ')'; };
			})
			.ringMaxRadius(function (r) { return r.kind === 'play' ? 3.2 : 1.8; })
			.ringPropagationSpeed(function (r) { return r.kind === 'play' ? 2.4 : 1.6; })
			.ringRepeatPeriod(function (r) { return r.kind === 'play' ? 1100 : 1600; })
			.onGlobeClick(function (coords) {
				var p = nearest(coords.lat, coords.lng, tolerance());
				// clic sul puntino: si apre il luogo e parte subito la prima stazione
				if (p) { selectPlace(p, true, true); }
			});

		globe.pointOfView({ lat: 41.9, lng: 12.5, altitude: 2.3 }, 0);

		var controls = globe.controls();
		controls.autoRotate = !!cfg.autorotate;
		controls.autoRotateSpeed = 0.35;
		controls.enableDamping = true;
		controls.dampingFactor = 0.12;

		controls.addEventListener('start', function () {
			userMoved = true;
			controls.autoRotate = false;
			ui.tooltip.hidden = true;
		});

		controls.addEventListener('change', function () {
			if (!userMoved) { return; }
			updateReticle();
			clearTimeout(reticleTimer);
			reticleTimer = setTimeout(reticleSettled, 650);
		});

		ui.globe.addEventListener('mousemove', onHover);
		ui.globe.addEventListener('mouseleave', function () {
			ui.tooltip.hidden = true;
			ui.globe.style.cursor = '';
		});

		if ('ResizeObserver' in window) {
			new ResizeObserver(function () {
				globe.width(ui.stage.clientWidth).height(ui.stage.clientHeight);
			}).observe(ui.stage);
		} else {
			window.addEventListener('resize', function () {
				globe.width(ui.stage.clientWidth).height(ui.stage.clientHeight);
			});
		}
	}

	var hoverFrame = 0;

	function onHover(e) {
		if (!globe || hoverFrame) { return; }
		hoverFrame = requestAnimationFrame(function () {
			hoverFrame = 0;
			var rect = ui.globe.getBoundingClientRect();
			var coords = globe.toGlobeCoords(e.clientX - rect.left, e.clientY - rect.top);
			var p = coords ? nearest(coords.lat, coords.lng, tolerance()) : null;

			if (!p) {
				ui.tooltip.hidden = true;
				ui.globe.style.cursor = '';
				return;
			}

			ui.globe.style.cursor = 'pointer';
			ui.tooltip.textContent = '';
			ui.tooltip.appendChild(el('strong', '', p.title));
			ui.tooltip.appendChild(el('span', '', p.country + ' \u00b7 ' + p.n + ' ' + L.stations));
			ui.tooltip.style.left = (e.clientX - rect.left + 14) + 'px';
			ui.tooltip.style.top = (e.clientY - rect.top + 14) + 'px';
			ui.tooltip.hidden = false;
		});
	}

	/** Mirino al centro, come in Radio Garden. */
	function updateReticle() {
		var pov = globe.pointOfView();
		var p = nearest(pov.lat, pov.lng, tolerance() * 0.55);
		ui.reticle.classList.toggle('rg-hit', !!p);
		if (p) {
			ui.reticleLabel.textContent = p.title + ', ' + p.country;
			ui.reticleLabel.hidden = false;
		} else {
			ui.reticleLabel.hidden = true;
		}
		return p;
	}

	function reticleSettled() {
		if (view !== 'explore' || searchTerm) { return; }
		var p = updateReticle();
		if (p && (!currentPlace || currentPlace.key !== p.key)) {
			selectPlace(p, false);
		}
	}

	function flyTo(lat, lng, altitude) {
		if (!globe) { return; }
		var pov = globe.pointOfView();
		globe.controls().autoRotate = false;
		globe.pointOfView({ lat: lat, lng: lng, altitude: altitude || Math.min(pov.altitude, 1.1) }, 1100);
	}

	function updateRings() {
		if (!globe) { return; }
		var rings = [];
		if (currentPlace) {
			rings.push({ lat: currentPlace.lat, lng: currentPlace.lng, kind: 'place' });
		}
		var s = RG.current();
		if (s && typeof s.lat === 'number') {
			rings.push({ lat: s.lat, lng: s.lng, kind: 'play' });
		}
		globe.ringsData(rings);
	}

	/* ------------------------------------------------------------------
	 * Dati
	 * ---------------------------------------------------------------- */
	function loadPlaces() {
		var url = cfg.placesUrl + (cfg.placesUrl.indexOf('?') > -1 ? '&' : '?') + 'v=' + cfg.dataVersion;

		return RG.request(url).then(function (data) {
			places = ((data && data.places) || []).map(function (p) {
				var o = { key: p[0], lat: p[1], lng: p[2], n: p[3], title: p[4], country: p[5], cc: p[6] };
				byKey[o.key] = o;
				return o;
			});

			if (globe) {
				globe.pointsData(places);
			}

			setStatus(places.length ? '' : '');
			return places;
		}).catch(function () {
			setStatus(L.error);
			return [];
		});
	}

	function selectPlace(place, fly, autoplay) {
		currentPlace = place;
		updateRings();

		if (fly) {
			flyTo(place.lat, place.lng);
		}

		if (history.replaceState) {
			history.replaceState(null, '', '#place=' + encodeURIComponent(place.key));
		}

		if (searchTerm) {
			searchTerm = '';
			ui.search.value = '';
		}

		if (view !== 'explore') {
			setView('explore', true);
		}

		renderPlaceHead(place, true);

		RG.request(cfg.placeUrl.replace('__KEY__', encodeURIComponent(place.key))).then(function (data) {
			if (currentPlace !== place || view !== 'explore' || searchTerm) { return; }
			currentStations = (data && data.stations) || [];
			renderPlaceHead(place, false);
			renderList(currentStations, place.title, false);

			if (autoplay) {
				var queue = playable(currentStations);
				if (queue.length) { RG.play(queue[0], queue, place.title); }
			}

			if (pendingStation) {
				var row = ui.list.querySelector('.rg-row[data-id="' + pendingStation + '"]');
				if (row) { row.scrollIntoView({ block: 'center' }); row.classList.add('rg-row-flash'); }
				pendingStation = 0;
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Barra laterale
	 * ---------------------------------------------------------------- */
	function playable(list) {
		return list.filter(function (s) {
			return s.active !== false && !(location.protocol === 'https:' && /^http:\/\//i.test(s.url));
		});
	}

	function renderPlaceHead(place, loading) {
		ui.head.innerHTML = '';
		var title = el('h2', '', place.title);
		var sub = el('p', '', place.country + ' \u00b7 ' + place.n + ' ' + L.stations);
		ui.head.appendChild(title);
		ui.head.appendChild(sub);

		if (!loading && currentStations.length) {
			var actions = el('div', 'rg-head-actions');
			var playBtn = el('button', 'rg-play rg-play-big');
			playBtn.type = 'button';
			playBtn.innerHTML = RG.icons.play;
			playBtn.title = L.play;
			playBtn.addEventListener('click', function () {
				var list = playable(currentStations);
				if (list.length) { RG.play(list[0], list, place.title); }
			});
			var shuffleBtn = el('button', 'rg-icon');
			shuffleBtn.type = 'button';
			shuffleBtn.innerHTML = RG.icons.shuffle;
			shuffleBtn.title = L.shuffle;
			shuffleBtn.addEventListener('click', function () {
				var list = playable(currentStations);
				if (list.length) { RG.play(list[Math.floor(Math.random() * list.length)], list, place.title); }
			});
			actions.appendChild(playBtn);
			actions.appendChild(shuffleBtn);
			ui.head.appendChild(actions);
		}

		if (loading) {
			ui.list.innerHTML = '';
			ui.list.appendChild(el('p', 'rg-empty', '\u2026'));
		}
	}

	function renderList(list, listName, showPlace, onPlay) {
		ui.list.innerHTML = '';

		if (!list.length) {
			ui.list.appendChild(el('p', 'rg-empty', L.noStations));
			return;
		}

		var queue = playable(list);

		list.forEach(function (s) {
			ui.list.appendChild(RG.stationRow(s, {
				list: queue,
				listName: listName,
				showPlace: showPlace,
				onPlay: onPlay
			}));
		});

		RG.markCurrentRows(ui.list);
	}

	function setView(name, silent) {
		view = name;
		ui.tabs.querySelectorAll('button').forEach(function (b) {
			b.classList.toggle('rg-on', b.dataset.view === name);
		});

		if (silent) { return; }

		if (name === 'favorites') {
			ui.head.innerHTML = '';
			ui.head.appendChild(el('h2', '', L.favorites));
			ui.list.innerHTML = '';
			ui.list.appendChild(el('p', 'rg-empty', '\u2026'));
			RG.loadFavorites(true).then(function (list) {
				if (view !== 'favorites') { return; }
				if (!list.length) {
					ui.list.innerHTML = '';
					ui.list.appendChild(el('p', 'rg-empty', L.favoritesEmpty));
					return;
				}
				renderList(list, L.favorites, true, playAndLocate(list, L.favorites));
			});
		} else if (name === 'history') {
			var hist = RG.history();
			ui.head.innerHTML = '';
			ui.head.appendChild(el('h2', '', L.history));
			if (!hist.length) {
				ui.list.innerHTML = '';
				ui.list.appendChild(el('p', 'rg-empty', L.historyEmpty));
				return;
			}
			renderList(hist, L.history, true, playAndLocate(hist, L.history));
		} else if (currentPlace) {
			selectPlace(currentPlace, false);
		}
	}

	/** Dalla ricerca o dai preferiti: suona e porta il globo sulla stazione. */
	function playAndLocate(list, listName) {
		return function (s) {
			RG.play(s, playable(list), listName);
			if (typeof s.lat === 'number') { flyTo(s.lat, s.lng, 0.9); }
			if (s.place && byKey[s.place]) {
				currentPlace = byKey[s.place];
				updateRings();
			}
		};
	}

	function runSearch(term) {
		searchTerm = term;

		if (term.length < 2) {
			searchTerm = '';
			setView(view === 'explore' ? 'explore' : view);
			if (view === 'explore' && !currentPlace) {
				ui.list.innerHTML = '';
			}
			return;
		}

		setView('explore', true);
		ui.head.innerHTML = '';
		ui.head.appendChild(el('h2', '', L.searchResults.replace('%s', term)));
		ui.list.innerHTML = '';
		ui.list.appendChild(el('p', 'rg-empty', L.searching));

		var url = cfg.searchUrl + (cfg.searchUrl.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(term);

		RG.request(url).then(function (data) {
			if (searchTerm !== term) { return; }
			var list = (data && data.stations) || [];
			renderList(list, L.searchResults.replace('%s', term), true, playAndLocate(list, L.searchResults.replace('%s', term)));
		});
	}

	ui.search.addEventListener('input', function () {
		clearTimeout(searchTimer);
		var term = ui.search.value.trim();
		searchTimer = setTimeout(function () { runSearch(term); }, 350);
	});

	ui.tabs.addEventListener('click', function (e) {
		var b = e.target.closest('button[data-view]');
		if (!b) { return; }
		searchTerm = '';
		ui.search.value = '';
		setView(b.dataset.view);
	});

	/* ------------------------------------------------------------------
	 * Collegamento con il player
	 * ---------------------------------------------------------------- */
	RG.on('station', function () {
		updateRings();
		RG.markCurrentRows(ui.list);
	});

	RG.on('locate', function (s) {
		if (typeof s.lat === 'number') { flyTo(s.lat, s.lng, 0.9); }
		if (s.place && byKey[s.place]) {
			pendingStation = s.id;
			selectPlace(byKey[s.place], false);
		}
	});

	RG.on('favorites', function () {
		if (view === 'favorites') { setView('favorites'); }
	});

	document.addEventListener('keydown', function (e) {
		var tag = (e.target.tagName || '').toLowerCase();
		if (e.code === 'Space' && tag !== 'input' && tag !== 'textarea' && tag !== 'button' && !e.target.isContentEditable) {
			if (RG.current()) {
				e.preventDefault();
				RG.togglePlay();
			}
		}
	});

	/* ------------------------------------------------------------------
	 * Avvio
	 * ---------------------------------------------------------------- */
	function readHash() {
		var out = {};
		location.hash.replace(/^#/, '').split('&').forEach(function (part) {
			var kv = part.split('=');
			if (kv[0]) { out[kv[0]] = decodeURIComponent(kv[1] || ''); }
		});
		return out;
	}

	initGlobe();

	if (!cfg.stationCount) {
		setStatus('');
		return;
	}

	loadPlaces().then(function () {
		var hash = readHash();

		if (hash.place && byKey[hash.place]) {
			pendingStation = parseInt(hash.station, 10) || 0;
			userMoved = true;
			selectPlace(byKey[hash.place], true);
		} else if (RG.current() && RG.current().place && byKey[RG.current().place]) {
			var s = RG.current();
			pendingStation = s.id;
			userMoved = true;
			flyTo(s.lat, s.lng, 1.2);
			selectPlace(byKey[s.place], false);
		}

		updateRings();
	});
})(window, document);
