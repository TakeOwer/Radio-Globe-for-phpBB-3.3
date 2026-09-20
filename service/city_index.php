<?php
/**
 *
 * Radio Globe extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Citta': GeoNames (https://www.geonames.org), licenza CC BY 4.0.
 *
 */

namespace salvocortesiano\radioglobe\service;

/**
 * Riconosce una citta' nel nome, nelle etichette o nel campo regione di una stazione
 * senza coordinate ("Radio Palermo Centrale", "NRJ Lyon", "Tucson AZ").
 *
 * Dati in service/data/: cities.tsv (citta' sopra 15.000 abitanti, ordinate per paese)
 * e words.txt (parole generiche e nomi di paesi da non scambiare per luoghi).
 * Il file delle citta' si legge un paese alla volta, solo quando serve.
 */
class city_index
{
	/**
	 * Lettere accentate minuscole (latine, greche, cirilliche) e la lettera senza segni: quello che fa
	 * Normalizer (forma NFD senza i segni diacritici) quando l'estensione intl non c'e'.
	 */
	const FOLD_MAP = [
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
		'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
		'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
		'ā' => 'a', 'ă' => 'a', 'ą' => 'a', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'č' => 'c', 'ď' => 'd', 'ē' => 'e',
		'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h',
		'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ĵ' => 'j', 'ķ' => 'k', 'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l',
		'ń' => 'n', 'ņ' => 'n', 'ň' => 'n', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o', 'ŕ' => 'r', 'ŗ' => 'r', 'ř' => 'r',
		'ś' => 's', 'ŝ' => 's', 'ş' => 's', 'š' => 's', 'ţ' => 't', 'ť' => 't', 'ũ' => 'u', 'ū' => 'u', 'ŭ' => 'u',
		'ů' => 'u', 'ű' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ŷ' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z', 'ơ' => 'o',
		'ư' => 'u', 'ǎ' => 'a', 'ǐ' => 'i', 'ǒ' => 'o', 'ǔ' => 'u', 'ǖ' => 'u', 'ǘ' => 'u', 'ǚ' => 'u', 'ǜ' => 'u',
		'ǟ' => 'a', 'ǡ' => 'a', 'ǣ' => 'æ', 'ǧ' => 'g', 'ǩ' => 'k', 'ǫ' => 'o', 'ǭ' => 'o', 'ǯ' => 'ʒ', 'ǰ' => 'j',
		'ǵ' => 'g', 'ǹ' => 'n', 'ǻ' => 'a', 'ǽ' => 'æ', 'ǿ' => 'ø', 'ȁ' => 'a', 'ȃ' => 'a', 'ȅ' => 'e', 'ȇ' => 'e',
		'ȉ' => 'i', 'ȋ' => 'i', 'ȍ' => 'o', 'ȏ' => 'o', 'ȑ' => 'r', 'ȓ' => 'r', 'ȕ' => 'u', 'ȗ' => 'u', 'ș' => 's',
		'ț' => 't', 'ȟ' => 'h', 'ȧ' => 'a', 'ȩ' => 'e', 'ȫ' => 'o', 'ȭ' => 'o', 'ȯ' => 'o', 'ȱ' => 'o', 'ȳ' => 'y',
		'ḁ' => 'a', 'ḃ' => 'b', 'ḅ' => 'b', 'ḇ' => 'b', 'ḉ' => 'c', 'ḋ' => 'd', 'ḍ' => 'd', 'ḏ' => 'd', 'ḑ' => 'd',
		'ḓ' => 'd', 'ḕ' => 'e', 'ḗ' => 'e', 'ḙ' => 'e', 'ḛ' => 'e', 'ḝ' => 'e', 'ḟ' => 'f', 'ḡ' => 'g', 'ḣ' => 'h',
		'ḥ' => 'h', 'ḧ' => 'h', 'ḩ' => 'h', 'ḫ' => 'h', 'ḭ' => 'i', 'ḯ' => 'i', 'ḱ' => 'k', 'ḳ' => 'k', 'ḵ' => 'k',
		'ḷ' => 'l', 'ḹ' => 'l', 'ḻ' => 'l', 'ḽ' => 'l', 'ḿ' => 'm', 'ṁ' => 'm', 'ṃ' => 'm', 'ṅ' => 'n', 'ṇ' => 'n',
		'ṉ' => 'n', 'ṋ' => 'n', 'ṍ' => 'o', 'ṏ' => 'o', 'ṑ' => 'o', 'ṓ' => 'o', 'ṕ' => 'p', 'ṗ' => 'p', 'ṙ' => 'r',
		'ṛ' => 'r', 'ṝ' => 'r', 'ṟ' => 'r', 'ṡ' => 's', 'ṣ' => 's', 'ṥ' => 's', 'ṧ' => 's', 'ṩ' => 's', 'ṫ' => 't',
		'ṭ' => 't', 'ṯ' => 't', 'ṱ' => 't', 'ṳ' => 'u', 'ṵ' => 'u', 'ṷ' => 'u', 'ṹ' => 'u', 'ṻ' => 'u', 'ṽ' => 'v',
		'ṿ' => 'v', 'ẁ' => 'w', 'ẃ' => 'w', 'ẅ' => 'w', 'ẇ' => 'w', 'ẉ' => 'w', 'ẋ' => 'x', 'ẍ' => 'x', 'ẏ' => 'y',
		'ẑ' => 'z', 'ẓ' => 'z', 'ẕ' => 'z', 'ẖ' => 'h', 'ẗ' => 't', 'ẘ' => 'w', 'ẙ' => 'y', 'ẛ' => 'ſ', 'ạ' => 'a',
		'ả' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a',
		'ẵ' => 'a', 'ặ' => 'a', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e',
		'ệ' => 'e', 'ỉ' => 'i', 'ị' => 'i', 'ọ' => 'o', 'ỏ' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
		'ộ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o', 'ụ' => 'u', 'ủ' => 'u', 'ứ' => 'u',
		'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u', 'ỳ' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ʹ' => 'ʹ',
		';' => ';', '΅' => '¨', '·' => '·', 'ΐ' => 'ι', 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ΰ' => 'υ',
		'ϊ' => 'ι', 'ϋ' => 'υ', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ϓ' => 'ϒ', 'ϔ' => 'ϒ', 'й' => 'и', 'ѐ' => 'е',
		'ё' => 'е', 'ѓ' => 'г', 'ї' => 'і', 'ќ' => 'к', 'ѝ' => 'и', 'ў' => 'у', 'ѷ' => 'ѵ', 'ӂ' => 'ж', 'ӑ' => 'а',
		'ӓ' => 'а', 'ӗ' => 'е', 'ӛ' => 'ә', 'ӝ' => 'ж', 'ӟ' => 'з', 'ӣ' => 'и', 'ӥ' => 'и', 'ӧ' => 'о', 'ӫ' => 'ө',
		'ӭ' => 'э', 'ӯ' => 'у', 'ӱ' => 'у', 'ӳ' => 'у', 'ӵ' => 'ч', 'ӹ' => 'ы',
	];

