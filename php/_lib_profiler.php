<?php

/**
 * @param bool|string $message
 * @param bool $return_log
 * @return array
 */
function log_time($message = false, $return_log = false)
{
	static $start = 0;
	static $previous = 0;
	static $log;

	$trace = debug_backtrace();
	$call = @$trace[1];
	$function = $call['function'];
	$class = (@$call['class'] ? $call['class'] . '::' : '');
	if ($class && $function == '__call') $function = $call['args'][0];
	$prefix = $class . $function;

	if (!in_array($prefix, ['include', 'include_once', 'require', 'require_once']))
		$message = $prefix . '() ' . $message;

	if (!$start) {
		$start = microtime(true);
		$previous = 0;

		$entry['time'] = 0;
		$entry['difference'] = 0;
		$entry['message'] = "Starting time logging";
	}
	else {
		$time = microtime(true) - $start;
		$difference = $time - $previous;
		$previous = $time;
		//$entry = sprintf("%0.2f: delta %0.2f - $message", $time*1000, $difference*1000);
		$entry['time'] = sprintf("%0.2f", $time * 1000);
		$entry['difference'] = sprintf("%0.2f", $difference * 1000);
		$entry['message'] = $message;
		if (class_exists('Nexus') && Nexus::$db) {
			$entry['sql_count'] = Nexus::$db->count;
			$entry['sql_time'] = sprintf("%0.2f", Nexus::$db->time * 1000);
		}

	}
	$entry['memory'] = memory_get_usage();

	$log[] = $entry;
	if ($return_log) return $log;
	else return $entry;
}
function pretty_time_log($entries)
{
	$header_template = "% 10s % 8s % 8s % 10s % 10s | %s\n";
	$template        = "% 10s % 8s % 6dKB % 10s % 10s | %s\n";
	$s = sprintf($header_template, 'Time', 'Delta', 'Memory', 'SQL Count', 'SQL Time', 'Message');
	foreach ($entries as $entry)
		$s .= sprintf($template,
		              $entry['time'],
		              $entry['difference'],
		              round($entry['memory'] / 1000),
		              @$entry['sql_count'],
		              @$entry['sql_time'],
		              $entry['message']);
	return $s;
}

function caller_function()
{
	$trace = debug_backtrace(false);
	/** @noinspection PhpUnusedLocalVariableInspection */
	$caller = array_shift($trace); // this function
	/** @noinspection PhpUnusedLocalVariableInspection */
	$caller = array_shift($trace); // this function called by
	$caller = array_shift($trace); // calling function is called by
	return (@$caller['class'] ? $caller['class'] . '::' : '') . $caller['function'];
}