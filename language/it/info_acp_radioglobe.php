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
	'ACP_RADIOGLOBE_TITLE'			=> 'Radio Globe',
	'ACP_RADIOGLOBE_SETTINGS'		=> 'Impostazioni',
	'ACP_RADIOGLOBE_GROUPS'			=> 'Gruppi autorizzati',
	'ACP_RADIOGLOBE_SYNC'			=> 'Aggiornamento stazioni',
	'ACP_RADIOGLOBE_COMMENTS'		=> 'Commenti',

	'LOG_RADIOGLOBE_SYNC_STARTED'	=> '<strong>Radio Globe:</strong> avviato l’aggiornamento manuale delle stazioni',

	'RADIOGLOBE_SECONDS'			=> 'secondi',
	'RADIOGLOBE_UNIT_SECONDS'		=> 'secondi',
	'RADIOGLOBE_UNIT_MINUTES'		=> 'minuti',
	'RADIOGLOBE_UNIT_HOURS'			=> 'ore',
	'RADIOGLOBE_NO_CURL'			=> 'L’estensione cURL di PHP non è attiva: il download funziona lo stesso, ma il titolo in onda e le copertine non saranno disponibili.',

	// Impostazioni
	'RADIOGLOBE_SETTINGS_INTRO'		=> 'Le stazioni vengono scaricate da Radio Browser (servizio gratuito e open source) e salvate nel database del forum: i visitatori non interrogano mai il servizio esterno.',
	'RADIOGLOBE_SETTINGS_SAVED'		=> 'Impostazioni salvate.',
	'RADIOGLOBE_FILTERS_CHANGED'	=> 'Hai cambiato i filtri delle stazioni: saranno applicati al prossimo aggiornamento. %sAggiorna adesso%s',

	'RADIOGLOBE_DEDICATION'			=> '“A mia moglie, la donna più importante della mia vita”',
	'RADIOGLOBE_SET_SOURCE'			=> 'Stazioni e filtri',
	'RADIOGLOBE_SOURCE_NOTE'		=> 'Vengono importate le stazioni verificate come funzionanti da Radio Browser: quelle con coordinate geografiche e, se l’opzione qui sotto è attiva, anche quelle senza, collocate nella loro regione o nel loro paese. I filtri valgono dal prossimo aggiornamento.',
	'RADIOGLOBE_HTTPS_ONLY'			=> 'Solo stream HTTPS',
	'RADIOGLOBE_HTTPS_ONLY_EXPLAIN'	=> 'Consigliato se il forum è in HTTPS: i browser bloccano l’audio HTTP su pagine sicure (“mixed content”). Se disattivato, le stazioni HTTP compaiono ma sono segnalate come non riproducibili.',
	'RADIOGLOBE_EXCLUDE_HLS'		=> 'Escludi gli stream HLS',
	'RADIOGLOBE_EXCLUDE_HLS_EXPLAIN'	=> 'Gli stream HLS (.m3u8) non sono riproducibili nativamente in tutti i browser.',
	'RADIOGLOBE_NOGEO'			=> 'Includi le stazioni senza coordinate',
	'RADIOGLOBE_NOGEO_EXPLAIN'	=> 'Circa quattro stazioni su cinque di Radio Browser non hanno coordinate. Se attivo, vengono messe sul globo nella loro regione (dove si trovano le stazioni con coordinate della stessa regione) oppure, se manca la regione, sul loro paese. Sul globo compaiono come «Nome (regione)» e «Paese (tutto il paese)». Vale dal prossimo aggiornamento; conviene alzare il numero massimo di stazioni a 40.000.',
	'RADIOGLOBE_TAGS'				=> 'Generi da includere',
	'RADIOGLOBE_TAGS_EXPLAIN'		=> 'Uno per riga o separati da virgola (es. rock, jazz, news). Una stazione è inclusa se almeno uno dei suoi generi contiene una delle parole. Lascia vuoto per includere tutti i generi.',
	'RADIOGLOBE_MIN_BITRATE'		=> 'Bitrate minimo',
	'RADIOGLOBE_MIN_BITRATE_EXPLAIN'	=> '0 = nessun limite.',
	'RADIOGLOBE_MAX_STATIONS'		=> 'Numero massimo di stazioni',
	'RADIOGLOBE_MAX_STATIONS_EXPLAIN'	=> 'Si importano per prime le stazioni più ascoltate. Sugli hosting condivisi conviene non superare 30.000.',
	'RADIOGLOBE_CLUSTER_GRID'		=> 'Raggruppamento dei luoghi',
	'RADIOGLOBE_CLUSTER_GRID_EXPLAIN'	=> 'Ampiezza della zona che diventa un unico punto sul globo, in centesimi di grado: 25 = circa 25 km (una città), 100 = circa 100 km.',
	'RADIOGLOBE_API_SERVER'			=> 'Server di Radio Browser',
	'RADIOGLOBE_API_SERVER_EXPLAIN'	=> 'Lascia vuoto per la scelta automatica tra i server disponibili (consigliato).',
	'RADIOGLOBE_API_SERVER_INVALID'	=> 'Il nome del server non è valido.',

	'RADIOGLOBE_SET_CRON'			=> 'Aggiornamento automatico',
	'RADIOGLOBE_CRON_ENABLED'		=> 'Aggiornamento automatico attivo',
	'RADIOGLOBE_CRON_ENABLED_EXPLAIN'	=> 'Usa le attività pianificate di phpBB, che partono con le visite al forum: all’ora scelta, o alla prima visita successiva, l’aggiornamento si avvia e prosegue a piccoli passi.',
	'RADIOGLOBE_CRON_HOURS'			=> 'Ore di avvio',
	'RADIOGLOBE_CRON_HOURS_EXPLAIN'	=> 'Seleziona una o più ore. Fuso orario del forum:',
	'RADIOGLOBE_CRON_HOURS_EMPTY'	=> 'Seleziona almeno un’ora di avvio, oppure disattiva l’aggiornamento automatico.',
	'RADIOGLOBE_CRON_PRESETS'		=> 'Selezione rapida',
	'RADIOGLOBE_CRON_PRESETS_EXPLAIN'	=> 'Parte dalla prima ora già selezionata (le 04:00 se non ce n’è nessuna).',
	'RADIOGLOBE_EVERY_24'			=> 'Ogni 24 ore',
	'RADIOGLOBE_EVERY_12'			=> 'Ogni 12 ore',
	'RADIOGLOBE_EVERY_6'			=> 'Ogni 6 ore',

	'RADIOGLOBE_SET_PLAYER'			=> 'Player e globo',
	'RADIOGLOBE_PLAYER_OPACITY'		=> 'Trasparenza del player',
	'RADIOGLOBE_PLAYER_OPACITY_EXPLAIN'	=> 'Opacità dello sfondo della barra del player: 100% è nero pieno, valori più bassi lasciano intravedere la pagina sotto (con una leggera sfocatura). Muovi il cursore e guarda l’anteprima.',
	'RADIOGLOBE_PREVIEW'			=> 'Anteprima dal vivo',
	'RADIOGLOBE_PREVIEW_NOTE'		=> 'Anteprima d’esempio: il contenuto della pagina scorre sotto la barra.',
	'RADIOGLOBE_COVER_SPIN'			=> 'Copertina che ruota',
	'RADIOGLOBE_COVER_SPIN_EXPLAIN'	=> 'La copertina (o il logo della stazione) a sinistra nel player ruota per circa 3 secondi a intervalli regolari, solo mentre una stazione è in riproduzione.',
	'RADIOGLOBE_COVER_SPIN_EVERY'	=> 'Ruota ogni',
	'RADIOGLOBE_COVER_SPIN_EVERY_EXPLAIN'	=> 'Da 5 secondi a 24 ore.',
	'RADIOGLOBE_COVER_SPIN_STYLE'	=> 'Tipo di rotazione',
	'RADIOGLOBE_COVER_SPIN_STYLE_EXPLAIN'	=> '«Prova» fa ruotare la copertina dell’anteprima qui sopra.',
	'RADIOGLOBE_COVER_SPIN_FLIP'	=> '3D, come una moneta',
	'RADIOGLOBE_COVER_SPIN_FLAT'	=> 'Piatta, come un disco',
	'RADIOGLOBE_COVER_SPIN_TEST'	=> 'Prova',
	'RADIOGLOBE_PLAYER_EVERYWHERE'	=> 'Player in tutto il forum',
	'RADIOGLOBE_PLAYER_EVERYWHERE_EXPLAIN'	=> 'La barra del player resta visibile navigando nel forum e riprende la stazione a ogni cambio pagina. Se disattivato compare solo nella pagina del globo.',
	'RADIOGLOBE_NAV_LINK'			=> 'Voce “Radio” nel menu',
	'RADIOGLOBE_NOWPLAYING'			=> 'Mostra il titolo in onda',
	'RADIOGLOBE_NOWPLAYING_EXPLAIN'	=> 'Il forum legge periodicamente i metadati ICY dello stream (“Artista - Titolo”). Non tutte le radio li trasmettono. Il risultato resta in cache 25 secondi per stazione.',
	'RADIOGLOBE_COVERS'				=> 'Cerca le copertine',
	'RADIOGLOBE_COVERS_EXPLAIN'		=> 'Gli stream non contengono copertine: vengono cercate sull’iTunes Search API partendo dal titolo in onda e tenute in cache per 7 giorni. Senza risultato si usa il logo della stazione.',
	'RADIOGLOBE_TEXTURE'			=> 'Aspetto del globo',
	'RADIOGLOBE_TEXTURE_DARK'		=> 'Scuro (leggero, consigliato)',
	'RADIOGLOBE_TEXTURE_NIGHT'		=> 'Terra di notte',
	'RADIOGLOBE_TEXTURE_MARBLE'		=> 'Blue Marble (più pesante)',
	'RADIOGLOBE_TEXTURE_SATELLITE'	=> 'Satellite dettagliato (stile Radio Garden)',
	'RADIOGLOBE_TEXTURE_EXPLAIN'	=> 'Con «Satellite dettagliato» il globo carica le immagini satellitari Esri World Imagery, sempre più definite man mano che ci si avvicina. L’attribuzione a Esri compare in basso sul globo.',
	'RADIOGLOBE_MARKERS'			=> 'Stile dei punti',
	'RADIOGLOBE_MARKERS_EXPLAIN'	=> 'Con i puntini fissi i luoghi restano piccoli a ogni zoom e si separano avvicinandosi, come in Radio Garden. I punti classici hanno una dimensione fissa sul terreno e da vicino si sovrappongono.',
	'RADIOGLOBE_MARKERS_DOTS'		=> 'Puntini fissi (stile Radio Garden, consigliato)',
	'RADIOGLOBE_MARKERS_CLASSIC'	=> 'Punti classici 3D',
	'RADIOGLOBE_DOT_COLOR'          	=> 'Colore dei puntini',
	'RADIOGLOBE_DOT_COLOR_EXPLAIN'  	=> 'Colore dei luoghi con stazioni sul globo. Anche l’alone dell’atmosfera e l’anello della stazione in ascolto prendono questo colore.',
	'RADIOGLOBE_DOT_MODE'           	=> 'Colorazione dei puntini',
	'RADIOGLOBE_DOT_MODE_EXPLAIN'   	=> '«Mappa di calore» e «Un colore per paese» ignorano il colore scelto per i puntini (resta usato per atmosfera e anello).',
	'RADIOGLOBE_DOT_MODE_SHADES'    	=> 'Sfumature del colore scelto (più chiaro dove ci sono più stazioni)',
	'RADIOGLOBE_DOT_MODE_SINGLE'    	=> 'Tinta unita',
	'RADIOGLOBE_DOT_MODE_HEAT'      	=> 'Mappa di calore (blu → rosso secondo il numero di stazioni)',
	'RADIOGLOBE_DOT_MODE_COUNTRY'   	=> 'Un colore per paese',
	'RADIOGLOBE_DOT_PREVIEW'        	=> 'Anteprima',
	'RADIOGLOBE_DOT_RESET'          	=> 'Ripristina verde',
	'RADIOGLOBE_AUTOROTATE'			=> 'Rotazione automatica del globo',

	'RADIOGLOBE_SET_TOAST'			=> 'Avviso «sta ascoltando»',
	'RADIOGLOBE_TOAST_ENABLED'		=> 'Mostra chi sta ascoltando',
	'RADIOGLOBE_TOAST_ENABLED_EXPLAIN'	=> 'Quando un utente fa partire una stazione, in alto a destra su tutte le pagine del forum compare per qualche secondo «Utente sta ascoltando: stazione» con il titolo in onda, se disponibile. Il nome ha il colore del gruppo. Cliccando l’avviso si ascolta la stessa stazione. Lo vedono gli utenti che possono ascoltare la radio; vengono annunciati solo gli utenti dei gruppi con «Ascolto visibile agli altri» (scheda Gruppi autorizzati).',
	'RADIOGLOBE_TOAST_SECONDS'		=> 'Durata dell’avviso',
	'RADIOGLOBE_TOAST_SECONDS_EXPLAIN'	=> 'Da 2 a 30 secondi. Con il mouse sopra l’avviso resta aperto.',
	'RADIOGLOBE_TOAST_REPEAT'		=> 'Ripeti se ascolta ancora',
	'RADIOGLOBE_TOAST_REPEAT_EXPLAIN'	=> 'Mostra di nuovo l’avviso a intervalli regolari finché l’utente continua ad ascoltare la stessa stazione. Quando cambia stazione l’avviso compare subito, come prima.',
	'RADIOGLOBE_TOAST_REPEAT_MINUTES'	=> 'Ripeti ogni',
	'RADIOGLOBE_TOAST_REPEAT_MINUTES_EXPLAIN'	=> 'Da 1 a 1440 minuti (24 ore). Conta solo il tempo di ascolto vero: le pause non contano.',

	'RADIOGLOBE_SET_COMMENTS'		=> 'Commenti alle stazioni',
	'RADIOGLOBE_COMMENTS_ENABLED'	=> 'Commenti attivi',
	'RADIOGLOBE_COMMENT_MAXLEN'		=> 'Lunghezza massima di un commento',
	'RADIOGLOBE_COMMENTS_PER_PAGE'	=> 'Commenti caricati per volta',
	'RADIOGLOBE_COMMENT_FLOOD'		=> 'Intervallo minimo fra due commenti',
	'RADIOGLOBE_COMMENT_FLOOD_EXPLAIN'	=> 'Non vale per i moderatori. 0 = nessun limite.',

	// Aggiornamento
	'RADIOGLOBE_SYNC_INTRO'			=> 'Da qui puoi aggiornare subito l’elenco delle stazioni. La barra mostra l’avanzamento passo per passo: lascia aperta la pagina finché non arriva al 100%.',
	'RADIOGLOBE_SYNC_STATUS'		=> 'Stato',
	'RADIOGLOBE_SYNC_LAST'			=> 'Ultimo aggiornamento completato',
	'RADIOGLOBE_SYNC_COUNT'			=> 'Stazioni attive',
	'RADIOGLOBE_SYNC_PLACES'		=> 'Luoghi sul globo',
	'RADIOGLOBE_SYNC_DURATION'		=> 'Durata',
	'RADIOGLOBE_SYNC_HOURS'			=> 'Ore di avvio automatico',
	'RADIOGLOBE_SYNC_NEXT'			=> 'Prossimo avvio automatico',
	'RADIOGLOBE_SYNC_START'			=> 'Aggiorna le stazioni ora',
	'RADIOGLOBE_SYNC_CANCEL'		=> 'Annulla l’aggiornamento',
	'RADIOGLOBE_SYNC_CANCELLED'		=> 'Aggiornamento annullato.',
	'RADIOGLOBE_SYNC_RUNNING'		=> 'Aggiornamento in corso',
	'RADIOGLOBE_SYNC_SKIPPED'		=> '(%d stazioni non valide saltate)',
	'RADIOGLOBE_BADGE_VERSION'		=> 'versione',
	'RADIOGLOBE_BADGE_LICENSE'		=> 'licenza',
	'RADIOGLOBE_SYNC_PROGRESS'		=> 'Avanzamento',
	'RADIOGLOBE_SYNC_WAIT'			=> 'Avvio dell’aggiornamento…',
	'RADIOGLOBE_SYNC_RESUME'		=> 'Riprendi l’aggiornamento',
	'RADIOGLOBE_SYNC_CONTINUE'		=> 'Continua da qui',
	'RADIOGLOBE_SYNC_AUTO_REFRESH'	=> 'La pagina si ricarica automaticamente…',
	'RADIOGLOBE_SYNC_SERVERS'		=> 'Server',
	'RADIOGLOBE_SYNC_DONE'			=> 'Aggiornamento completato: %1$d stazioni in %2$d luoghi.',
	'RADIOGLOBE_SYNC_LAST_ERROR'	=> 'Ultimo errore',
	'RADIOGLOBE_SYNC_NOTE'			=> 'Le stazioni sparite da Radio Browser vengono disattivate, non cancellate subito: restano nei preferiti e conservano i commenti. Quelle senza preferiti né commenti vengono eliminate dopo 30 giorni.',
	'RADIOGLOBE_PHASE_DOWNLOAD'		=> 'Download dell’elenco: %1$d stazioni valide finora.',
	'RADIOGLOBE_PHASE_IMPORT'		=> 'Importazione nel database: %2$d di %1$d stazioni.',
	'RADIOGLOBE_PHASE_FINALIZE'		=> 'Creazione dei luoghi del globo…',
	'RADIOGLOBE_SOURCE_MANUAL'		=> 'avviato a mano',
	'RADIOGLOBE_SOURCE_CRON'		=> 'avviato dal cron',
	'RADIOGLOBE_NEVER'				=> 'Mai',
	'RADIOGLOBE_CRON_OFF'			=> 'Aggiornamento automatico disattivato',
	'RADIOGLOBE_STORE_NOT_WRITABLE'	=> 'La cartella store/radioglobe/ non esiste o non è scrivibile: serve per i file temporanei dell’aggiornamento.',

	// Gruppi
	'RADIOGLOBE_GROUPS_INTRO'		=> 'Scegli quali gruppi possono ascoltare la radio, aggiungere le stazioni ai preferiti (la loro playlist) e commentare. Le caselle scrivono nei normali permessi di phpBB, visibili anche in ACP &raquo; Permessi.',
	'RADIOGLOBE_GROUPS_ROLES_TITLE'	=> 'Gruppi con un ruolo',
	'RADIOGLOBE_GROUPS_ROLES_NOTE'	=> 'Se un gruppo usa un ruolo per i permessi utente (es. “Funzionalità standard”), la modifica viene applicata al ruolo per non cancellarne l’assegnazione: vale quindi per tutti i gruppi che usano lo stesso ruolo.',
	'RADIOGLOBE_CAN_LISTEN'			=> 'Ascoltare la radio',
	'RADIOGLOBE_CAN_FAVORITE'		=> 'Preferiti / playlist',
	'RADIOGLOBE_CAN_COMMENT'		=> 'Commentare',
	'RADIOGLOBE_CAN_ANNOUNCE'		=> 'Ascolto visibile agli altri',
	'RADIOGLOBE_USES_ROLE'			=> 'usa un ruolo',
	'RADIOGLOBE_GROUPS_UPDATED'		=> 'Permessi dei gruppi aggiornati.',
	'RADIOGLOBE_GROUPS_UNCHANGED'	=> 'Nessuna modifica da salvare.',
	'RADIOGLOBE_GROUPS_ROLES_TOUCHED'	=> 'Sono stati modificati anche questi ruoli, condivisi con altri gruppi: %s',

	// Commenti
	'RADIOGLOBE_COMMENTS_INTRO'		=> 'Ultimi commenti alle stazioni. Totale:',
	'RADIOGLOBE_COMMENT'			=> 'Commento',
	'RADIOGLOBE_STATION'			=> 'Stazione',
	'RADIOGLOBE_NO_COMMENTS'		=> 'Nessun commento.',
	'RADIOGLOBE_COMMENTS_DELETED'	=> 'Commenti eliminati: %d.',
	'RADIOGLOBE_COMMENTS_DELETE_CONFIRM'	=> 'Eliminare i commenti selezionati?',
]);
