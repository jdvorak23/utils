<?php

namespace Cesys\Utils;

use Nette\Utils\DateTime;

class DateTimeHelper
{
	/**
	 * Ze zadaných parametrů vrátí DateTime, nebo null, pokud nelze řádně vytvořit
	 * Pokud $stringDate je null, '', nebo (po trim()) obsahuje víc jak jeden delimiter -> vrátí se null
	 * Datum a čas musí být ve $stringDate odděleno $delimiter. Čas je nepovinný, pokud není uveden, bude nastaven na 0,0,0 (tj. jen datum)
	 * Kontroluje existenci datumu -> řeší "nešvar" DateTime::createFromFormat, že když do něj pošleš datum 32.2.2025,
	 * tak to považuje za validní, a vrátí ti datum 4.3.2025.
	 * Tato metoda to za validní nepovažuje, a v takových případech taky vrací null
	 * 0000-00-00 bude taky null
	 * @param ?string $stringDate Datum k převodu
	 * @param string $dateFormat Formát části $stringDate pro rok
	 * @param string $delimiter string oddělující $dateFormat a $timeFormat v $stringDate, v SQL je to ' ', v jiných případech může být jinak, např 'T'
	 * @param string $timeFormat Formát části $stringDate pro čas
	 * @param null $timezone doplní funkcí date_default_timezone_get()
	 * @return DateTime|null
	 */
	public static function createValidFromFormat(
		?string $stringDate,
		string $dateFormat = 'Y-m-d',
		string $delimiter = ' ',
		string $timeFormat = 'H:i:s',
		$timezone = null
	): ?DateTime
	{
		$stringDate = trim($stringDate);
		if ( ! $stringDate) {
			return null;
		}
		$parts = explode($delimiter, $stringDate);
		if (count($parts) > 2) {
			return null;
		} elseif (count($parts) == 2) {
			$format = $dateFormat . $delimiter . $timeFormat;
			$date = DateTime::createFromFormat($format, $stringDate, $timezone);
		} else {
			// Jenom datum bez času
			$format = $dateFormat;
			$date = DateTime::createFromFormat('!' . $format, $stringDate, $timezone);
		}

		if ( ! $date) {
			return null;
		}

		return $date->format($format) === $stringDate ? $date : null;
	}
}