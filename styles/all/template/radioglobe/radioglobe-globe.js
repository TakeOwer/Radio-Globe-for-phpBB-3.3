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
	var searchPoint = null;		// punto cercato per coordinate, segnato da un anello
	var searchTimer = null;
	var userMoved = false;
	var reticleTimer = null;
	var pendingStation = 0;

	/*
	 * Stile dei punti (ACP > Preferenze globo):
	 *  - 'dots'    puntini a dimensione fissa sullo schermo, come Radio Garden: a ogni zoom raggio e
	 *              altezza vengono ricalcolati in proporzione all'altitudine, cosi' avvicinandosi i punti
	 *              si separano invece di ingrandirsi e coprirsi a vicenda;
	 *  - 'classic' i cilindri 3D di dimensione fissa sul terreno (comportamento originale).
	 */
	var dotsMode = cfg.markers !== 'classic';
	var START_ALTITUDE = 2.3;
	var DOT_SIZE = 0.14;		// raggio angolare di un puntino per unita' di altitudine (circa 5 px di diametro)
	var markerAltitude = START_ALTITUDE;
	var markerTimer = null;
	var markerLastResize = 0;

	/*
	 * Colore dei puntini (ACP > Preferenze globo):
	 *  - 'shades'  sfumature del colore scelto, piu' chiare dove ci sono piu' stazioni;
	 *  - 'single'  tutti i luoghi dello stesso colore;
	 *  - 'heat'    mappa di calore: blu (poche stazioni) -> verde -> giallo -> rosso (molte);
	 *  - 'country' un colore diverso per ogni paese.
	 */
	var dotColor = /^#[0-9a-f]{6}$/i.test(cfg.dotColor || '') ? cfg.dotColor.toLowerCase() : '#1ed760';
	var dotMode = ['shades', 'single', 'heat', 'country'].indexOf(cfg.dotMode) > -1 ? cfg.dotMode : 'shades';
	var dotRgb = [parseInt(dotColor.substr(1, 2), 16), parseInt(dotColor.substr(3, 2), 16), parseInt(dotColor.substr(5, 2), 16)];
	var countryColors = {};

	function mixWhite(rgb, k) {
		return 'rgb(' + rgb.map(function (c) { return Math.round(c + (255 - c) * k); }).join(',') + ')';
	}

	function rgba(rgb, a) {
		return 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + a + ')';
	}

	function pointColor(p) {
		if (dotMode === 'single') {
			return dotColor;
		}
		if (dotMode === 'heat') {
			// scala logaritmica: 1 stazione = blu (220), 60+ stazioni = rosso (0)
			var t = Math.min(1, Math.log(Math.max(1, p.n)) / Math.log(60));
			return 'hsl(' + Math.round(220 * (1 - t)) + ',90%,' + Math.round(55 + 10 * t) + '%)';
		}
		if (dotMode === 'country') {
			var cc = (p.cc || p.key || '').substr(0, 2).toUpperCase();
			if (!countryColors[cc]) {
				// tonalita' stabile ricavata dal codice paese, mescolata perche' paesi con sigle vicine abbiano colori lontani
				var x = ((cc.charCodeAt(0) || 0) << 8) | (cc.charCodeAt(1) || 0);
				x ^= x >>> 16; x = Math.imul(x, 0x85ebca6b); x ^= x >>> 13; x = Math.imul(x, 0xc2b2ae35); x ^= x >>> 16; x = x >>> 0;
				countryColors[cc] = 'hsl(' + (x % 360) + ',85%,60%)';
			}
			return countryColors[cc];
		}
		return p.n >= 25 ? mixWhite(dotRgb, 0.75) : (p.n >= 6 ? mixWhite(dotRgb, 0.3) : dotColor);
	}

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
		if (dotsMode) {
			// raggio di aggancio proporzionale ai puntini: da vicino non si "prende" un luogo lontano
			return Math.max(0.002, Math.min(5, alt * 0.5));
		}
		return Math.max(0.2, Math.min(5, alt * 1.4));
	}

	/* ------------------------------------------------------------------
	 * Dimensione dei punti
	 * ---------------------------------------------------------------- */
	function markerRadius(p) {
		if (!dotsMode) {
			return Math.min(0.5, 0.12 + Math.sqrt(p.n) * 0.04);
		}
		var size = p.n >= 25 ? 1.6 : (p.n >= 6 ? 1.25 : 1);
		return DOT_SIZE * markerAltitude * size;
	}

	function markerHeight() {
		// quasi piatti: da vicino un'altezza fissa mostrerebbe il fianco del cilindro
		// sempre proporzionale allo zoom: con un minimo fisso, da molto vicino il cilindro sarebbe piu' alto
		// che largo e si vedrebbe di lato come una stanghetta
		return dotsMode ? Math.max(0.0000001, markerAltitude * 0.0004) : 0.003;
	}

	/** Anelli di luogo e stazione: in modalita' puntini seguono lo zoom come i punti. */
	function ringScale() {
		return dotsMode ? Math.min(1, markerAltitude / 1.5) : 1;
	}

	function applyMarkerSize() {
		// funzioni nuove a ogni chiamata: globe.gl ricostruisce i punti solo se l'accessor cambia
		globe.pointAltitude(function () { return markerHeight(); })
			.pointRadius(function (p) { return markerRadius(p); });
		updateRings();
	}

	/*
	 * globe.gl da' ai cilindri un'altezza minima fissa (circa 6 km): quando da vicino il raggio dei
	 * puntini scende sotto quella misura, ai bordi dello schermo si vedrebbero di lato come stanghette.
	 * Con pointsMerge tutti i punti sono un'unica mesh centrata sulla Terra: portando ogni vertice alla
	 * stessa distanza dal centro, ogni cilindro diventa un disco piatto appoggiato sul globo.
	 */
	function flattenPointsMesh(obj) {
		if (!obj || obj.__globeObjType !== 'points' || !obj.geometry || obj.geometry.__rgFlat) { return; }
		var position = obj.geometry.getAttribute('position');
		if (!position || !position.count) { return; }
		var lift = globe.getGlobeRadius() * (1 + Math.max(0.000002, markerAltitude * 0.0004));
		var a = position.array;
		for (var i = 0; i < a.length; i += 3) {
			var len = Math.sqrt(a[i] * a[i] + a[i + 1] * a[i + 1] + a[i + 2] * a[i + 2]) || 1;
			var k = lift / len;
			a[i] *= k;
			a[i + 1] *= k;
			a[i + 2] *= k;
		}
		position.needsUpdate = true;
		obj.geometry.computeBoundingSphere();
		obj.geometry.__rgFlat = true;
	}

	/**
	 * globe.gl ricostruisce la mesh dei punti in momenti diversi (dopo il caricamento del globo, a ogni
	 * cambio di dimensione...): invece di inseguire i tempi, la si appiattisce nel momento esatto in cui
	 * viene aggiunta alla scena. Riguarda solo gli oggetti marcati da globe.gl come "points".
	 */
	function hookPointsMesh() {
		var proto = Object.getPrototypeOf(Object.getPrototypeOf(globe.scene()));
		if (!proto || typeof proto.add !== 'function' || proto.add.__rgHooked) { return; }
		var originalAdd = proto.add;
		var hookedAdd = function () {
			for (var i = 0; i < arguments.length; i++) {
				if (arguments[i] && arguments[i].__globeObjType === 'points') {
					flattenPointsMesh(arguments[i]);
				}
			}
			return originalAdd.apply(this, arguments);
		};
		hookedAdd.__rgHooked = true;
		proto.add = hookedAdd;
	}

	/** Durante lo zoom: ridimensiona al massimo ogni 250 ms e comunque alla fine del movimento. */
	function onZoom(pov) {
		if (!dotsMode || !globe) { return; }
		var alt = pov.altitude;
		var changed = Math.abs(alt - markerAltitude) / markerAltitude > 0.12;
		var now = Date.now();

		if (changed && now - markerLastResize > 250) {
			markerAltitude = alt;
			markerLastResize = now;
			applyMarkerSize();
		}

		clearTimeout(markerTimer);
		markerTimer = setTimeout(function () {
			var current = globe.pointOfView().altitude;
			if (Math.abs(current - markerAltitude) / markerAltitude > 0.02) {
				markerAltitude = current;
				markerLastResize = Date.now();
				applyMarkerSize();
			}
		}, 160);
	}

	/** Tasselli satellitari Esri World Imagery (piu' dettagliati man mano che ci si avvicina). */
	function tileUrl(x, y, level) {
		return 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/' + level + '/' + y + '/' + x;
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
			.showAtmosphere(true)
			.atmosphereColor(mixWhite(dotRgb, 0.45))
			.atmosphereAltitude(0.16)
			.pointsData([])
			.pointLat('lat')
			.pointLng('lng')
			.pointAltitude(function () { return markerHeight(); })
			.pointRadius(function (p) { return markerRadius(p); })
			.pointColor(pointColor)
			.pointResolution(dotsMode ? 16 : 8)
			.pointsTransitionDuration(0)
			.pointsMerge(true)
			.onZoom(onZoom)
			.ringsData([])
			.ringLat('lat')
			.ringLng('lng')
			.ringColor(function (r) {
				return r.kind === 'play'
					? function (t) { return rgba(dotRgb, 1 - t); }
					: function (t) { return 'rgba(255,255,255,' + (0.85 * (1 - t)) + ')'; };
			})
			.ringMaxRadius(function (r) { return (r.kind === 'play' ? 3.2 : 1.8) * ringScale(); })
			.ringPropagationSpeed(function (r) { return r.kind === 'play' ? 2.4 : 1.6; })
			.ringRepeatPeriod(function (r) { return r.kind === 'play' ? 1100 : 1600; })
			.onGlobeClick(function (coords) {
				var p = nearest(coords.lat, coords.lng, tolerance());
				// clic sul puntino: si apre il luogo e parte subito la prima stazione
				if (p) { selectPlace(p, true, true); }
			});

		if (dotsMode) {
			hookPointsMesh();
		}

		// Aspetto del globo: immagine unica (scuro, notte, Blue Marble) oppure tasselli satellitari
		if (cfg.tiles) {
			globe.globeTileEngineUrl(tileUrl);
		} else {
			globe.globeImageUrl(cfg.texture);
		}

		globe.pointOfView({ lat: 41.9, lng: 12.5, altitude: START_ALTITUDE }, 0);

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
		if (searchPoint) {
			rings.push({ lat: searchPoint.lat, lng: searchPoint.lng, kind: 'search' });
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
				// stazioni senza coordinate raccolte sulla regione ("CC_R_...") o sul paese ("CC_C")
				if (/_R_[0-9a-f]+$/.test(o.key) && L.placeRegion) {
					o.title += ' ' + L.placeRegion;
				} else if (/_C$/.test(o.key) && L.placeCountry) {
					o.title += ' ' + L.placeCountry;
				}
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
		if (searchPoint) {
			searchPoint = null;
			updateRings();
		}

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

			// coordinate: il globo va sul punto, un anello lo segna e l'elenco e' per distanza
			if (data && data.coords) {
				var c = data.coords;
				var label = (L.nearCoords || '%s').replace('%s', c.lat.toFixed(4) + ', ' + c.lng.toFixed(4));
				searchPoint = { lat: c.lat, lng: c.lng };
				ui.head.innerHTML = '';
				ui.head.appendChild(el('h2', '', label));
				flyTo(c.lat, c.lng, 0.12);
				updateRings();
				renderList(list, label, true, playAndLocate(list, label));
				return;
			}

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
