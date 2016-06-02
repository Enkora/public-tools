<?php
// Date handling functions

// date formats
define('NDF_DATETIME', "Y-m-d H:i:s");
define('NDF_DATE', "Y-m-d");
define('NDF_TIME', "H:i:s");
define('NDF_TIME_MINUTES', "H:i");
define('NDF_MONTH', "Y-m");
define('NDF_YEAR', "Y");
define('NDF_MINUTE', "Y-m-d H:i");
define('NDF_HOUR', "Y-m-d H");
define('NDF_WEEK', "Y-\WW");

define('NDF_DATETIME_XML', "Y-m-d\TH:i:s");

define('NDF_FI_DATE', "d/m/Y"); // used for wintime
define('NDF_FI_DATETIME', "d.m.Y H:i:s");
define('NDF_FI_DATETIME_MINUTES', "d.m.Y H:i");

// datetime parts in seconds
define('NDT_SECOND', 1);
define('NDT_MINUTE', 60);
define('NDT_HOUR', 3600);
define('NDT_DAY', 86400);
define('NDT_WEEK', 604800);
define('NDT_MONTH', 2592000); // approximate - only for type definition
define('NDT_YEAR', 31536000); // approximate - only for type definition
define('NDT_ZERO_DATE', '0000-00-00');
define('NDT_ZERO_TIME', '00:00:00');
define('NDT_ZERO_DATETIME', '0000-00-00 00:00:00');
define('NDT_DATE_PAST', '1900-01-01');
define('NDT_DATE_FUTURE', '3000-01-01');

/**
 * @return string
 * @param string $s
 * @desc Returns separator that has been used to separate numerical values.
 */
function find_separator($s)
{
	for ($i = 0; $i < strlen($s); $i++)
		if (!is_numeric($s{$i})) return $s{$i};
	return false;
}

/**
 * @return string
 * @param string $s
 * @desc Converts relaxed date input string into dates that are ISO compliant.
 */
function fix_dates($s)
{
	$dates = explode('--', $s);
	foreach ($dates as $key => $date) {
		$date = trim($date);
		$dates[$key] = format_date_as_ISO8601($date);
	}
	// set the date of the first date to today's date if it's empty
	@list($date_string, $time_string) = split_date_time($dates[0]);
	if (!$date_string && $time_string) {
		$date_string = date(NDF_DATE);
		$dates[0] = $date_string . " " . $time_string;
	}
	$s = implode('--', $dates);
	return $s;
}

/**
 * fix the date before feeding it to mysql
 * @param $date
 * @return bool|string
 */
function fix_date($date)
{
	if (!$date) return '';
	return date(NDF_DATE, strtotime($date));
}

function make_full_time($time_string)
{
	$hour = $minute = $second = 0;
	switch (time_type($time_string)) {
		case NDT_SECOND:
			list($hour, $minute, $second) = explode(':', $time_string);
			break;
		case NDT_MINUTE:
			list($hour, $minute) = explode(':', $time_string);
			break;
		case NDT_HOUR:
			$hour = $time_string;
			break;
	}
	return [$hour, $minute, $second];
}

function make_full_date($date_string)
{
	$year = $month = $day = 1;
	switch (date_type($date_string)) {
		case NDT_DAY:
			list($year, $month, $day) = explode('-', $date_string);
			break;
		case NDT_WEEK:
			list($year, $week) = explode("-W", $date_string);
			// weekday of the first day of the year (starting from sunday(0))
			$first_day_of_year = date('w', mktime(0, 0, 0, 1, 1, $year));
			$first_day_of_year = ($first_day_of_year + 6) % 7; // weekday of the first day of the year (starting from monday(0))
			$day_of_year = $week * 7 - $first_day_of_year + ($first_day_of_year > 3 ? 7 : 0) - 6;
			$day         = $day_of_year;
			break;
		case NDT_MONTH:
			list($year, $month) = explode('-', $date_string);
			break;
		case NDT_YEAR:
			$year = $date_string;
			break;
	}
	return [$year, $month, $day];
}

/**
 * Returns an array of date components based on the supplied datetime string
 * @param string $iso_datetime
 * @return array
 */
function parse_date($iso_datetime)
{
	$r = [];
	@list($date, $time) = split_date_time($iso_datetime);
	if (date_type($date) == NDT_WEEK)
		@list($r[NDT_YEAR], $r[NDT_WEEK]) = explode("W", $date);
	else
		@list($r[NDT_YEAR], $r[NDT_MONTH], $r[NDT_DAY]) = explode('-', $date);

	@list($r[NDT_HOUR], $r[NDT_MINUTE], $r[NDT_SECOND]) = explode(':', $time);
	$f = [];
	foreach ($r as $key => $value) if ($value) $f[$key] = $value + 0;
	ksort($f);
	return $f;
}

