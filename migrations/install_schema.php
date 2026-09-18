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

class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'radioglobe_stations');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				// Stazioni scaricate da Radio Browser. Le coordinate sono
				// salvate come interi (gradi x 1.000.000) per non perdere
				// precisione con il tipo DECIMAL di phpBB, che ha 2 decimali.
				$this->table_prefix . 'radioglobe_stations'	=> [
					'COLUMNS'		=> [
						'station_id'		=> ['UINT', null, 'auto_increment'],
						'station_uuid'		=> ['VCHAR:36', ''],
						'station_name'		=> ['VCHAR_UNI:255', ''],
						'stream_url'		=> ['TEXT_UNI', ''],
						'homepage'			=> ['TEXT_UNI', ''],
						'favicon'			=> ['TEXT_UNI', ''],
						'tags'				=> ['VCHAR_UNI:255', ''],
						'country'			=> ['VCHAR_UNI:100', ''],
						'countrycode'		=> ['VCHAR:2', ''],
						'state'				=> ['VCHAR_UNI:100', ''],
						'language'			=> ['VCHAR_UNI:100', ''],
						'codec'				=> ['VCHAR:20', ''],
						'bitrate'			=> ['USINT', 0],
						'is_https'			=> ['BOOL', 0],
						'geo_lat'			=> ['INT:11', 0],
						'geo_long'			=> ['INT:11', 0],
						'place_key'			=> ['VCHAR:40', ''],
						'votes'				=> ['UINT', 0],
						'clicks'			=> ['UINT', 0],
						'station_hash'		=> ['VCHAR:32', ''],
						'station_active'	=> ['BOOL', 1],
						'last_seen'			=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'station_id',
					'KEYS'			=> [
						'rg_uuid'		=> ['UNIQUE', 'station_uuid'],
						'rg_place'		=> ['INDEX', ['place_key', 'station_active']],
						'rg_active'		=> ['INDEX', 'station_active'],
					],
				],

				// Luoghi del globo: stazioni vicine raggruppate in un punto,
				// come i pallini di Radio Garden.
				$this->table_prefix . 'radioglobe_places'	=> [
					'COLUMNS'		=> [
						'place_key'		=> ['VCHAR:40', ''],
						'place_title'	=> ['VCHAR_UNI:150', ''],
						'country'		=> ['VCHAR_UNI:100', ''],
						'countrycode'	=> ['VCHAR:2', ''],
						'geo_lat'		=> ['INT:11', 0],
						'geo_long'		=> ['INT:11', 0],
						'station_count'	=> ['UINT', 0],
					],
					'PRIMARY_KEY'	=> 'place_key',
				],

				// Preferiti dell'utente: e' anche la sua playlist personale.
				$this->table_prefix . 'radioglobe_favorites'	=> [
					'COLUMNS'		=> [
						'fav_id'		=> ['UINT', null, 'auto_increment'],
						'user_id'		=> ['UINT', 0],
						'station_id'	=> ['UINT', 0],
						'fav_time'		=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'fav_id',
					'KEYS'			=> [
						'rg_user_st'	=> ['UNIQUE', ['user_id', 'station_id']],
						'rg_station'	=> ['INDEX', 'station_id'],
					],
				],

				// Commenti alle stazioni.
				$this->table_prefix . 'radioglobe_comments'	=> [
					'COLUMNS'		=> [
						'comment_id'	=> ['UINT', null, 'auto_increment'],
						'station_id'	=> ['UINT', 0],
						'user_id'		=> ['UINT', 0],
						'comment_text'	=> ['TEXT_UNI', ''],
						'comment_time'	=> ['TIMESTAMP', 0],
						'comment_ip'	=> ['VCHAR:40', ''],
					],
					'PRIMARY_KEY'	=> 'comment_id',
					'KEYS'			=> [
						'rg_st_time'	=> ['INDEX', ['station_id', 'comment_time']],
						'rg_user'		=> ['INDEX', 'user_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'radioglobe_comments',
				$this->table_prefix . 'radioglobe_favorites',
				$this->table_prefix . 'radioglobe_places',
				$this->table_prefix . 'radioglobe_stations',
			],
		];
	}
}
