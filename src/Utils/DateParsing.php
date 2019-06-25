<?php

namespace NastuzziSamy\Laravel\Utils;

use NastuzziSamy\Laravel\DateParsingException;
use Carbon\Carbon;

class DateParsing {
    public static function parse($date, string $format = null) {
        try {
            if ($format) {
                if ($format === 'timestamp')
                    return Carbon::createFromTimestamp(substr($date, 0, 10)); // To avoid js ms
                else
                    return Carbon::createFromFormat($format, $date);
            }
            else
                return Carbon::parse($date);
        } catch (\Exception $e) {
            throw new DateParsingException('A date given can not be parsed and recognized. Try to use a valid timestamp or an YYYY-mm-dd format date');
        }
    }

    public static function interval($date1, $date2, string $format1 = null, string $format2 = null, bool $allowEqualValues = false) {
        $carbonDate1 = self::parse($date1, $fromat1);
        $carbonDate2 = self::parse($date1, $fromat2);

        if ($carbonDate1 > $carbonDate2)
            throw new DateParsingException('An interval is incorrect, the second date must happen after the first one');
        else if (!$allowEqualValues && $carbonDate1 === $carbonDate2)
            throw new DateParsingException('An interval must contain two different dates');

        return [
            $carbonDate1,
            $carbonDate2
        ];
    }
}
