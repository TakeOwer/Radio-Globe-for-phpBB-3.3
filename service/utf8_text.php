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
 * Testi esterni (titoli ICY degli stream, nomi di Radio Browser) sempre in UTF-8 valido e leggibile.
 *
 * - Byte non UTF-8: quasi sempre Windows-1252 / ISO-8859-1 ("Tamb\xe9m" -> "Também"), anche le
 *   virgolette curve e il simbolo dell'euro che ISO-8859-1 non conosce.
 * - Doppia codifica ("DinÃ¡mica" -> "Dinámica"): si ripara solo se il risultato e' UTF-8 valido.
 * - Sequenze di caratteri sostitutivi ("WXPN ����") dove l'originale e' andato perso alla fonte.
 * Funziona anche senza le estensioni mbstring e iconv.
 */
class utf8_text
{
	/** Tracce tipiche della doppia codifica: "Ã©", "Ã¨", "Â°", "â€™", "Ð¿" (cirillico). */
	const MOJIBAKE = '#(?:[ÃÐÑ][\x{80}-\x{BF}\x{152}\x{153}\x{160}\x{161}\x{178}\x{17D}\x{17E}\x{192}\x{2C6}\x{2DC}\x{2013}-\x{203A}\x{20AC}\x{2122}]|Â[\x{A0}-\x{BF}]|â€)#u';

	public static function fix($text)
	{
		$text = (string) $text;

		if ($text === '')
		{
			return '';
		}

		if (!preg_match('//u', $text))
		{
			$text = self::from_windows_1252($text);
		}
		else if (preg_match(self::MOJIBAKE, $text))
		{
			$back = self::to_windows_1252($text);

			if ($back !== null && $back !== $text && preg_match('//u', $back) && !preg_match(self::MOJIBAKE, $back))
			{
				$text = $back;
			}
		}

		return trim(preg_replace('#\x{FFFD}{2,}#u', '', $text));
	}

	protected static function from_windows_1252($text)
	{
		$out = false;

		if (function_exists('mb_convert_encoding'))
		{
			$out = @mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
		}
		else if (function_exists('iconv'))
		{
			$out = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
		}

		if (!is_string($out) || !preg_match('//u', $out))
		{
			// ultima risorsa: ogni byte alto come carattere ISO-8859-1
			$out = preg_replace_callback('#[\x80-\xFF]#', function ($m) {
				$c = ord($m[0]);
				return chr(0xC0 | ($c >> 6)) . chr(0x80 | ($c & 0x3F));
			}, $text);
		}

		return $out;
	}

	/** Testo UTF-8 riportato ai byte Windows-1252 da cui era stato (erroneamente) ricodificato. */
	protected static function to_windows_1252($text)
	{
		if (function_exists('iconv'))
		{
			$out = @iconv('UTF-8', 'Windows-1252', $text);
			return is_string($out) ? $out : null;
		}

		if (function_exists('mb_convert_encoding'))
		{
			// mbstring mette "?" al posto dei caratteri che Windows-1252 non ha: allora non era doppia codifica
			$out = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
			return (is_string($out) && substr_count($out, '?') === substr_count($text, '?')) ? $out : null;
		}

		return null;
	}
}
