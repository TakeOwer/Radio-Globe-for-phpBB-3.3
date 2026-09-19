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
	'ACP_RADIOGLOBE_SETTINGS'		=> 'Settings',
	'ACP_RADIOGLOBE_GROUPS'			=> 'Authorised groups',
	'ACP_RADIOGLOBE_SYNC'			=> 'Station update',
	'ACP_RADIOGLOBE_COMMENTS'		=> 'Comments',

	'LOG_RADIOGLOBE_SYNC_STARTED'	=> '<strong>Radio Globe:</strong> manual station update started',

	'RADIOGLOBE_SECONDS'			=> 'seconds',
	'RADIOGLOBE_NO_CURL'			=> 'The PHP cURL extension is not enabled: downloading still works, but now-playing titles and covers will not be available.',

	'RADIOGLOBE_SETTINGS_INTRO'		=> 'Stations are downloaded from Radio Browser (a free, open source service) and stored in the board database: visitors never query the external service.',
	'RADIOGLOBE_SETTINGS_SAVED'		=> 'Settings saved.',
	'RADIOGLOBE_FILTERS_CHANGED'	=> 'You changed the station filters: they apply from the next update. %sUpdate now%s',

	'RADIOGLOBE_DEDICATION'			=> '“To my wife, the most important woman in my life”',
	'RADIOGLOBE_SET_SOURCE'			=> 'Stations and filters',
	'RADIOGLOBE_SOURCE_NOTE'		=> 'Stations that Radio Browser verified as working are imported: those with geographic coordinates and, when the option below is enabled, those without, placed in their region or country. Filters apply from the next update.',
	'RADIOGLOBE_HTTPS_ONLY'			=> 'HTTPS streams only',
	'RADIOGLOBE_HTTPS_ONLY_EXPLAIN'	=> 'Recommended if the board uses HTTPS: browsers block HTTP audio on secure pages (mixed content). If disabled, HTTP stations are listed but marked as not playable.',
	'RADIOGLOBE_EXCLUDE_HLS'		=> 'Exclude HLS streams',
	'RADIOGLOBE_EXCLUDE_HLS_EXPLAIN'	=> 'HLS streams (.m3u8) do not play natively in every browser.',
	'RADIOGLOBE_NOGEO'			=> 'Include stations without coordinates',
	'RADIOGLOBE_NOGEO_EXPLAIN'	=> 'About four in five Radio Browser stations have no coordinates. When enabled they are placed on the globe in their region (where the stations with coordinates of the same region are) or, when the region is missing, in their country. They appear as “Name (region)” and “Country (whole country)”. Applies from the next update; raising the maximum number of stations to 40,000 is recommended.',
	'RADIOGLOBE_TAGS'				=> 'Genres to include',
	'RADIOGLOBE_TAGS_EXPLAIN'		=> 'One per line or comma separated (e.g. rock, jazz, news). A station is included if at least one of its tags contains one of the words. Leave empty to include every genre.',
	'RADIOGLOBE_MIN_BITRATE'		=> 'Minimum bitrate',
	'RADIOGLOBE_MIN_BITRATE_EXPLAIN'	=> '0 = no limit.',
	'RADIOGLOBE_MAX_STATIONS'		=> 'Maximum number of stations',
	'RADIOGLOBE_MAX_STATIONS_EXPLAIN'	=> 'The most listened stations are imported first. On shared hosting stay below 30,000.',
	'RADIOGLOBE_CLUSTER_GRID'		=> 'Place grouping',
	'RADIOGLOBE_CLUSTER_GRID_EXPLAIN'	=> 'Size of the area that becomes a single dot on the globe, in hundredths of a degree: 25 = about 25 km (a city), 100 = about 100 km.',
	'RADIOGLOBE_API_SERVER'			=> 'Radio Browser server',
	'RADIOGLOBE_API_SERVER_EXPLAIN'	=> 'Leave empty to pick automatically among the available servers (recommended).',
	'RADIOGLOBE_API_SERVER_INVALID'	=> 'The server name is not valid.',

	'RADIOGLOBE_SET_CRON'			=> 'Automatic update',
	'RADIOGLOBE_CRON_ENABLED'		=> 'Automatic update enabled',
	'RADIOGLOBE_CRON_ENABLED_EXPLAIN'	=> 'Uses phpBB scheduled tasks, triggered by board visits: at the chosen hour, or on the first visit after it, the update starts and proceeds in small steps.',
	'RADIOGLOBE_CRON_HOURS'			=> 'Start hours',
	'RADIOGLOBE_CRON_HOURS_EXPLAIN'	=> 'Select one or more hours. Board timezone:',
	'RADIOGLOBE_CRON_HOURS_EMPTY'	=> 'Select at least one start hour, or disable the automatic update.',
	'RADIOGLOBE_CRON_PRESETS'		=> 'Quick selection',
	'RADIOGLOBE_CRON_PRESETS_EXPLAIN'	=> 'Starts from the first hour already selected (04:00 if none).',
	'RADIOGLOBE_EVERY_24'			=> 'Every 24 hours',
	'RADIOGLOBE_EVERY_12'			=> 'Every 12 hours',
	'RADIOGLOBE_EVERY_6'			=> 'Every 6 hours',

	'RADIOGLOBE_SET_PLAYER'			=> 'Player and globe',
	'RADIOGLOBE_PLAYER_OPACITY'		=> 'Player transparency',
	'RADIOGLOBE_PLAYER_OPACITY_EXPLAIN'	=> 'Background opacity of the player bar: 100% is solid black, lower values let the page show through (with a light blur). Move the slider and watch the preview.',
	'RADIOGLOBE_PREVIEW'			=> 'Live preview',
	'RADIOGLOBE_PREVIEW_NOTE'		=> 'Sample preview: the page content scrolls under the bar.',
	'RADIOGLOBE_PLAYER_EVERYWHERE'	=> 'Player across the board',
	'RADIOGLOBE_PLAYER_EVERYWHERE_EXPLAIN'	=> 'The player bar stays visible while browsing the board and resumes the station on every page change. If disabled it only appears on the globe page.',
	'RADIOGLOBE_NAV_LINK'			=> '“Radio” menu link',
	'RADIOGLOBE_NOWPLAYING'			=> 'Show the now-playing title',
	'RADIOGLOBE_NOWPLAYING_EXPLAIN'	=> 'The board periodically reads the stream ICY metadata (“Artist - Title”). Not every station sends it. The result is cached for 25 seconds per station.',
	'RADIOGLOBE_COVERS'				=> 'Look up covers',
	'RADIOGLOBE_COVERS_EXPLAIN'		=> 'Streams carry no artwork: covers are looked up on the iTunes Search API from the now-playing title and cached for 7 days. The station logo is used when nothing is found.',
	'RADIOGLOBE_TEXTURE'			=> 'Globe look',
	'RADIOGLOBE_TEXTURE_DARK'		=> 'Dark (light, recommended)',
	'RADIOGLOBE_TEXTURE_NIGHT'		=> 'Earth at night',
	'RADIOGLOBE_TEXTURE_MARBLE'		=> 'Blue Marble (heavier)',
	'RADIOGLOBE_TEXTURE_SATELLITE'	=> 'Detailed satellite (Radio Garden style)',
	'RADIOGLOBE_TEXTURE_EXPLAIN'	=> 'With “Detailed satellite” the globe loads Esri World Imagery tiles, sharper and sharper as you zoom in. The Esri attribution is shown at the bottom of the globe.',
	'RADIOGLOBE_MARKERS'			=> 'Marker style',
	'RADIOGLOBE_MARKERS_EXPLAIN'	=> 'Fixed-size dots stay small at every zoom level and spread apart as you zoom in, like Radio Garden. Classic markers have a fixed size on the ground and overlap when zoomed in.',
	'RADIOGLOBE_MARKERS_DOTS'		=> 'Fixed-size dots (Radio Garden style, recommended)',
	'RADIOGLOBE_MARKERS_CLASSIC'	=> 'Classic 3D markers',
	'RADIOGLOBE_DOT_COLOR'          	=> 'Dot colour',
	'RADIOGLOBE_DOT_COLOR_EXPLAIN'  	=> 'Colour of the places with stations on the globe. The atmosphere glow and the ring around the playing station use it too.',
	'RADIOGLOBE_DOT_MODE'           	=> 'Dot colouring',
	'RADIOGLOBE_DOT_MODE_EXPLAIN'   	=> '“Heat map” and “One colour per country” ignore the chosen dot colour (it is still used for the atmosphere and the ring).',
	'RADIOGLOBE_DOT_MODE_SHADES'    	=> 'Shades of the chosen colour (lighter where there are more stations)',
	'RADIOGLOBE_DOT_MODE_SINGLE'    	=> 'Solid colour',
	'RADIOGLOBE_DOT_MODE_HEAT'      	=> 'Heat map (blue → red by number of stations)',
	'RADIOGLOBE_DOT_MODE_COUNTRY'   	=> 'One colour per country',
	'RADIOGLOBE_DOT_PREVIEW'        	=> 'Preview',
	'RADIOGLOBE_DOT_RESET'          	=> 'Reset to green',
	'RADIOGLOBE_AUTOROTATE'			=> 'Globe auto-rotation',

	'RADIOGLOBE_SET_TOAST'			=> '“Is listening” notice',
	'RADIOGLOBE_TOAST_ENABLED'		=> 'Show who is listening',
	'RADIOGLOBE_TOAST_ENABLED_EXPLAIN'	=> 'When a user starts a station, “User is listening to: station” appears for a few seconds at the top right of every board page, with the now-playing title when available. The name uses the group colour. Clicking the notice plays the same station. It is shown to users who can listen to the radio; only users in groups with “Listening visible to others” (Authorised groups page) are announced.',
	'RADIOGLOBE_TOAST_SECONDS'		=> 'Notice duration',
	'RADIOGLOBE_TOAST_SECONDS_EXPLAIN'	=> 'From 2 to 30 seconds. The notice stays open while the mouse is over it.',

	'RADIOGLOBE_SET_COMMENTS'		=> 'Station comments',
	'RADIOGLOBE_COMMENTS_ENABLED'	=> 'Comments enabled',
	'RADIOGLOBE_COMMENT_MAXLEN'		=> 'Maximum comment length',
	'RADIOGLOBE_COMMENTS_PER_PAGE'	=> 'Comments loaded at a time',
	'RADIOGLOBE_COMMENT_FLOOD'		=> 'Minimum interval between comments',
	'RADIOGLOBE_COMMENT_FLOOD_EXPLAIN'	=> 'Does not apply to moderators. 0 = no limit.',

	'RADIOGLOBE_SYNC_INTRO'			=> 'Update the station list right now. The bar shows the progress step by step: keep the page open until it reaches 100%.',
	'RADIOGLOBE_SYNC_STATUS'		=> 'Status',
	'RADIOGLOBE_SYNC_LAST'			=> 'Last completed update',
	'RADIOGLOBE_SYNC_COUNT'			=> 'Active stations',
	'RADIOGLOBE_SYNC_PLACES'		=> 'Places on the globe',
	'RADIOGLOBE_SYNC_DURATION'		=> 'Duration',
	'RADIOGLOBE_SYNC_HOURS'			=> 'Automatic start hours',
	'RADIOGLOBE_SYNC_NEXT'			=> 'Next automatic start',
	'RADIOGLOBE_SYNC_START'			=> 'Update stations now',
	'RADIOGLOBE_SYNC_CANCEL'		=> 'Cancel the update',
	'RADIOGLOBE_SYNC_CANCELLED'		=> 'Update cancelled.',
	'RADIOGLOBE_SYNC_RUNNING'		=> 'Update in progress',
	'RADIOGLOBE_SYNC_SKIPPED'		=> '(%d invalid stations skipped)',
	'RADIOGLOBE_BADGE_VERSION'		=> 'version',
	'RADIOGLOBE_BADGE_LICENSE'		=> 'license',
	'RADIOGLOBE_SYNC_PROGRESS'		=> 'Progress',
	'RADIOGLOBE_SYNC_WAIT'			=> 'Starting the update…',
	'RADIOGLOBE_SYNC_RESUME'		=> 'Resume the update',
	'RADIOGLOBE_SYNC_CONTINUE'		=> 'Continue from here',
	'RADIOGLOBE_SYNC_AUTO_REFRESH'	=> 'The page reloads automatically…',
	'RADIOGLOBE_SYNC_SERVERS'		=> 'Servers',
	'RADIOGLOBE_SYNC_DONE'			=> 'Update completed: %1$d stations in %2$d places.',
	'RADIOGLOBE_SYNC_LAST_ERROR'	=> 'Last error',
	'RADIOGLOBE_SYNC_NOTE'			=> 'Stations removed from Radio Browser are deactivated, not deleted right away: they stay in favourites and keep their comments. Those without favourites or comments are deleted after 30 days.',
	'RADIOGLOBE_PHASE_DOWNLOAD'		=> 'Downloading the list: %1$d valid stations so far.',
	'RADIOGLOBE_PHASE_IMPORT'		=> 'Importing into the database: %2$d of %1$d stations.',
	'RADIOGLOBE_PHASE_FINALIZE'		=> 'Building the globe places…',
	'RADIOGLOBE_SOURCE_MANUAL'		=> 'started manually',
	'RADIOGLOBE_SOURCE_CRON'		=> 'started by cron',
	'RADIOGLOBE_NEVER'				=> 'Never',
	'RADIOGLOBE_CRON_OFF'			=> 'Automatic update disabled',
	'RADIOGLOBE_STORE_NOT_WRITABLE'	=> 'The store/radioglobe/ folder does not exist or is not writable: it holds the temporary update files.',

	'RADIOGLOBE_GROUPS_INTRO'		=> 'Choose which groups can listen to the radio, add stations to their favourites (their playlist) and comment. The checkboxes write to the regular phpBB permissions, also visible in ACP &raquo; Permissions.',
	'RADIOGLOBE_GROUPS_ROLES_TITLE'	=> 'Groups with a role',
	'RADIOGLOBE_GROUPS_ROLES_NOTE'	=> 'If a group uses a role for user permissions (e.g. “Standard Features”), the change is applied to the role so its assignment is kept: it therefore applies to every group using the same role.',
	'RADIOGLOBE_CAN_LISTEN'			=> 'Listen to the radio',
	'RADIOGLOBE_CAN_FAVORITE'		=> 'Favourites / playlist',
	'RADIOGLOBE_CAN_COMMENT'		=> 'Comment',
	'RADIOGLOBE_CAN_ANNOUNCE'		=> 'Listening visible to others',
	'RADIOGLOBE_USES_ROLE'			=> 'uses a role',
	'RADIOGLOBE_GROUPS_UPDATED'		=> 'Group permissions updated.',
	'RADIOGLOBE_GROUPS_UNCHANGED'	=> 'Nothing to save.',
	'RADIOGLOBE_GROUPS_ROLES_TOUCHED'	=> 'These roles, shared with other groups, were changed as well: %s',

	'RADIOGLOBE_COMMENTS_INTRO'		=> 'Latest station comments. Total:',
	'RADIOGLOBE_COMMENT'			=> 'Comment',
	'RADIOGLOBE_STATION'			=> 'Station',
	'RADIOGLOBE_NO_COMMENTS'		=> 'No comments.',
	'RADIOGLOBE_COMMENTS_DELETED'	=> 'Comments deleted: %d.',
	'RADIOGLOBE_COMMENTS_DELETE_CONFIRM'	=> 'Delete the selected comments?',
]);
