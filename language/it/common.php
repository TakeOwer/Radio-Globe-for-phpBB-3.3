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
	'RADIOGLOBE_NAV'				=> 'Radio',
	'RADIOGLOBE_NAV_TITLE'			=> 'Radio dal mondo',
	'RADIOGLOBE_PAGE_TITLE'			=> 'Radio dal mondo',

	'RADIOGLOBE_NO_PERMISSION'		=> 'Non hai il permesso di ascoltare la radio.',
	'RADIOGLOBE_NO_FAV_PERMISSION'	=> 'Non hai il permesso di aggiungere stazioni ai preferiti.',
	'RADIOGLOBE_NO_COMMENT_PERMISSION'	=> 'Non hai il permesso di commentare le stazioni.',
	'RADIOGLOBE_PLACE_NOT_FOUND'	=> 'Luogo non trovato.',
	'RADIOGLOBE_STATION_NOT_FOUND'	=> 'Stazione non trovata.',
	'RADIOGLOBE_COMMENT_NOT_FOUND'	=> 'Commento non trovato.',
	'RADIOGLOBE_ERROR'				=> 'Si è verificato un errore. Riprova.',

	// Pagina del globo
	'RADIOGLOBE_LOADING_GLOBE'		=> 'Caricamento del globo…',
	'RADIOGLOBE_WEBGL_MISSING'		=> 'Il tuo browser non supporta WebGL: il globo non può essere mostrato. Puoi comunque cercare le stazioni qui a destra.',
	'RADIOGLOBE_SEARCH_PLACEHOLDER'	=> 'Cerca stazioni, generi, paesi o coordinate…',
	'RADIOGLOBE_EXPLORE'			=> 'Esplora',
	'RADIOGLOBE_WELCOME_TITLE'		=> 'Gira il mondo',
	'RADIOGLOBE_WELCOME_TEXT'		=> 'Trascina il globo e fermati su un punto verde, oppure cliccalo: qui compaiono le radio di quel luogo. Ogni punto raggruppa le stazioni di una città o di una zona.',
	'RADIOGLOBE_EMPTY_TITLE'		=> 'Nessuna stazione, per ora',
	'RADIOGLOBE_EMPTY_TEXT'			=> 'L’elenco delle radio non è ancora stato scaricato. Verrà aggiornato automaticamente all’ora prevista, oppure un amministratore può avviarlo subito dal pannello di controllo.',
	'RADIOGLOBE_STATIONS'			=> 'stazioni',
	'RADIOGLOBE_PLACES'				=> 'luoghi',
	'RADIOGLOBE_NO_STATIONS'		=> 'Nessuna stazione trovata.',
	'RADIOGLOBE_SEARCH_RESULTS'		=> 'Risultati per “%s”',
	'RADIOGLOBE_NEAR_COORDS'		=> 'Stazioni vicine a %s',
	'RADIOGLOBE_PLACE_REGION'		=> '(regione)',
	'RADIOGLOBE_PLACE_COUNTRY'	=> '(tutto il paese)',
	'RADIOGLOBE_SEARCHING'			=> 'Ricerca in corso…',
	'RADIOGLOBE_DATA_SOURCE'		=> 'Dati: Radio Browser',
	'RADIOGLOBE_IMAGERY_SOURCE'	=> 'Immagini: Esri, Maxar, Earthstar Geographics',

	// Player
	'RADIOGLOBE_PLAY'				=> 'Riproduci',
	'RADIOGLOBE_PAUSE'				=> 'Pausa',
	'RADIOGLOBE_PREV'				=> 'Stazione precedente',
	'RADIOGLOBE_NEXT'				=> 'Stazione successiva',
	'RADIOGLOBE_SHUFFLE'			=> 'Ordine casuale',
	'RADIOGLOBE_REPEAT'				=> 'Riconnessione automatica',
	'RADIOGLOBE_LIVE'				=> 'DIRETTA',
	'RADIOGLOBE_QUEUE'				=> 'Coda',
	'RADIOGLOBE_NOW_LISTENING'		=> 'Stai ascoltando',
	'RADIOGLOBE_NEXT_FROM'			=> 'Prossima stazione da: %s',
	'RADIOGLOBE_QUEUE_EMPTY'		=> 'Nessuna stazione in ascolto.',
	'RADIOGLOBE_FAVORITES'			=> 'Preferiti',
	'RADIOGLOBE_FAVORITES_EMPTY'	=> 'Non hai ancora stazioni preferite. Usa il pulsante + mentre ascolti.',
	'RADIOGLOBE_FAV_ADD'			=> 'Aggiungi ai preferiti',
	'RADIOGLOBE_FAV_REMOVE'			=> 'Rimuovi dai preferiti',
	'RADIOGLOBE_FAV_ADDED'			=> 'Aggiunta ai preferiti',
	'RADIOGLOBE_FAV_REMOVED'		=> 'Rimossa dai preferiti',
	'RADIOGLOBE_LOGIN_TO_FAV'		=> 'Accedi per salvare le stazioni preferite.',
	'RADIOGLOBE_HISTORY'			=> 'Cronologia',
	'RADIOGLOBE_HISTORY_EMPTY'		=> 'Le stazioni che ascolti compariranno qui.',
	'RADIOGLOBE_VOLUME'				=> 'Volume',
	'RADIOGLOBE_MUTE'				=> 'Disattiva audio',
	'RADIOGLOBE_MINI_PLAYER'		=> 'Apri mini player',
	'RADIOGLOBE_FULLSCREEN'			=> 'Schermo intero',
	'RADIOGLOBE_OPEN_GLOBE'			=> 'Mostra sul globo',
	'RADIOGLOBE_CONNECTING'			=> 'Connessione…',
	'RADIOGLOBE_STREAM_ERROR'		=> 'Questa stazione non risponde. Prova con un’altra.',
	'RADIOGLOBE_MIXED_CONTENT'		=> 'Questa stazione trasmette solo in HTTP e il browser la blocca su una pagina sicura.',
	'RADIOGLOBE_RESUME'				=> 'Clicca per riprendere l’ascolto',
	'RADIOGLOBE_WEBSITE'			=> 'Sito della stazione',
	'RADIOGLOBE_CLOSE'				=> 'Chiudi',
	'RADIOGLOBE_TOAST_LISTENING'	=> '%s sta ascoltando:',
	'RADIOGLOBE_TOAST_PLAY'		=> 'Clicca per ascoltare anche tu',
	'RADIOGLOBE_CLOSE_PLAYER'		=> 'Chiudi il player',
	'RADIOGLOBE_INACTIVE'			=> 'Stazione non più disponibile',

	// Commenti
	'RADIOGLOBE_COMMENTS'			=> 'Commenti',
	'RADIOGLOBE_COMMENTS_EMPTY'		=> 'Nessun commento. Scrivi il primo!',
	'RADIOGLOBE_COMMENTS_OFF'		=> 'I commenti sono disattivati.',
	'RADIOGLOBE_COMMENT_PLACEHOLDER'	=> 'Cosa ne pensi di questa radio?',
	'RADIOGLOBE_COMMENT_SEND'		=> 'Pubblica',
	'RADIOGLOBE_COMMENT_LOGIN'		=> 'Accedi con un account autorizzato per commentare.',
	'RADIOGLOBE_COMMENT_DELETE'		=> 'Elimina commento',
	'RADIOGLOBE_COMMENT_DELETE_ASK'	=> 'Eliminare questo commento?',
	'RADIOGLOBE_COMMENT_EMPTY'		=> 'Il commento è vuoto.',
	'RADIOGLOBE_COMMENT_TOO_LONG'	=> 'Il commento supera il limite di %d caratteri.',
	'RADIOGLOBE_COMMENT_FLOOD'		=> 'Hai appena commentato: attendi qualche secondo.',
	'RADIOGLOBE_LOAD_MORE'			=> 'Mostra altri',
]);