function make_timestamp($r)
{
	$r[NDT_MONTH]  = @$r[NDT_MONTH] ? $r[NDT_MONTH] : 1;
	$r[NDT_DAY]    = @$r[NDT_DAY] ? $r[NDT_DAY] : 1;
	$r[NDT_HOUR]   = @$r[NDT_HOUR] ? $r[NDT_HOUR] : 0;
	$r[NDT_MINUTE] = @$r[NDT_MINUTE] ? $r[NDT_MINUTE] : 0;
	$r[NDT_SECOND] = @$r[NDT_SECOND] ? $r[NDT_SECOND] : 0;
	$timestamp     = mktime($r[NDT_HOUR], $r[NDT_MINUTE], $r[NDT_SECOND], $r[NDT_MONTH], $r[NDT_DAY], $r[NDT_YEAR]);
	return $timestamp;
}

function make_iso_date($r)
{
	$f = $r;
	if (isset($r[NDT_WEEK])) {
		$first_day_of_year = date('w', mktime(0, 0, 0, 1, 1, $r[NDT_YEAR]));
		$first_day_of_year = ($first_day_of_year + 6) % 7; // weekday of the first day of the year (starting from monday(0))
		$day_of_year = $r[NDT_WEEK] * 7 - $first_day_of_year + ($first_day_of_year > 3 ? 7 : 0) - 6;
		$f[NDT_DAY]  = $day_of_year;
	}

	if (!isset($f[NDT_DAY])) $f[NDT_DAY] = 1;
	if (!isset($r[NDT_MONTH])) $f[NDT_MONTH] = 1;

	$f[NDT_HOUR]   = @$f[NDT_HOUR] ? $f[NDT_HOUR] : 0;
	$f[NDT_MINUTE] = @$f[NDT_MINUTE] ? $f[NDT_MINUTE] : 0;
	$f[NDT_SECOND] = @$f[NDT_SECOND] ? $f[NDT_SECOND] : 0;

	$timestamp = mktime(
		$f[NDT_HOUR], $f[NDT_MINUTE], $f[NDT_SECOND], $f[NDT_MONTH], $f[NDT_DAY], $f[NDT_YEAR]);
	ksort($r);
	$type = '';
	foreach ($r as $key => $value)
		if (isset($r[$key])) {
			$type = $key;
			break;
		}

	$date = date(date_type_format($type), $timestamp);
	return $date;
}

function add_period($r, $period_length)
{
	ksort($r);
	$type = '';
	foreach ($r as $key => $value)
		if (isset($r[$key])) {
			$type = $key;
			break;
		}
	$r[$type] = $r[$type] + $period_length;
	return $r;
}

/**
 * @return string
 * @param string $isodate
 * @desc Returns start date of the given date period.
 */
function start_date($isodate)
{
	if (empty($isodate)) return '';
	@list($date_string, $time_string) = split_date_time($isodate);
	list($hour, $minute, $second) = make_full_time($time_string);
	list($year, $month, $day) = make_full_date($date_string);

	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
	if ($time_string) $start_date = date(NDF_DATETIME, $timestamp);
	else $start_date = date(NDF_DATE, $timestamp);
	return $start_date;
}

/**
 * @return string
 * @param string         $isodate
 * @param string|boolean $isodate_start
 * @desc Returns end date of the given date period.
 *       If $isodate contains just time - then $isodate_start is used for the date.
 */
function end_date($isodate, $isodate_start = false)
{
	if (empty($isodate)) return '';
	@list($date_string, $time_string) = split_date_time($isodate);
	/** @noinspection PhpUnusedLocalVariableInspection */
	@list($date_start_string, $time_start_string) = split_date_time($isodate_start);

	list($hour, $minute, $second) = make_full_time($time_string);
	if ($date_string) list($year, $month, $day) = make_full_date($date_string);
	else list($year, $month, $day) = make_full_date($date_start_string);
	if ($time_string) {
		switch (time_type($time_string)) {
			case NDT_HOUR:
				$hour++;
				break;
			case NDT_MINUTE:
				$minute++;
				break;
			case NDT_SECOND:
				$second++;
				break;
		}
	}
	else {
		switch (date_type($date_string)) {
			case NDT_DAY:
				$day++;
				break;
			case NDT_WEEK:
				$day += 7;
				break;
			case NDT_MONTH:
				$month++;
				break;
			case NDT_YEAR:
				$year++;
				break;
		}
	}

	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
	if ($time_string) $end_date = date(NDF_DATETIME, $timestamp);
	else $end_date = date(NDF_DATE, $timestamp);
	return $end_date;
}

