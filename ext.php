<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe;

class ext extends \phpbb\extension\base
{
	/**
	 * Richiede phpBB 3.3.0 o superiore e l'estensione JSON di PHP.
	 *
	 * @return bool
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		return version_compare($config['version'], '3.3.0', '>=')
			&& function_exists('json_decode');
	}

	/**
	 * Alla disattivazione si eliminano i file temporanei di una
	 * sincronizzazione eventualmente rimasta a meta'.
	 */
	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			$root = $this->container->getParameter('core.root_path');
			$dir = $root . 'store/radioglobe/';

			foreach (['sync_state.json', 'sync_stations.ndjson'] as $file)
			{
				if (is_file($dir . $file))
				{
					@unlink($dir . $file);
				}
			}

			return 'files_removed';
		}

		return parent::disable_step($old_state);
	}
}