	protected $dir;
	/** Elenco delle citta' in uso: quello scaricato dall'ACP se c'e', altrimenti quello incluso. */
	protected $file;
	/** Posizione nel file dell'inizio di ogni paese. */
	protected $offsets = null;
	/** [cc => [chiave => [[primario, migliaia di abitanti, stato USA, lat, lng, nome], ...]]] */
	protected $cities = [];
	protected $generic = null;
	protected $countries = null;

	/**
	 * @param string      $dir        cartella dei dati inclusi (cities.tsv, words.txt)
	 * @param string|null $downloaded elenco scaricato da GeoNames dall'ACP, usato al posto di quello incluso
	 */
	public function __construct($dir, $downloaded = null)
	{
		$this->dir = rtrim($dir, '/\\') . '/';
		$this->file = ($downloaded !== null && self::has_data($downloaded)) ? $downloaded : $this->dir . 'cities.tsv';
	}

	/** Un elenco delle citta' completo (non vuoto ne' troncato). */
	public static function has_data($file)
	{
		return is_file($file) && filesize($file) > 500000;
	}

	public function is_available()
	{
		return is_readable($this->file);
	}

	/** Minuscole senza accenti: stessa normalizzazione di station_sync::region_key(). */
	public static function fold($text)
	{
		$text = function_exists('utf8_strtolower') ? utf8_strtolower((string) $text) : mb_strtolower((string) $text, 'UTF-8');

		if (class_exists('Normalizer'))
		{
			$decomposed = \Normalizer::normalize($text, \Normalizer::FORM_D);
			if ($decomposed !== false)
			{
				$text = preg_replace('#\p{Mn}+#u', '', $decomposed);
			}
		}
		else
		{
			// PHP senza l'estensione intl: stessa trasformazione, da tabella
			$text = strtr($text, self::FOLD_MAP);
		}

		return (string) $text;
	}

