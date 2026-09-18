<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\radioglobe\service;

/**
 * Richieste HTTP in uscita: cURL quando disponibile, altrimenti gli
 * stream di PHP. Tutte le chiamate hanno un timeout: l'estensione non
 * deve mai tenere appesa una pagina del forum.
 */
class http_client
{
	const USER_AGENT = 'RadioGlobe-phpBB/1.0';

	/**
	 * @param string $url
	 * @param array $headers  righe "Nome: valore"
	 * @param int $timeout    secondi
	 * @return array ['status' => int, 'body' => string, 'error' => string]
	 */
	public function get($url, array $headers = [], $timeout = 30)
	{
		if (function_exists('curl_init'))
		{
			return $this->get_curl($url, $headers, $timeout);
		}

		return $this->get_stream($url, $headers, $timeout);
	}

	public function has_curl()
	{
		return function_exists('curl_init');
	}

	public function user_agent()
	{
		// forma "Mozilla/5.0 (compatible; ...)": alcuni server di streaming (es. SomaFM) non rispondono
		// affatto a un User-Agent che non comincia cosi', e il titolo del brano non arrivava mai.
		// Il nome dell'estensione e l'indirizzo del forum restano dichiarati.
		return 'Mozilla/5.0 (compatible; ' . self::USER_AGENT . '; +' . generate_board_url() . ')';
	}

	protected function get_curl($url, array $headers, $timeout)
	{
		$ch = curl_init($url);

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_MAXREDIRS		=> 3,
			CURLOPT_CONNECTTIMEOUT	=> min(10, (int) $timeout),
			CURLOPT_TIMEOUT			=> (int) $timeout,
			CURLOPT_ENCODING		=> '',
			CURLOPT_USERAGENT		=> $this->user_agent(),
			CURLOPT_HTTPHEADER		=> $headers,
			CURLOPT_SSL_VERIFYPEER	=> true,
		]);

		$body = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = ($body === false) ? curl_error($ch) : '';
		curl_close($ch);

		return [
			'status'	=> $status,
			'body'		=> ($body === false) ? '' : (string) $body,
			'error'		=> $error,
		];
	}

	protected function get_stream($url, array $headers, $timeout)
	{
		$headers[] = 'User-Agent: ' . $this->user_agent();

		$context = stream_context_create([
			'http'	=> [
				'method'			=> 'GET',
				'header'			=> implode("\r\n", $headers),
				'timeout'			=> (int) $timeout,
				'follow_location'	=> 1,
				'max_redirects'		=> 3,
				'ignore_errors'		=> true,
			],
		]);

		$body = @file_get_contents($url, false, $context);
		$status = 0;

		if (!empty($http_response_header))
		{
			foreach ($http_response_header as $line)
			{
				if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m))
				{
					$status = (int) $m[1];
				}
			}
		}

		return [
			'status'	=> $status,
			'body'		=> ($body === false) ? '' : (string) $body,
			'error'		=> ($body === false) ? 'stream request failed' : '',
		];
	}

	/**
	 * Legge l'inizio di uno stream audio chiedendo i metadati ICY e
	 * restituisce il titolo in onda (StreamTitle), se il server lo manda.
	 *
	 * Si scaricano al massimo "metaint" byte di audio piu' il blocco dei
	 * metadati (in genere 16-32 KB), poi la connessione viene chiusa.
	 *
	 * @param string $url
	 * @param int $timeout
	 * @return string|null
	 */
	public function read_icy_title($url, $timeout = 5)
	{
		if (!function_exists('curl_init'))
		{
			return null;
		}

		$metaint = 0;
		$buffer = '';
		$title = null;
		$limit = 200000;

		$ch = curl_init($url);

		curl_setopt_array($ch, [
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_MAXREDIRS		=> 3,
			CURLOPT_CONNECTTIMEOUT	=> 4,
			CURLOPT_TIMEOUT			=> (int) $timeout,
			CURLOPT_USERAGENT		=> $this->user_agent(),
			CURLOPT_HTTPHEADER		=> ['Icy-MetaData: 1'],
			CURLOPT_HEADERFUNCTION	=> function ($ch, $line) use (&$metaint) {
				// con i redirect arrivano piu' blocchi di intestazioni:
				// vale l'ultimo
				if (preg_match('#^(HTTP/|ICY )#i', $line))
				{
					$metaint = 0;
				}
				else if (preg_match('#^icy-metaint:\s*(\d+)#i', $line, $m))
				{
					$metaint = (int) $m[1];
				}

				return strlen($line);
			},
			CURLOPT_WRITEFUNCTION	=> function ($ch, $data) use (&$metaint, &$buffer, &$title, $limit) {
				if ($metaint <= 0)
				{
					// nessun metadato annunciato: inutile proseguire
					return -1;
				}

				$buffer .= $data;
				$len = strlen($buffer);

				if ($len > $metaint)
				{
					$meta_len = ord($buffer[$metaint]) * 16;

					if ($meta_len === 0)
					{
						$title = '';
						return -1;
					}

					if ($len >= $metaint + 1 + $meta_len)
					{
						$meta = substr($buffer, $metaint + 1, $meta_len);

						if (preg_match("#StreamTitle='(.*?)';#s", $meta, $m))
						{
							$title = $m[1];
						}
						else
						{
							$title = '';
						}

						return -1;
					}
				}

				return ($len > $limit) ? -1 : strlen($data);
			},
		]);

		curl_exec($ch);
		curl_close($ch);

		if ($title === null || $title === '')
		{
			return $title;
		}

		$title = rtrim($title, "\0");

		if (function_exists('mb_check_encoding') && !mb_check_encoding($title, 'UTF-8'))
		{
			$title = mb_convert_encoding($title, 'UTF-8', 'ISO-8859-1');
		}

		// alcuni server (Shoutcast/Icecast) mandano i caratteri non latini
		// come entita' HTML: "&#1050;&#1077;..." va riportato a "Ке..."
		$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return trim($title);
	}
}
