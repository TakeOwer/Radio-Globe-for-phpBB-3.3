<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x. [Italiano]
 * Rapporto di verifica.
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
	'RADIOGLOBE_HC_INTRO'			=> 'Un controllo completo dell’estensione, una sezione alla volta: ambiente del server, file dell’estensione, cartella dei file scaricati, database, indirizzi, stazioni, città, collegamenti esterni e alcune prove reali. Non scrive nulla, quindi si può eseguire in qualsiasi momento anche con il forum attivo.',
	'RADIOGLOBE_HC_RUN'				=> 'Verifica adesso',
	'RADIOGLOBE_HC_COPY'			=> 'Copia il rapporto',
	'RADIOGLOBE_HC_COPIED'			=> 'Rapporto copiato negli appunti.',
	'RADIOGLOBE_HC_WAIT'			=> 'Verifica in corso…',
	'RADIOGLOBE_HC_CHECKING'		=> 'Controllo: %s…',
	'RADIOGLOBE_HC_READY'			=> 'Premi «Verifica adesso» per controllare l’estensione.',
	'RADIOGLOBE_HC_ALL_OK'			=> 'Tutto in ordine',
	'RADIOGLOBE_HC_SOME_WARN'		=> 'Funziona, con qualche avviso',
	'RADIOGLOBE_HC_SOME_ERROR'		=> 'Ci sono errori da correggere',
	'RADIOGLOBE_HC_SUMMARY'			=> '%1$d superati, %2$d avvisi, %3$d errori — rapporto generato in %4$s ms.',
	'RADIOGLOBE_HC_STATUS_OK'		=> 'OK',
	'RADIOGLOBE_HC_STATUS_WARN'		=> 'AVVISO',
	'RADIOGLOBE_HC_STATUS_ERROR'	=> 'ERRORE',
	'RADIOGLOBE_HC_FAILED'			=> 'La verifica si è interrotta',
	'RADIOGLOBE_HC_LEGEND'			=> 'OK = tutto a posto · AVVISO = funziona, ma conviene guardarci · ERRORE = qualcosa non funziona · il punto indica un’informazione.',

	'RADIOGLOBE_HC_SECTION_ENVIRONMENT'	=> 'Ambiente',
	'RADIOGLOBE_HC_SECTION_FILES'		=> 'File dell’estensione',
	'RADIOGLOBE_HC_SECTION_STORE'		=> 'Cartella dei file scaricati',
	'RADIOGLOBE_HC_SECTION_DATABASE'	=> 'Database',
	'RADIOGLOBE_HC_SECTION_ROUTES'		=> 'Indirizzi e integrazione con phpBB',
	'RADIOGLOBE_HC_SECTION_STATIONS'	=> 'Stazioni radio',
	'RADIOGLOBE_HC_SECTION_CITIES'		=> 'Città (GeoNames)',
	'RADIOGLOBE_HC_SECTION_NETWORK'		=> 'Connettività',
	'RADIOGLOBE_HC_SECTION_LIVE'		=> 'Prove reali',

	'RADIOGLOBE_HC_UNEXPECTED'		=> 'Errore imprevisto',
	'RADIOGLOBE_HC_YES'				=> 'Sì',
	'RADIOGLOBE_HC_NO'				=> 'No',
	'RADIOGLOBE_HC_NONE'			=> 'nessuno',
	'RADIOGLOBE_HC_NEVER'			=> 'mai',
	'RADIOGLOBE_HC_OFF'				=> 'disattivato',
	'RADIOGLOBE_HC_UNKNOWN'			=> 'sconosciuto',
	'RADIOGLOBE_HC_PRESENT'			=> 'presente',
	'RADIOGLOBE_HC_DAYS'			=> '%d giorni fa',
	'RADIOGLOBE_HC_MINUTES_AGO'		=> '%d minuti fa',
	'RADIOGLOBE_HC_HOURS_AGO'		=> '%d ore fa',
	'RADIOGLOBE_HC_DAYS_AGO'		=> '%d giorni fa',

	// Ambiente
	'RADIOGLOBE_HC_EXT_VERSION'		=> 'Versione dell’estensione',
	'RADIOGLOBE_HC_PHPBB_VERSION'	=> 'Versione di phpBB',
	'RADIOGLOBE_HC_PHP_VERSION'		=> 'Versione di PHP',
	'RADIOGLOBE_HC_CURL'			=> 'cURL / libreria TLS',
	'RADIOGLOBE_HC_CURL_STREAMS'	=> 'assente: si usano gli stream di PHP (titoli in onda non disponibili)',
	'RADIOGLOBE_HC_CURL_NONE'		=> 'assente, e allow_url_fopen è spento: nessun collegamento esterno possibile',
	'RADIOGLOBE_HC_MBSTRING_MISSING'	=> 'assente: phpBB usa un sostituto, funziona ma più lento',
	'RADIOGLOBE_HC_INTL_MISSING'	=> 'assente: per gli accenti si usa la tabella interna (normale)',
	'RADIOGLOBE_HC_ZLIB_MISSING'	=> 'assente: non si può estrarre l’elenco delle città da GeoNames',
	'RADIOGLOBE_HC_ZIP_MISSING'		=> 'assente: si usa il lettore ZIP interno (normale)',
	'RADIOGLOBE_HC_MEMORY'			=> 'Limite di memoria PHP',
	'RADIOGLOBE_HC_MEMORY_LOW'		=> 'basso: gli aggiornamenti possono fallire',
	'RADIOGLOBE_HC_MAX_TIME'		=> 'Tempo massimo di esecuzione',
	'RADIOGLOBE_HC_SERVER_TIME'		=> 'Ora del server',

	// File
	'RADIOGLOBE_HC_EXT_PATH'		=> 'Cartella dell’estensione',
	'RADIOGLOBE_HC_MANIFEST'		=> 'Elenco di controllo',
	'RADIOGLOBE_HC_MANIFEST_MISSING'	=> 'checksums.json mancante: impossibile controllare i file (ricarica il pacchetto completo)',
	'RADIOGLOBE_HC_MANIFEST_VERSION'	=> 'Versione dei file',
	'RADIOGLOBE_HC_MANIFEST_VERSION_DIFF'	=> 'file della versione %1$s, composer.json della %2$s: caricamento incompleto',
	'RADIOGLOBE_HC_FILES_PRESENT'	=> 'File presenti',
	'RADIOGLOBE_HC_FILES_ALL'		=> 'tutti i %1$d file (%2$s)',
	'RADIOGLOBE_HC_FILES_MISSING'	=> 'mancano %1$d file su %2$d',
	'RADIOGLOBE_HC_FILES_INTACT'	=> 'File integri',
	'RADIOGLOBE_HC_FILES_INTACT_ALL'	=> 'tutti identici all’originale',
	'RADIOGLOBE_HC_FILES_CHANGED'	=> '%d file diversi dall’originale (modificati o caricati male)',
	'RADIOGLOBE_HC_FILES_EXTRA'		=> 'File in più',

	// Cartella dei file scaricati
	'RADIOGLOBE_HC_STORE_PATH'		=> 'Percorso',
	'RADIOGLOBE_HC_STORE_EXISTS'	=> 'Cartella',
	'RADIOGLOBE_HC_STORE_NOT_YET'	=> 'non ancora creata: nasce con il primo aggiornamento delle stazioni',
	'RADIOGLOBE_HC_STORE_WRITABLE'	=> 'Scrivibile',
	'RADIOGLOBE_HC_STORE_READONLY'	=> 'no: gli aggiornamenti non possono salvare i file',
	'RADIOGLOBE_HC_STORE_PROTECTED'	=> 'Protetta dal web',
	'RADIOGLOBE_HC_STORE_HTACCESS'	=> 'sì (.htaccess)',
	'RADIOGLOBE_HC_STORE_OPEN'		=> '.htaccess mancante: i file si potrebbero scaricare dal browser',
	'RADIOGLOBE_HC_DISK_FREE'		=> 'Spazio libero su disco',
	'RADIOGLOBE_HC_STORE_FILES'		=> 'File',
	'RADIOGLOBE_HC_LEFTOVER'		=> 'avanzo di un aggiornamento interrotto: si cancella al prossimo',

	// Database
	'RADIOGLOBE_HC_TABLE'			=> 'Tabella',
	'RADIOGLOBE_HC_TABLE_MISSING'	=> 'mancante: disattiva e riattiva l’estensione',
	'RADIOGLOBE_HC_ROWS'			=> '%s righe',
	'RADIOGLOBE_HC_MIGRATIONS'		=> 'Migrazioni',
	'RADIOGLOBE_HC_MIGRATIONS_OK'	=> 'tutte eseguite (%d con impostazioni)',
	'RADIOGLOBE_HC_MIGRATIONS_MISSING'	=> 'non eseguite: %s — disattiva e riattiva l’estensione (senza eliminare i dati)',
	'RADIOGLOBE_HC_PERMISSIONS'		=> 'Permessi',
	'RADIOGLOBE_HC_PERMISSIONS_OK'	=> 'tutti presenti (%d)',
	'RADIOGLOBE_HC_PERMISSIONS_MISSING'	=> 'mancanti: %s',
	'RADIOGLOBE_HC_MODULES'			=> 'Schede dell’ACP',
	'RADIOGLOBE_HC_MODULES_OK'		=> 'tutte presenti (%d)',
	'RADIOGLOBE_HC_MODULES_MISSING'	=> 'mancanti: %s',
	'RADIOGLOBE_HC_ORPHANS'			=> 'Commenti e preferiti senza stazione',
	'RADIOGLOBE_HC_ORPHANS_FOUND'	=> '%d (restano nel database ma non si vedono)',

	// Indirizzi
	'RADIOGLOBE_HC_ROUTES'			=> 'Indirizzi dell’estensione',
	'RADIOGLOBE_HC_ROUTES_OK'		=> 'tutti trovati (%d)',
	'RADIOGLOBE_HC_ROUTES_BAD'		=> '%1$d su %2$d non trovati: svuota la cache di phpBB',
	'RADIOGLOBE_HC_PAGE_URL'		=> 'Pagina del globo',
	'RADIOGLOBE_HC_PLAYER_EVERYWHERE'	=> 'Player in tutto il forum',
	'RADIOGLOBE_HC_NAV_LINK'		=> 'Voce «Radio» nel menu',
	'RADIOGLOBE_HC_TOAST'			=> 'Avviso «sta ascoltando»',
	'RADIOGLOBE_HC_COMMENTS'		=> 'Commenti alle stazioni',

	// Stazioni
	'RADIOGLOBE_HC_SYNC_LAST'		=> 'Ultimo aggiornamento',
	'RADIOGLOBE_HC_SYNC_NEVER'		=> 'mai: avvialo dalla scheda Aggiornamento stazioni',
	'RADIOGLOBE_HC_STATIONS_ACTIVE'	=> 'Stazioni attive',
	'RADIOGLOBE_HC_PLACES'			=> 'Luoghi sul globo',
	'RADIOGLOBE_HC_MAX_STATIONS'	=> 'Numero massimo di stazioni',
	'RADIOGLOBE_HC_MAX_REACHED'		=> 'raggiunto: alcune stazioni restano fuori, conviene alzarlo',
	'RADIOGLOBE_HC_SYNC_RUNNING'	=> 'Aggiornamento in corso',
	'RADIOGLOBE_HC_SYNC_STALE'		=> 'fermo da più di un’ora: riprendilo o annullalo dalla scheda Aggiornamento stazioni',
	'RADIOGLOBE_HC_LAST_ERROR'		=> 'Ultimo errore',
	'RADIOGLOBE_HC_SYNC_CRON'		=> 'Aggiornamento automatico',
	'RADIOGLOBE_HC_SYNC_NEXT'		=> 'attivo, prossimo avvio %s',
	'RADIOGLOBE_HC_SYNC_CRON_LAST'	=> 'Ultimo avvio automatico',
	'RADIOGLOBE_HC_NOGEO'			=> 'Stazioni senza coordinate incluse',

	// Città
	'RADIOGLOBE_HC_CITIES_SOURCE'	=> 'Elenco in uso',
	'RADIOGLOBE_HC_CITIES_DOWNLOADED'	=> 'scaricato da GeoNames',
	'RADIOGLOBE_HC_CITIES_BUNDLED'	=> 'incluso nell’estensione',
	'RADIOGLOBE_HC_CITIES_COUNT'	=> 'Città nell’elenco',
	'RADIOGLOBE_HC_CITIES_DATE'		=> 'Data dei dati',
	'RADIOGLOBE_HC_CITIES_OLD'		=> 'più vecchio della soglia scelta: aggiornalo dalla scheda Città',
	'RADIOGLOBE_HC_CITIES_AUTO'		=> 'Aggiornamento automatico',
	'RADIOGLOBE_HC_CITIES_TEST'		=> 'Prova di riconoscimento',
	'RADIOGLOBE_HC_CITIES_TEST_OK'	=> '%d su 3 («NRJ Lyon» → Lione, «Radio Mitre Mendoza» → Mendoza, «Salem NH» → Salem nel New Hampshire)',
	'RADIOGLOBE_HC_CITIES_TEST_FAIL'	=> 'non riconosciute: %s',

	// Rete
	'RADIOGLOBE_HC_RB_SERVERS'		=> 'Server di Radio Browser',
	'RADIOGLOBE_HC_RB_STATIONS'		=> '%s stazioni nel catalogo',
	'RADIOGLOBE_HC_MODIFIED'		=> 'file del %s',
	'RADIOGLOBE_HC_ITUNES'			=> 'Copertine (iTunes)',
	'RADIOGLOBE_HC_ESRI'			=> 'Immagini satellitari (Esri)',

	// Prove reali
	'RADIOGLOBE_HC_LIVE_PLACES'		=> 'Luoghi del globo',
	'RADIOGLOBE_HC_LIVE_RESULTS'	=> '%1$s risultati in %2$s',
	'RADIOGLOBE_HC_LIVE_SEARCH'		=> 'Ricerca per nome',
	'RADIOGLOBE_HC_LIVE_NEAR'		=> 'Ricerca per coordinate (Roma)',
	'RADIOGLOBE_HC_LIVE_ICY'		=> 'Titolo in onda da uno stream',
	'RADIOGLOBE_HC_LIVE_ICY_EMPTY'	=> 'nessun titolo trasmesso in questo momento',
	'RADIOGLOBE_HC_LIVE_ICY_FAIL'	=> 'nessuna delle %d stazioni provate ha risposto',
	'RADIOGLOBE_HC_LIVE_UTF8'		=> 'Correzione dei testi (UTF-8)',
	'RADIOGLOBE_HC_LIVE_UTF8_OK'	=> 'funziona («Tamb\xe9m» → «Também», «DinÃ¡mica» → «Dinámica»)',
	'RADIOGLOBE_HC_LIVE_UTF8_FAIL'	=> 'non funziona: i titoli potrebbero mostrare caratteri strani',
]);