	/** Parole del testo, gia' normalizzate: "Radio Bahía Blanca 97.1" -> radio, bahia, blanca, 97, 1 */
	public static function tokens($text)
	{
		return preg_split('#[^\p{L}\p{N}]+#u', self::fold($text), -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Stato USA scritto in fondo: "Tucson AZ", "Salem, NH". Serve a scegliere la Salem giusta.
	 */
	public static function us_state_hint($cc, array $texts)
	{
		if ($cc !== 'US')
		{
			return '';
		}

		foreach ($texts as $text)
		{
			if (preg_match('#(?:^|[\s,])([A-Z]{2})\s*$#', trim((string) $text), $m))
			{
				return $m[1];
			}
		}

		return '';
	}

	/** Parola che compare nei nomi delle radio di mezzo mondo (radio, fm, music, hits...). */
	public function is_generic($token)
	{
		$this->load_words();

		return isset($this->generic[$token]);
	}

	/** Nome di un paese o di un continente ("deutschland", "polska", "europe"): non e' una regione. */
	public function is_country_word($key)
	{
		$this->load_words();

		return isset($this->countries[$key]);
	}

	/**
	 * Cerca una citta' nei testi dati.
	 *
	 * @param string $cc      paese della stazione
	 * @param array  $texts   testi da esaminare (nome, ogni etichetta, oppure il campo regione)
	 * @param bool   $strict  true per nome ed etichette: si saltano le parole generiche (radio, fm,
	 *                        music...); false per il campo regione, scritto apposta
	 * @param string $hint    stato USA ("AZ"), '' se non indicato
	 * @return array|null ['name', 'lat', 'lng']
	 */
	public function find($cc, array $texts, $strict, $hint = '')
	{
		$cities = $this->load_country($cc);

		if (empty($cities))
		{
			return null;
		}

		$best = null;

		foreach ($texts as $text)
		{
			$tokens = self::tokens($text);
			$found = [];

			// prima le sequenze piu' lunghe: "New York" batte "York", "Bahia Blanca" batte "Blanca"
			for ($n = 3; $n >= 1; $n--)
			{
				for ($i = 0, $last = count($tokens) - $n; $i <= $last; $i++)
				{
					if ($strict && $n === 1 && $this->is_generic($tokens[$i]))
					{
						continue;
					}

					$key = implode('', array_slice($tokens, $i, $n));

					if (strlen($key) < 4 || !isset($cities[$key]) || $this->is_country_word($key))
					{
						continue;
					}

					$city = $this->pick($cc, $cities[$key], $hint);

					if ($city === null)
					{
						continue;
					}

					foreach ($found as $f)
					{
						if ($i < $f['end'] && $i + $n > $f['start'])
						{
							continue 2;
						}
					}

					$found[] = ['start' => $i, 'end' => $i + $n, 'city' => $city];
				}
			}

			foreach ($found as $f)
			{
				// nome principale prima di quello alternativo, poi la citta' piu' grande
				if ($best === null || [$f['city'][0], $f['city'][1]] > [$best[0], $best[1]])
				{
					$best = $f['city'];
				}
			}
		}

		return $best === null ? null : ['name' => $best[5], 'lat' => $best[3], 'lng' => $best[4]];
	}

	/**
	 * Fra le citta' con lo stesso nome: quella dello stato indicato (USA), altrimenti la piu' importante.
	 */
	protected function pick($cc, array $list, $hint)
	{
		if ($hint !== '')
		{
			foreach ($list as $city)
			{
				if ($city[2] === $hint)
				{
					return $city;
				}
			}

			// "Salem OR" ma nessuna Salem in Oregon nell'elenco: meglio non tirare a indovinare
			if ($cc === 'US')
			{
				return null;
			}
		}

		return $list[0];
	}

	protected function load_country($cc)
	{
		if (isset($this->cities[$cc]))
		{
			return $this->cities[$cc];
		}

		$this->cities[$cc] = [];

		if (!preg_match('#^[A-Z]{2}$#', (string) $cc))
		{
			return [];
		}

		$handle = @fopen($this->file, 'rb');

		if (!$handle)
		{
			return [];
		}

		if ($this->offsets === null)
		{
			// una lettura sola del file per sapere dove comincia ogni paese
			$this->offsets = [];
			$pos = 0;

			while (($line = fgets($handle)) !== false)
			{
				$code = substr($line, 0, 2);

				if ($line[0] !== '#' && !isset($this->offsets[$code]))
				{
					$this->offsets[$code] = $pos;
				}
				$pos += strlen($line);
			}
		}

		if (isset($this->offsets[$cc]))
		{
			fseek($handle, $this->offsets[$cc]);

			while (($line = fgets($handle)) !== false && strncmp($line, $cc . "\t", 3) === 0)
			{
				$f = explode("\t", rtrim($line, "\r\n"));

				if (count($f) === 8)
				{
					$this->cities[$cc][$f[1]][] = [$f[2] === '0' ? 1 : 0, (int) $f[4], $f[3], (float) $f[5], (float) $f[6], $f[7]];
				}
			}
		}

		fclose($handle);

		foreach ($this->cities[$cc] as &$list)
		{
			// principale prima, poi per abitanti
			usort($list, function ($a, $b) { return [$b[0], $b[1]] <=> [$a[0], $a[1]]; });
		}
		unset($list);

		return $this->cities[$cc];
	}

	protected function load_words()
	{
		if ($this->generic !== null)
		{
			return;
		}

		$this->generic = [];
		$this->countries = [];
		$section = null;

		foreach (@file($this->dir . 'words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line)
		{
			if ($line[0] === '#')
			{
				continue;
			}

			if ($line === '[generic]' || $line === '[country]')
			{
				$section = $line;
				continue;
			}

			if ($section === '[generic]')
			{
				$this->generic[$line] = true;
			}
			else if ($section === '[country]')
			{
				$this->countries[$line] = true;
			}
		}
	}
}
