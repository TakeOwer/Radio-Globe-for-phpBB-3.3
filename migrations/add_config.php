<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\migrations;

class add_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['radioglobe_cron_hours']);
	}

	public static function depends_on()
	{
		return ['\salvocortesiano\radioglobe\migrations\install_schema'];
	}

	public function update_data()
	{
		return [
			// --- Sorgente dati (Radio Browser) ---
			['config.add', ['radioglobe_api_server', '']],
			['config.add', ['radioglobe_https_only', 1]],
			['config.add', ['radioglobe_exclude_hls', 1]],
			['config.add', ['radioglobe_min_bitrate', 0]],
			['config.add', ['radioglobe_max_stations', 30000]],
			// ampiezza della griglia che raggruppa le stazioni vicine, in
			// centesimi di grado (25 = 0,25 gradi, circa 25-28 km)
			['config.add', ['radioglobe_cluster_grid', 25]],
			// generi (tag) ammessi: elenco lungo, quindi in config_text
			['config_text.add', ['radioglobe_tags', '']],

			// --- Aggiornamento automatico ---
			['config.add', ['radioglobe_cron_enabled', 1]],
			// ore di avvio, separate da virgola (fuso orario del forum)
			['config.add', ['radioglobe_cron_hours', '4,16']],
			['config.add', ['radioglobe_cron_last', 0, true]],

			// --- Stato dell'ultima sincronizzazione ---
			['config.add', ['radioglobe_sync_last', 0, true]],
			['config.add', ['radioglobe_sync_count', 0, true]],
			['config.add', ['radioglobe_sync_places', 0, true]],
			['config.add', ['radioglobe_sync_duration', 0, true]],
			['config.add', ['radioglobe_sync_error', '', true]],
			['config.add', ['radioglobe_sync_lock', 0, true]],
			['config.add', ['radioglobe_data_version', 0, true]],

			// --- Player e globo ---
			['config.add', ['radioglobe_player_everywhere', 1]],
			['config.add', ['radioglobe_nowplaying', 1]],
			['config.add', ['radioglobe_covers', 1]],
			['config.add', ['radioglobe_texture', 'dark']],
			['config.add', ['radioglobe_autorotate', 1]],
			['config.add', ['radioglobe_nav_link', 1]],

			// --- Commenti ---
			['config.add', ['radioglobe_comments_enabled', 1]],
			['config.add', ['radioglobe_comment_maxlen', 1000]],
			['config.add', ['radioglobe_comments_per_page', 20]],
			['config.add', ['radioglobe_comment_flood', 15]],
		];
	}

	public function revert_data()
	{
		return [
			['config_text.remove', ['radioglobe_tags']],
		];
	}
}
