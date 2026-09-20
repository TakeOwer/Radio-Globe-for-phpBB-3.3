<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x. [English]
 * Check-up report.
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
	'RADIOGLOBE_HC_INTRO'			=> 'A complete check of the extension, one section at a time: server environment, extension files, downloaded files folder, database, addresses, stations, cities, external connections and some real tests. It writes nothing, so it can be run at any time, even on a live board.',
	'RADIOGLOBE_HC_RUN'				=> 'Check now',
	'RADIOGLOBE_HC_COPY'			=> 'Copy the report',
	'RADIOGLOBE_HC_COPIED'			=> 'Report copied to the clipboard.',
	'RADIOGLOBE_HC_WAIT'			=> 'Checking…',
	'RADIOGLOBE_HC_CHECKING'		=> 'Checking: %s…',
	'RADIOGLOBE_HC_READY'			=> 'Press “Check now” to check the extension.',
	'RADIOGLOBE_HC_ALL_OK'			=> 'Everything is fine',
	'RADIOGLOBE_HC_SOME_WARN'		=> 'Working, with some warnings',
	'RADIOGLOBE_HC_SOME_ERROR'		=> 'There are errors to fix',
	'RADIOGLOBE_HC_SUMMARY'			=> '%1$d passed, %2$d warnings, %3$d errors — report generated in %4$s ms.',
	'RADIOGLOBE_HC_STATUS_OK'		=> 'OK',
	'RADIOGLOBE_HC_STATUS_WARN'		=> 'WARNING',
	'RADIOGLOBE_HC_STATUS_ERROR'	=> 'ERROR',
	'RADIOGLOBE_HC_FAILED'			=> 'The check stopped',
	'RADIOGLOBE_HC_LEGEND'			=> 'OK = all good · WARNING = working, but worth a look · ERROR = something does not work · the dot marks information.',

	'RADIOGLOBE_HC_SECTION_ENVIRONMENT'	=> 'Environment',
	'RADIOGLOBE_HC_SECTION_FILES'		=> 'Extension files',
	'RADIOGLOBE_HC_SECTION_STORE'		=> 'Downloaded files folder',
	'RADIOGLOBE_HC_SECTION_DATABASE'	=> 'Database',
	'RADIOGLOBE_HC_SECTION_ROUTES'		=> 'Addresses and phpBB integration',
	'RADIOGLOBE_HC_SECTION_STATIONS'	=> 'Radio stations',
	'RADIOGLOBE_HC_SECTION_CITIES'		=> 'Cities (GeoNames)',
	'RADIOGLOBE_HC_SECTION_NETWORK'		=> 'Connectivity',
	'RADIOGLOBE_HC_SECTION_LIVE'		=> 'Real tests',

	'RADIOGLOBE_HC_UNEXPECTED'		=> 'Unexpected error',
	'RADIOGLOBE_HC_YES'				=> 'Yes',
	'RADIOGLOBE_HC_NO'				=> 'No',
	'RADIOGLOBE_HC_NONE'			=> 'none',
	'RADIOGLOBE_HC_NEVER'			=> 'never',
	'RADIOGLOBE_HC_OFF'				=> 'off',
	'RADIOGLOBE_HC_UNKNOWN'			=> 'unknown',
	'RADIOGLOBE_HC_PRESENT'			=> 'present',
	'RADIOGLOBE_HC_DAYS'			=> '%d days ago',
	'RADIOGLOBE_HC_MINUTES_AGO'		=> '%d minutes ago',
	'RADIOGLOBE_HC_HOURS_AGO'		=> '%d hours ago',
	'RADIOGLOBE_HC_DAYS_AGO'		=> '%d days ago',

	'RADIOGLOBE_HC_EXT_VERSION'		=> 'Extension version',
	'RADIOGLOBE_HC_PHPBB_VERSION'	=> 'phpBB version',
	'RADIOGLOBE_HC_PHP_VERSION'		=> 'PHP version',
	'RADIOGLOBE_HC_CURL'			=> 'cURL / TLS library',
	'RADIOGLOBE_HC_CURL_STREAMS'	=> 'missing: PHP streams are used (now-playing titles not available)',
	'RADIOGLOBE_HC_CURL_NONE'		=> 'missing, and allow_url_fopen is off: no external connection possible',
	'RADIOGLOBE_HC_MBSTRING_MISSING'	=> 'missing: phpBB uses a replacement, it works but slower',
	'RADIOGLOBE_HC_INTL_MISSING'	=> 'missing: the built-in table is used for accents (normal)',
	'RADIOGLOBE_HC_ZLIB_MISSING'	=> 'missing: the city list from GeoNames cannot be extracted',
	'RADIOGLOBE_HC_ZIP_MISSING'		=> 'missing: the built-in ZIP reader is used (normal)',
	'RADIOGLOBE_HC_MEMORY'			=> 'PHP memory limit',
	'RADIOGLOBE_HC_MEMORY_LOW'		=> 'low: updates may fail',
	'RADIOGLOBE_HC_MAX_TIME'		=> 'Maximum execution time',
	'RADIOGLOBE_HC_SERVER_TIME'		=> 'Server time',

	'RADIOGLOBE_HC_EXT_PATH'		=> 'Extension folder',
	'RADIOGLOBE_HC_MANIFEST'		=> 'Checklist',
	'RADIOGLOBE_HC_MANIFEST_MISSING'	=> 'checksums.json missing: the files cannot be checked (upload the complete package again)',
	'RADIOGLOBE_HC_MANIFEST_VERSION'	=> 'Files version',
	'RADIOGLOBE_HC_MANIFEST_VERSION_DIFF'	=> 'files of version %1$s, composer.json of %2$s: incomplete upload',
	'RADIOGLOBE_HC_FILES_PRESENT'	=> 'Files present',
	'RADIOGLOBE_HC_FILES_ALL'		=> 'all %1$d files (%2$s)',
	'RADIOGLOBE_HC_FILES_MISSING'	=> '%1$d of %2$d files missing',
	'RADIOGLOBE_HC_FILES_INTACT'	=> 'Files intact',
	'RADIOGLOBE_HC_FILES_INTACT_ALL'	=> 'all identical to the original',
	'RADIOGLOBE_HC_FILES_CHANGED'	=> '%d files differ from the original (edited or uploaded badly)',
	'RADIOGLOBE_HC_FILES_EXTRA'		=> 'Extra files',

	'RADIOGLOBE_HC_STORE_PATH'		=> 'Path',
	'RADIOGLOBE_HC_STORE_EXISTS'	=> 'Folder',
	'RADIOGLOBE_HC_STORE_NOT_YET'	=> 'not created yet: it appears with the first station update',
	'RADIOGLOBE_HC_STORE_WRITABLE'	=> 'Writable',
	'RADIOGLOBE_HC_STORE_READONLY'	=> 'no: updates cannot save their files',
	'RADIOGLOBE_HC_STORE_PROTECTED'	=> 'Protected from the web',
	'RADIOGLOBE_HC_STORE_HTACCESS'	=> 'yes (.htaccess)',
	'RADIOGLOBE_HC_STORE_OPEN'		=> '.htaccess missing: the files might be downloadable from a browser',
	'RADIOGLOBE_HC_DISK_FREE'		=> 'Free disk space',
	'RADIOGLOBE_HC_STORE_FILES'		=> 'Files',
	'RADIOGLOBE_HC_LEFTOVER'		=> 'left over from an interrupted update: removed by the next one',

	'RADIOGLOBE_HC_TABLE'			=> 'Table',
	'RADIOGLOBE_HC_TABLE_MISSING'	=> 'missing: disable and enable the extension',
	'RADIOGLOBE_HC_ROWS'			=> '%s rows',
	'RADIOGLOBE_HC_MIGRATIONS'		=> 'Migrations',
	'RADIOGLOBE_HC_MIGRATIONS_OK'	=> 'all run (%d with settings)',
	'RADIOGLOBE_HC_MIGRATIONS_MISSING'	=> 'not run: %s — disable and enable the extension (without deleting data)',
	'RADIOGLOBE_HC_PERMISSIONS'		=> 'Permissions',
	'RADIOGLOBE_HC_PERMISSIONS_OK'	=> 'all present (%d)',
	'RADIOGLOBE_HC_PERMISSIONS_MISSING'	=> 'missing: %s',
	'RADIOGLOBE_HC_MODULES'			=> 'ACP tabs',
	'RADIOGLOBE_HC_MODULES_OK'		=> 'all present (%d)',
	'RADIOGLOBE_HC_MODULES_MISSING'	=> 'missing: %s',
	'RADIOGLOBE_HC_ORPHANS'			=> 'Comments and favourites without station',
	'RADIOGLOBE_HC_ORPHANS_FOUND'	=> '%d (kept in the database but not shown)',

	'RADIOGLOBE_HC_ROUTES'			=> 'Extension addresses',
	'RADIOGLOBE_HC_ROUTES_OK'		=> 'all found (%d)',
	'RADIOGLOBE_HC_ROUTES_BAD'		=> '%1$d of %2$d not found: purge the phpBB cache',
	'RADIOGLOBE_HC_PAGE_URL'		=> 'Globe page',
	'RADIOGLOBE_HC_PLAYER_EVERYWHERE'	=> 'Player on the whole board',
	'RADIOGLOBE_HC_NAV_LINK'		=> '“Radio” menu link',
	'RADIOGLOBE_HC_TOAST'			=> '“Is listening” notice',
	'RADIOGLOBE_HC_COMMENTS'		=> 'Station comments',

	'RADIOGLOBE_HC_SYNC_LAST'		=> 'Last update',
	'RADIOGLOBE_HC_SYNC_NEVER'		=> 'never: start it from the Station update tab',
	'RADIOGLOBE_HC_STATIONS_ACTIVE'	=> 'Active stations',
	'RADIOGLOBE_HC_PLACES'			=> 'Places on the globe',
	'RADIOGLOBE_HC_MAX_STATIONS'	=> 'Maximum number of stations',
	'RADIOGLOBE_HC_MAX_REACHED'		=> 'reached: some stations are left out, consider raising it',
	'RADIOGLOBE_HC_SYNC_RUNNING'	=> 'Update in progress',
	'RADIOGLOBE_HC_SYNC_STALE'		=> 'stuck for more than an hour: resume or cancel it from the Station update tab',
	'RADIOGLOBE_HC_LAST_ERROR'		=> 'Last error',
	'RADIOGLOBE_HC_SYNC_CRON'		=> 'Automatic update',
	'RADIOGLOBE_HC_SYNC_NEXT'		=> 'on, next start %s',
	'RADIOGLOBE_HC_SYNC_CRON_LAST'	=> 'Last automatic start',
	'RADIOGLOBE_HC_NOGEO'			=> 'Stations without coordinates included',

	'RADIOGLOBE_HC_CITIES_SOURCE'	=> 'List in use',
	'RADIOGLOBE_HC_CITIES_DOWNLOADED'	=> 'downloaded from GeoNames',
	'RADIOGLOBE_HC_CITIES_BUNDLED'	=> 'included in the extension',
	'RADIOGLOBE_HC_CITIES_COUNT'	=> 'Cities in the list',
	'RADIOGLOBE_HC_CITIES_DATE'		=> 'Data date',
	'RADIOGLOBE_HC_CITIES_OLD'		=> 'older than the chosen age: update it from the Cities tab',
	'RADIOGLOBE_HC_CITIES_AUTO'		=> 'Automatic update',
	'RADIOGLOBE_HC_CITIES_TEST'		=> 'Recognition test',
	'RADIOGLOBE_HC_CITIES_TEST_OK'	=> '%d of 3 (“NRJ Lyon” → Lyon, “Radio Mitre Mendoza” → Mendoza, “Salem NH” → Salem in New Hampshire)',
	'RADIOGLOBE_HC_CITIES_TEST_FAIL'	=> 'not recognised: %s',

	'RADIOGLOBE_HC_RB_SERVERS'		=> 'Radio Browser servers',
	'RADIOGLOBE_HC_RB_STATIONS'		=> '%s stations in the catalogue',
	'RADIOGLOBE_HC_MODIFIED'		=> 'file of %s',
	'RADIOGLOBE_HC_ITUNES'			=> 'Covers (iTunes)',
	'RADIOGLOBE_HC_ESRI'			=> 'Satellite imagery (Esri)',

	'RADIOGLOBE_HC_LIVE_PLACES'		=> 'Globe places',
	'RADIOGLOBE_HC_LIVE_RESULTS'	=> '%1$s results in %2$s',
	'RADIOGLOBE_HC_LIVE_SEARCH'		=> 'Search by name',
	'RADIOGLOBE_HC_LIVE_NEAR'		=> 'Search by coordinates (Rome)',
	'RADIOGLOBE_HC_LIVE_ICY'		=> 'Now-playing title from a stream',
	'RADIOGLOBE_HC_LIVE_ICY_EMPTY'	=> 'no title sent at the moment',
	'RADIOGLOBE_HC_LIVE_ICY_FAIL'	=> 'none of the %d stations tried answered',
	'RADIOGLOBE_HC_LIVE_UTF8'		=> 'Text repair (UTF-8)',
	'RADIOGLOBE_HC_LIVE_UTF8_OK'	=> 'working (“Tamb\xe9m” → “Também”, “DinÃ¡mica” → “Dinámica”)',
	'RADIOGLOBE_HC_LIVE_UTF8_FAIL'	=> 'not working: titles may show strange characters',
]);
