<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\event;

use salvocortesiano\radioglobe\service\city_update;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Avviso nella pagina principale dell'ACP quando l'elenco delle citta' e' vecchio.
 */
class acp_listener implements EventSubscriberInterface
{
	protected $config;
	protected $template;
	protected $user;
	protected $update;
	protected $php_ext;

	public static function getSubscribedEvents()
	{
		return [
			'core.acp_main_notice'	=> 'cities_notice',
		];
	}

	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, city_update $update, $php_ext)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->update = $update;
		$this->php_ext = $php_ext;
	}

	public function cities_notice()
	{
		if (empty($this->config['radioglobe_cities_notice']) || $this->update->is_running() || !$this->update->is_outdated())
		{
			return;
		}

		$this->user->add_lang_ext('salvocortesiano/radioglobe', 'info_acp_radioglobe');

		$this->template->assign_vars([
			'S_RADIOGLOBE_CITIES_OLD'		=> true,
			'RADIOGLOBE_CITIES_OLD_TEXT'	=> self::notice_text($this->user, $this->update, $this->config),
			'U_RADIOGLOBE_CITIES'			=> append_sid('index.' . $this->php_ext, 'i=-salvocortesiano-radioglobe-acp-main_module&amp;mode=cities'),
		]);
	}

	/** "L'elenco delle citta' e' del 19/09/2026, 7 mesi fa..." (usato anche nella scheda Citta'). */
	public static function notice_text(\phpbb\user $user, city_update $update, $config)
	{
		$date = $update->data_date();
		$months = max(1, (int) floor($update->age_days() / 30));
		$text = $user->lang('RADIOGLOBE_CITIES_OLD', $user->format_date(strtotime($date . ' 12:00:00 UTC'), 'd/m/Y'), $months);

		if (!empty($config['radioglobe_cities_auto']))
		{
			$text .= ' ' . $user->lang('RADIOGLOBE_CITIES_OLD_AUTO');
		}

		return $text;
	}
}