/**
 * @return array
 * @param array|string $isodates
 * @desc Modifies two dates so that they could be used as search parameters in SQL.
E.g. if $dates contains (2004), the function will return (2004-01-01,2005-01-01).
If $dates contains (2004-10,2004-11), the function will return (2004-10-01,2004-12-01).
Both input and output dates are in ISO format. Input parameter can be either a string or an array.
 */
function date_range($isodates)
{
	if (!is_array($isodates)) {
		$tmp = $isodates;

		$isodates    = [];
		$isodates[0] = $tmp;
		$isodates[1] = $tmp;
	}
	elseif (count($isodates) == 1) $isodates[1] = $isodates[0];
	$r[0] = start_date($isodates[0]);
	$r[1] = end_date($isodates[1], $r[0]);
	return $r;
}

function split_date_time($date_time_string)
{
	$blocks      = explode(' ', $date_time_string);
	$time_string = $date_string = false;
	if (count($blocks) == 2) {
		$date_string = $blocks[0];
		$time_string = $blocks[1];
	}
	else { // if there is only date or time decide which one is it
		$separator = find_separator($blocks[0]);
		if ($separator == ':') $time_string = $blocks[0];
		else $date_string = $blocks[0];
	}
	return [$date_string, $time_string];
}

/**
 * @return string
 * @param string $date_time_string
 * @desc Formats one date string as ISO8601 date.
 */
function format_date_as_ISO8601($date_time_string)
{
	$filtered_date_time_string = str_replace(' ', '', $date_time_string);
	$filtered_date_time_string = str_replace(':', '', $filtered_date_time_string);
	$filtered_date_time_string = str_replace('-', '', $filtered_date_time_string);
	$filtered_date_time_string = str_replace('.', '', $filtered_date_time_string);
	$filtered_date_time_string = str_replace('w', '', $filtered_date_time_string);
	$filtered_date_time_string = str_replace('W', '', $filtered_date_time_string);

	$date = '';
	if ($filtered_date_time_string && !is_numeric($filtered_date_time_string)) {
		$date = strtotime($date_time_string);
		if ($date == -1) return '';
		$date = date(NDF_DATE, $date);
		return $date;
	}

	list($date_string, $time_string) = split_date_time($date_time_string);

	/** @noinspection PhpUnusedLocalVariableInspection */
	$year = $month = $day = $week = $hour = $minute = $second = false;

	if ($date_string) {
		$separator = '-';
		if (substr_count($date_string, 'W') || substr_count($date_string, 'w')) {
			$a = explode('W', $date_string);
			if (count($a) == 1) $a = explode('w', $date_string);
			list($year, $week) = $a;
			if ((string)$year === '') $year = date("Y") + 0;
			else $year = $year + 0;
		}
		else if (substr_count($date_string, $separator) == 0) {
			$l = strlen($date_string);
			switch ($l) {
				case 8:
					list($year, $month, $day) = sscanf($date_string, "%4s%2s%2s");
					break;
				case 6:
					list($year, $month, $day) = sscanf($date_string, "%2s%2s%2s");
					break;
				case 4:
				case 2:
					$year = $date_string;
					break;
				default:
					return false;
			}
		}
		else {
			@list($year, $month, $day) = explode($separator, $date_string);
			$year  = trim($year);
			$month = trim($month);
			$day   = trim($day);
		}
		switch (strlen($year)) {
			case 1:
				$year = "200" . $year;
				break;
			case 2:
				$year = $year >= 70 ? "19" . $year : "20" . $year;
				break;
			case 3:
				$year = "1" . $year;
				break;
		}
		$date = $year;
		$date = $month ? "$date-" . sprintf("%02d", $month) : $date;
		$date = $day ? "$date-" . sprintf("%02d", $day) : $date;
		$date = $week ? "$date-W" . sprintf("%02d", $week) : $date;
	}
	if ($time_string) {
		$separator = ':';
		if (substr_count($time_string, $separator) == 0) {
			$l = strlen($time_string);
			switch ($l) {
				case 6:
					list($hour, $minute, $second) = sscanf($time_string, "%2s%2s%2s");
					break;
				case 4:
					list($hour, $minute) = sscanf($time_string, "%2s%2s");
					break;
				case 2:
					$hour = $time_string;
					break;
				default:
					return false;
			}
		}
		else {
			@list($hour, $minute, $second) = explode($separator, $time_string);
			$hour   = trim($hour);
			$minute = trim($minute);
			$second = trim($second);
		}
		if ($date) $date .= " ";
		$date = $hour ? "$date" . sprintf("%02d", $hour) : $date;
		$date = $minute ? "$date:" . sprintf("%02d", $minute) : $date;
		$date = $second ? "$date:" . sprintf("%02d", $second) : $date;
	}
	return $date;
}

