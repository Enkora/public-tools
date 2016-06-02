<?php

function parse_db_type($s)
{
	return sscanf($s, "%[^(](%d) %s");
}

/**
 * Make where array for a SQL query
 *
 * If $insert_values - $named_values is not modified
 *
 * @param array|stdClass $named_values
 * @param array|boolean $allowed_keys list of allowed keys
 * @param boolean $insert_values
 * @param bool $return_empty If enabled, then "WHERE $where AND something => WHERE 1 AND something"
 * @return array|string
 * @throws Exception
 */
function make_and_conditions(&$named_values, $allowed_keys = false, $insert_values = false, $return_empty = false)
{
	// "WHERE $where AND something => WHERE 1 AND something"
	if (!$named_values) return $return_empty ? ' 1 ' : '';

	$conditions = [];
	$tmp_named_values = $named_values;
	foreach ($tmp_named_values as $key => $value) {
		if (!$key) continue;

		// prevent SQL injections if no $allowed_keys is explicitly defined
		if (!$allowed_keys && !preg_match("/^[a-zA-Z0-9\.\_\-]+$/", $key))
			throw new Exception("make_and_conditions key value ${key} contains invalid characters");

		if (((is_scalar($value) || is_null($value)) && (string)$value == '')
				|| ($allowed_keys && !in_array($key, $allowed_keys))) {
			// if value is null, empty, or not in allowed_keys (where present)
			if (!$insert_values) unset($named_values[$key]);
		}
		elseif (is_array($value)) {
			// @todo $value should not be an array of arrays!
			if (list_to_string($value, ',')) {
				$values = db_quote($value);

				$conditions[] = "$key IN (" . list_to_string($values, ',') . ")";
			}
			unset($named_values[$key]);
		}
		else {
			if ($insert_values) {
				// do not modify original array
				$conditions[] = "$key = " . db_quote($value);
			} else {
				// modify array
				$param_name = $key;
				if (strpos($param_name, '.') !== false) {
					$param_name = str_replace('.', '_', $param_name);

					$named_values[$param_name] = $value;
					unset($named_values[$key]);
				}
				$conditions[] = "$key = :$param_name";
			}
		}
	}
	$conditions = list_to_string($conditions, ' AND ');

	// "WHERE $where AND something => WHERE 1 AND something"
	if (!$conditions && $return_empty) return ' 1 ';

	return $conditions;
}

/**
 * @param array $array
 * @param string|array $fields
 * @param bool $preserve_keys
 * @return void
 *
 * Array passed as reference to avoid creating redundant duplicate copies of data. Will remove array keys.
 */
function multisort(&$array, $fields, $preserve_keys = true)
{
	if (!$fields) return;
	if (!is_array($fields)) $fields = [$fields];
	$comparisons = [];
	foreach ($fields as $field) {
		if (is_string($field)) {
			$key   = $field;
			$order = true;
			$type  = 4;
		}
		else {
			$key = $field[0];
			if (isset($field[1])) $order = $field[1];
			else $order = true;
			if (isset($field[2])) $type = $field[2];
			else $type = 4;
		}
		if (is_string($key)) $key = '\'' . $key . '\'';
		$a = 'strip_tags(@$a[' . $key . '])';
		$b = 'strip_tags(@$b[' . $key . '])';

		switch ($type)
		{
			case 1: // Case insensitive natural.
				$t = "strcasecmp($a, $b)";
				break;
			case 2: // Numeric.
				$t = "($a == $b) ? 0:(($a < $b) ? -1 : 1)";
				break;
			case 3: // Case sensitive string.
				$t = "strcmp($a, $b)";
				break;
			case 4: // Case insensitive string.
				$t = "strcasecmp($a, $b)";
				break;
			default: // Case sensitive natural.
				$t = "strnatcmp($a, $b)";
				break;
		}

		$comparisons[] = '$r = ' . ($order ? '' : '-') . '(' . $t . ');';
	}

	//$comparisons = array_reverse($comparisons);

	$f = '';
	foreach ($comparisons as $comparison) {
		$f .= $comparison . "\n";
		$f .= 'if ($r) return $r;' . "\n";
	}
	$f .= 'return $r;';

	if ($preserve_keys) uasort($array, create_function('$a, $b', $f));
	else usort($array, create_function('$a, $b', $f));
}

/**
 * @param mixed|array$values
 * @return array|int|string
 */
function db_quote($values)
{
	if (class_exists('Nexus'))
		/** @noinspection PhpUndefinedClassInspection */
		$r = Nexus::$db->quote($values);
	else {
		if (is_array($values)) {
			$r = [];
			foreach ($values as $key => $value) $r[$key] = db_quote($value);
		}
		elseif (is_numeric($values)) $r = $values;
		else $r = "'" . addslashes($values) . "'";
	}
	return $r;
}

/**
 * Generates SQL for going an SQL equivalent for explode($splitter, $value)[$item]
 * @param string $fieldname
 * @param int $item index of returned part (starting from 1)
 * @param string $splitter
 * @return string
 */
function mysql_explode_get_sql($fieldname, $item, $splitter)
{
	$splitter_escaped = db_quote($splitter);
	$item = (int) $item;
	if (strpos($fieldname, '.') === false) $fieldname = "`$fieldname`";
	$sql = "REPLACE(
					SUBSTRING(SUBSTRING_INDEX($fieldname, $splitter_escaped, $item),
								LENGTH(SUBSTRING_INDEX($fieldname, $splitter_escaped, $item - 1)) + 1),
					'$splitter', '')";
	return $sql;
}