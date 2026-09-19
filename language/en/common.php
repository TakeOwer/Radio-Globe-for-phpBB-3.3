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
	'RADIOGLOBE_NAV_TITLE'			=> 'World radio',
	'RADIOGLOBE_PAGE_TITLE'			=> 'World radio',

	'RADIOGLOBE_NO_PERMISSION'		=> 'You are not allowed to listen to the radio.',
	'RADIOGLOBE_NO_FAV_PERMISSION'	=> 'You are not allowed to add stations to your favourites.',
	'RADIOGLOBE_NO_COMMENT_PERMISSION'	=> 'You are not allowed to comment on stations.',
	'RADIOGLOBE_PLACE_NOT_FOUND'	=> 'Place not found.',
	'RADIOGLOBE_STATION_NOT_FOUND'	=> 'Station not found.',
	'RADIOGLOBE_COMMENT_NOT_FOUND'	=> 'Comment not found.',
	'RADIOGLOBE_ERROR'				=> 'Something went wrong. Please try again.',

	'RADIOGLOBE_LOADING_GLOBE'		=> 'Loading the globe…',
	'RADIOGLOBE_WEBGL_MISSING'		=> 'Your browser does not support WebGL, so the globe cannot be shown. You can still search stations on the right.',
	'RADIOGLOBE_SEARCH_PLACEHOLDER'	=> 'Search stations, genres, countries or coordinates…',
	'RADIOGLOBE_EXPLORE'			=> 'Explore',
	'RADIOGLOBE_WELCOME_TITLE'		=> 'Spin the world',
	'RADIOGLOBE_WELCOME_TEXT'		=> 'Drag the globe and stop on a green dot, or click it: the stations of that place appear here. Each dot groups the stations of a city or area.',
	'RADIOGLOBE_EMPTY_TITLE'		=> 'No stations yet',
	'RADIOGLOBE_EMPTY_TEXT'			=> 'The station list has not been downloaded yet. It will be updated automatically at the scheduled time, or an administrator can start it now from the control panel.',
	'RADIOGLOBE_STATIONS'			=> 'stations',
	'RADIOGLOBE_PLACES'				=> 'places',
	'RADIOGLOBE_NO_STATIONS'		=> 'No stations found.',
	'RADIOGLOBE_SEARCH_RESULTS'		=> 'Results for “%s”',
	'RADIOGLOBE_NEAR_COORDS'		=> 'Stations near %s',
	'RADIOGLOBE_PLACE_REGION'		=> '(region)',
	'RADIOGLOBE_PLACE_COUNTRY'	=> '(whole country)',
	'RADIOGLOBE_SEARCHING'			=> 'Searching…',
	'RADIOGLOBE_DATA_SOURCE'		=> 'Data: Radio Browser',
	'RADIOGLOBE_IMAGERY_SOURCE'	=> 'Imagery: Esri, Maxar, Earthstar Geographics',

	'RADIOGLOBE_PLAY'				=> 'Play',
	'RADIOGLOBE_PAUSE'				=> 'Pause',
	'RADIOGLOBE_PREV'				=> 'Previous station',
	'RADIOGLOBE_NEXT'				=> 'Next station',
	'RADIOGLOBE_SHUFFLE'			=> 'Shuffle',
	'RADIOGLOBE_REPEAT'				=> 'Auto reconnect',
	'RADIOGLOBE_LIVE'				=> 'LIVE',
	'RADIOGLOBE_QUEUE'				=> 'Queue',
	'RADIOGLOBE_NOW_LISTENING'		=> 'Now playing',
	'RADIOGLOBE_NEXT_FROM'			=> 'Next from: %s',
	'RADIOGLOBE_QUEUE_EMPTY'		=> 'Nothing is playing.',
	'RADIOGLOBE_FAVORITES'			=> 'Favourites',
	'RADIOGLOBE_FAVORITES_EMPTY'	=> 'No favourite stations yet. Use the + button while listening.',
	'RADIOGLOBE_FAV_ADD'			=> 'Add to favourites',
	'RADIOGLOBE_FAV_REMOVE'			=> 'Remove from favourites',
	'RADIOGLOBE_FAV_ADDED'			=> 'Added to favourites',
	'RADIOGLOBE_FAV_REMOVED'		=> 'Removed from favourites',
	'RADIOGLOBE_LOGIN_TO_FAV'		=> 'Log in to save favourite stations.',
	'RADIOGLOBE_HISTORY'			=> 'History',
	'RADIOGLOBE_HISTORY_EMPTY'		=> 'Stations you listen to will show up here.',
	'RADIOGLOBE_VOLUME'				=> 'Volume',
	'RADIOGLOBE_MUTE'				=> 'Mute',
	'RADIOGLOBE_MINI_PLAYER'		=> 'Open mini player',
	'RADIOGLOBE_FULLSCREEN'			=> 'Full screen',
	'RADIOGLOBE_OPEN_GLOBE'			=> 'Show on the globe',
	'RADIOGLOBE_CONNECTING'			=> 'Connecting…',
	'RADIOGLOBE_STREAM_ERROR'		=> 'This station is not responding. Try another one.',
	'RADIOGLOBE_MIXED_CONTENT'		=> 'This station only streams over HTTP and the browser blocks it on a secure page.',
	'RADIOGLOBE_RESUME'				=> 'Click to resume listening',
	'RADIOGLOBE_WEBSITE'			=> 'Station website',
	'RADIOGLOBE_CLOSE'				=> 'Close',
	'RADIOGLOBE_TOAST_LISTENING'	=> '%s is listening to:',
	'RADIOGLOBE_TOAST_PLAY'		=> 'Click to listen too',
	'RADIOGLOBE_CLOSE_PLAYER'		=> 'Close the player',
	'RADIOGLOBE_INACTIVE'			=> 'Station no longer available',

	'RADIOGLOBE_COMMENTS'			=> 'Comments',
	'RADIOGLOBE_COMMENTS_EMPTY'		=> 'No comments yet. Be the first!',
	'RADIOGLOBE_COMMENTS_OFF'		=> 'Comments are disabled.',
	'RADIOGLOBE_COMMENT_PLACEHOLDER'	=> 'What do you think of this station?',
	'RADIOGLOBE_COMMENT_SEND'		=> 'Post',
	'RADIOGLOBE_COMMENT_LOGIN'		=> 'Log in with an authorised account to comment.',
	'RADIOGLOBE_COMMENT_DELETE'		=> 'Delete comment',
	'RADIOGLOBE_COMMENT_DELETE_ASK'	=> 'Delete this comment?',
	'RADIOGLOBE_COMMENT_EMPTY'		=> 'The comment is empty.',
	'RADIOGLOBE_COMMENT_TOO_LONG'	=> 'The comment exceeds the limit of %d characters.',
	'RADIOGLOBE_COMMENT_FLOOD'		=> 'You just commented: please wait a few seconds.',
	'RADIOGLOBE_LOAD_MORE'			=> 'Show more',
]);