/**
 * @return string
 * @param string $isodate
 * @desc Returns the type of the date. The date should be in ISO format.
 */
function date_type($isodate)
{
	if (substr_count($isodate, ':') == 2) return NDT_SECOND;
	else if (substr_count($isodate, ':') == 1) return NDT_MINUTE;
	else if (substr_count($isodate, ' ') == 1) return NDT_HOUR;
	else if (substr_count($isodate, '-') == 2) return NDT_DAY;
	else if (substr_count($isodate, 'W') == 1) return NDT_WEEK;
	else if (substr_count($isodate, '-') == 1) return NDT_MONTH;
	else if ((substr_count($isodate, '-') == 0) && (strlen($isodate) > 2)) return NDT_YEAR;
	return false;
}

/**
 * @param $date_type
 * @return mixed
 */
function date_type_format($date_type)
{
	$conversion = [
		NDT_SECOND => NDF_DATETIME,
		NDT_MINUTE => NDF_MINUTE,
		NDT_HOUR   => NDF_HOUR,
		NDT_DAY    => NDF_DATE,
		NDT_WEEK   => NDF_WEEK,
		NDT_MONTH  => NDF_MONTH,
		NDT_YEAR   => NDF_YEAR
	];
	return $conversion[$date_type];
}

/**
 * @return string
 * @param string $isotime
 * @desc Returns the type of the time. The time should be with ':' separators.
 */
function time_type($isotime)
{
	if (substr_count($isotime, ':') == 2) return NDT_SECOND;
	else if (substr_count($isotime, ':') == 1) return NDT_MINUTE;
	else if ((substr_count($isotime, ':') == 0) && (strlen($isotime) > 1)) return NDT_HOUR;
	return false;
}

/**
 * @param $start_date
 * @param $end_date
 * @param string $period
 * @return float
 */
function date_subtract($start_date, $end_date, $period = 's')
{
	$period_duration = ['s' => 1,
	                         'm' => 60,
	                         'h' => 60 * 60,
	                         'd' => 60 * 60 * 24];
	if (!is_integer($start_date)) $start_date = strtotime($start_date);
	if (!is_integer($end_date)) $end_date = strtotime($end_date);
	$diff = (($end_date - $start_date) / $period_duration[$period]);
	return $diff;
}

/**
 * @param string $time
 * @return int
 */
function time_to_seconds($time = '00:00:00')
{
	if (!$time) {
		return 0;
	}
	list($hours, $minutes, $seconds) = explode(':', $time);
	return ((int)$hours * 3600) + ((int)$minutes * 60) + (int)$seconds;
}

/**
 * @param string $month
 * @param string $year
 * @return bool|string
 */
function days_in_month($month = '', $year = '')
{
	if (preg_match('/^\d{4}-\d{2}[-]*\d{0,2}$/', $month)) {
		/** @noinspection PhpUnusedLocalVariableInspection */
		@list($year, $month, $day) = explode('-', $month);
		$number_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
	}
	elseif ($month <= 12 && !$year) {
		$number_of_days = date('t', mktime(0, 0, 0, $month, 1, date('Y')));
	}
	else $number_of_days = false;

	return $number_of_days;
}

/**
 *  Strips off seconds and removes the date part of event ending timestamp, if it ends within the same day.
 *  Returns e.g "2013-06-27 13:00 - 14:00" for ("2013-06-27 13:00", "2013-06-27 14:00")
 *
 * @param $time_start
 * @param $time_end
 * @return string
 */
function format_reservation_event_start_and_end_time($time_start, $time_end)
{
	// Get event starting and ending date and times + strip off seconds from times
	list($start_day, $start_time) = explode(' ', $time_start);
	list($end_day, $end_time) = explode(' ', $time_end);

	// Strip off seconds
	$start_time = substr($start_time, 0, -3);
	$end_time = substr($end_time, 0, -3);

	// If event is set to end in within the same day, then don't display date twice
	if ($end_day == $start_day) $end = $end_time;
	else $end = "$end_day $end_time";

	return "$start_day $start_time - $end";
}

/**
 * Wrapper for format_reservation_event_start_and_end_time
 * @param $event
 * @return string
 */
function format_reservation_event_start_and_end_time_from_array($event)
{
	return format_reservation_event_start_and_end_time($event['time_start'], $event['time_end']);
}
