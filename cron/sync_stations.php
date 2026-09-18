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

use salvocortesiano\radioglobe\service\station_sync;

/**
 * Aggiornamento automatico delle stazioni alle ore scelte in ACP.
 *
 * Le attivita' pianificate di phpBB partono in coda alla visita di una
 * pagina: all'ora indicata (o alla prima visita successiva) la
 * sincronizzazione si avvia, e ogni visita seguente ne porta avanti un
 * pezzo finche' non e' completa. Se nell'ora prevista nessuno visita il
 * forum, l'aggiornamento parte alla prima visita utile.
 */
class sync_stations extends \phpbb\cron\task\base
{
	/** Secondi di lavoro per ogni esecuzione. */
	const SLICE = 20;

	protected $config;
	protected $sync;

	public function __construct(\phpbb\config\config $config, station_sync $sync)
	{
		$this->config = $config;
		$this->sync = $sync;
	}

	public function is_runnable()
	{
		// una sincronizzazione avviata a mano dall'ACP va comunque finita
		return !empty($this->config['radioglobe_cron_enabled']) || $this->sync->is_running();
	}

	public function should_run()
	{
		if ($this->sync->is_running())
		{
			// non si insiste se un altro processo sta lavorando
			return (int) $this->config['radioglobe_sync_lock'] < time() - 30;
		}

		$scheduled = $this->sync->last_scheduled_time();

		return $scheduled > 0 && (int) $this->config['radioglobe_cron_last'] < $scheduled;
	}

	public function run()
	{
		if (!$this->sync->is_running())
		{
			$this->config->set('radioglobe_cron_last', time(), false);

			if (!$this->sync->start('cron'))
			{
				return;
			}
		}

		$this->sync->run_for(self::SLICE);
	}
}
