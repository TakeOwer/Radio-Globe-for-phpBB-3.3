<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\cron;

use salvocortesiano\radioglobe\service\city_update;

/**
 * Aggiornamento automatico dell'elenco delle citta' (ACP > Citta' (GeoNames)).
 *
 * Quando l'elenco in uso e' piu' vecchio della soglia scelta, il cron scarica quello nuovo da
 * GeoNames a passi brevi, in coda alle visite del forum, come l'aggiornamento delle stazioni.
 * Se qualcosa va storto resta in uso l'elenco di prima e si riprova il giorno dopo.
 * Un download avviato a mano dall'ACP e poi lasciato a meta' viene portato a termine.
 */
class update_cities extends \phpbb\cron\task\base
{
	/** Secondi di lavoro per ogni esecuzione. */
	const SLICE = 20;
	/** Dopo un tentativo automatico, il successivo non prima di un giorno. */
	const RETRY_AFTER = 86400;

	protected $config;
	protected $update;

	public function __construct(\phpbb\config\config $config, city_update $update)
	{
		$this->config = $config;
		$this->update = $update;
	}

	public function is_runnable()
	{
		return !empty($this->config['radioglobe_cities_auto']) || $this->update->is_running();
	}

	public function should_run()
	{
		if ($this->update->is_running())
		{
			return !$this->update->is_locked();
		}

		return (int) $this->config['radioglobe_cities_auto_last'] < time() - self::RETRY_AFTER
			&& $this->update->is_outdated();
	}

	public function run()
	{
		if (!$this->update->is_running())
		{
			$this->config->set('radioglobe_cities_auto_last', time(), false);

			if (!$this->update->start('cron'))
			{
				return;
			}
		}

		$this->update->run_for(self::SLICE);
	}
}
