<?php

namespace Cesys\Utils;

class DateTimeIntervals
{
	/**
	 * "Sečte" vložené DateIntervaly
	 * @param \DateInterval[] $dateIntervals
	 * @return \DateInterval
	 */
	public static function addDateIntervals(... $dateIntervals): \DateInterval
	{
		$reference = new \DateTimeImmutable;
		$endTime = \DateTime::createFromImmutable($reference);

		foreach ($dateIntervals as $dateInterval) {
			if ( ! $dateInterval instanceof \DateInterval) {
				continue;
			}
			$endTime = $endTime->add($dateInterval);
		}

		return $reference->diff($endTime);
	}
}