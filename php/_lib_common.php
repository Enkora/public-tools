<?php

define('SCRIPT_ROOT', substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], '/index.php')));

/**
 * Convert a list array into a string of comma-separated values
 * @param array   $list
 * @param string  $separator
 * @param boolean $include_empty
 * @param string  $fill_value
 * @return string
 */
function list_to_string($list, $separator = '', $include_empty = false, $fill_value = '')
{
	if (is_array($list)) {
		$list_string = '';
		foreach ($list as $value) {
			if (is_array($value)) $value = 'array(' . pairs_to_string($value, '=', ', ', '', true) . ')';
			elseif (is_object($value)) {
				if (method_exists($value, '__toString')) $value = (string)$value;
				else $value = 'object ' . get_class($value);
			}
			else $value = (string)$value;

			if ($value !== '') {
				$list_string .= "$value$separator";
			}
			elseif ($include_empty) {
				$value = $value ? $value : $fill_value;
				$list_string .= "$value$separator";
			}
		}
		$separator_length = strlen($separator);
		if ($separator_length && $separator_length < strlen($list_string))
			$list_string = substr($list_string, 0, -$separator_length); // remove the last separator
	}
	else $list_string = $list;
	return $list_string;
}

/**
 * Convert associative array to string e.g.: "a=1, b=2, c=3'. Strings have to be already quoted if used for SQL query.
 * @return string
 * @param string|int|array|stdClass $list
 * @param string                    $separator1
 * @param string                    $separator2
 * @param string                    $value_enclosure
 * @param boolean                   $include_empty
 */
function pairs_to_string($list, $separator1 = '', $separator2 = ',',
                         $value_enclosure = '', $include_empty = false)
{
	if (is_scalar($list)) return (string)$list;
	elseif (!$list) return '';
	elseif (is_object($list)) $list = get_object_vars($list);
	$list_string = '';
	$is_assoc = is_assoc($list);
	foreach ($list as $key => $value) {
		if (!is_scalar($value) && !is_null($value)) {
			$type = gettype($value);
			if ($type == 'array') $type = '';
			$value = "{$type}[" . pairs_to_string($value, ': ', ', ', $value_enclosure, $include_empty) . "]";
		}

		$key_string = $is_assoc ? $key . $separator1 : '';
		if ($include_empty) $list_string .= $key_string . $value_enclosure . $value . $value_enclosure . $separator2;
		else $list_string .=
				((string)$value === '')
						? ""
						: $key_string . $value_enclosure . $value . $value_enclosure . $separator2;
	}
	$list_string = substr($list_string, 0, -strlen($separator2)); // remove the last separator
	return $list_string;
}

/**
 * print_r wrapper
 * @param $var
 * @return mixed
 */
function v($var /*, $separator="; "*/)
{
	/*Zend_Log::log(print_r($var,true), Zend_Log::LEVEL_DEBUG );
	if ((string) $var == '') return '';
	if (is_object($var)) $var=get_object_vars($var);
	if (is_array($var)) return pairs_to_string($var,' = ',$separator,'"',true);
	else return (string) $var;*/
	return print_r($var, true);
}

/**
 * @param mixed $a
 * @param bool $include_empty
 * @param string $separator1
 * @param string $separator2
 * @return string
 */
function p2s($a, $include_empty = false, $separator1 = ' = ', $separator2 = '; ')
{
	if (!is_array($a) && !is_object($a)) return $a;
	return pairs_to_string($a, $separator1, $separator2, false, $include_empty);
}

/**
 * @param object $object
 * @return array
 * @throws Exception
 */
function get_visible_vars($object)
{
	if (!is_object($object)) throw new Exception('Supplied parameter is not an object!');
	$r = [];
	foreach ($object as $fieldname => $value) {
		$r[$fieldname] = $value;
	}
	return $r;
}


/**
 * Maps function to all elements of $data. Modified $data.
 * $function() should take parameters in the following way: row, parameters
 *
 * @param callable $function
 * @param array $data
 * @param mixed $_,...
 * @throws Exception
 */
function map($function, &$data, $_ = null)
{
	if (!is_callable($function)) throw new Exception("Function '$function' does not exist!");
	$args = func_get_args();
	foreach ($data as $key => $row) {
		if (isset($args[2][$key]) && is_array($args[2][$key]))
			$data[$key] = call_user_func($function, $row, $args[2][$key],
			                             @$args[3], @$args[4], @$args[5], @$args[6], @$args[7], @$args[8], @$args[9]);
		else
			$data[$key] = call_user_func($function, $row, @$args[2],
			                             @$args[3], @$args[4], @$args[5], @$args[6], @$args[7], @$args[8], @$args[9]);
	}
//	return $data;
}

/**
 * @param int|string $a
 * @param int|string $b
 * @return mixed
 */
function add($a, $b)
{
	return $a + $b;
}

/**
 * @param array $a
 * @param array $b
 * @param callable $function
 * @return array
 */
function merge_assoc(array $a, array $b, $function)
{
	foreach ($b as $key => $value) {
		$a[$key] = $function(@$a[$key], $b[$key]);
	}
	return $a;
}

/**
 * Returns a column from associative array
 *
 * @param array   $data
 * @param string  $column_name
 * @param boolean $distinct
 * @return array
 */
function get_column(array &$data, $column_name, $distinct = false)
{
	$column = [];
	foreach ($data as $key => $row) {
		$value = is_object($row) ?
				(property_exists($row, $column_name) ? $row->$column_name : null) :
				(array_key_exists($column_name, $row) ? $row[$column_name] : null);

		if ($distinct) $column[$value] = $value;
		else $column[$key] = $value;
	}
	
	return $column;
}

/**
 * Modifies data
 * @param array  $data
 * @param string $column_name
 * @param string $new_column
 * @return mixed
 */
function set_column(&$data, $column_name, $new_column)
{
	foreach ($data as $key => $row) {
		if (isset($new_column[$key])) {
			if (is_object($row)) $data[$key]->$column_name = $new_column[$key];
			else $data[$key][$column_name] = $new_column[$key];
		}
	}
}

/**
 * Returns a true if all column values equal to $value. $table is an array of named column arrays
 * @return boolean
 * @param array  $table
 * @param string $column_name
 * @param mixed  $value
 */
function column_is(array &$table, $column_name, $value = false)
{
	reset($table);
	$first_row = current($table);

	$value = ($value === false) ? @$first_row[$column_name] : $value;
	foreach ($table as $row)
		if ((string)@$row[$column_name] != (string)$value) return false;
	return true;
}

/**
 * Returns a maximum value in a column of an associative 2-dimensional array
 * @return int
 * @param array  $table
 * @param string $column_name
 * @param int    $base_value
 */
function max_in_column(array &$table, $column_name, $base_value = 0)
{
	$result = $base_value;
	foreach ($table as $item) $result = $item[$column_name] > $result ? (int)$item[$column_name] : $result;
	return $result;
}

/**
 * @return int
 * @param array  $table
 * @param string $column_name
 * @param int    $base_value
 * @desc Returns a minimum value in a column of an associative 2-dimensional array
 */
function min_in_column(array &$table, $column_name, $base_value = ENK_MAXINT)
{
	$result = $base_value;
	foreach ($table as $item) $result = $item[$column_name] < $result ? $item[$column_name] : $result;
	return $result;
}

/**
 * compare 1st param to the length of a string, and return max
 * @param int   $length
 * @param mixed $string2
 * @return mixed
 */
function mb_strlen_max($length, $string2)
{
	return max($length, mb_strlen((string)$string2));
}

/**
 * @param array    $table
 * @param string   $column_name
 * @param callable $function
 * @param mixed    $initial
 * @return mixed
 */
function array_reduce_column(array $table, $column_name, $function, $initial = null)
{
	$result = $initial;
	foreach ($table as $item) {
		$result = $function($result, $item[$column_name]);
	}
	return $result;
}

/**
 * @param array $r
 * @return mixed|null
 */
function array_first_key(array $r)
{
	if (count($r) == 0) return null; // throw new Exception("Cannot return first key of empty array!");
	reset($r);
	return key($r);
}

/**
 * @param array $r
 * @return mixed|null
 */
function array_last_key(array $r)
{
	if (count($r) == 0) return null; //throw new Exception("Cannot return last key of empty array!");
	/*$k = array_keys($r);
	return $k[count($k)-1];*/
	end($r);
	return key($r);
}

/**
 * Renames a key of an associative array. WARNING: order is lost. if old key does not exist, no new key is created
 * @param array      $array
 * @param string|int $old_key
 * @param string|int $new_key
 */
function array_rename_key(&$array, $old_key, $new_key)
{
	if (array_key_exists($old_key, $array)) {
		$array[$new_key] = $array[$old_key];
		unset($array[$old_key]);
	}
}

/**
 * @param array      $array
 * @param array|bool $white_list
 * @param array|bool $black_list
 */
function array_filter_keys(&$array, $white_list = [], $black_list = [])
{
	if ($white_list && is_array($white_list)) {
		foreach ($array as $key => $value) {
			if (!in_array($key, $white_list)) unset($array[$key]);
		}
	}

	if ($black_list && is_array($black_list)) {
		foreach ($array as $key => $value) {
			if (in_array($key, $black_list)) unset($array[$key]);
		}
	}
}

/*function urlencode_array($data, $name, $separator = '&')
{
	$tmp = array();
	foreach ($data as $key => $value) {
		if (is_array($value)) {
			$tmp[] = urlencode_array($value, "{$name}[{$key}]", $separator);
		} else {
			$tmp[] = "{$name}[{$key}]=".urlencode($value);
		}
	}
	return implode($separator, $tmp);
}*/

/**
 * @param array  $data
 * @param bool   $parent_name
 * @param string $separator
 * @return string
 */
function urlencode_array($data, $parent_name = false, $separator = '&')
{
	$tmp = [];
	foreach ($data as $key => $value) {
		if ($parent_name !== false) $name = "{$parent_name}[{$key}]";
		else $name = $key;
		if (is_numeric($name)) $name = "var$name";
		//echo "parent: $parent_name; key: $key; name: $name<br />";
		if (is_array($value)) {

			$tmp[] = urlencode_array($value, $name, $separator);
		}
		else {
			$tmp[] = "$name=" . urlencode($value);
		}
	}
	return implode($separator, $tmp);
}

/**
 * @param int $columns
 * @param int $rows
 * @return array
 */
function get_column_lengths($columns, $rows)
{
	$base = floor($rows / $columns);

	$remainder = $rows % $columns;

	$r = [];

	for ($i = 1; $i <= $columns; $i++) {
		if ($i <= $remainder) $r[$i] = $base + 1;
		else $r[$i] = $base;
	}
	return $r;
}

function generate_password($syllables = 3, $use_prefix = false)
{
	// Define function unless it is already exists
	if (!function_exists('ae_arr')) {
		// This function returns random array element
		function ae_arr(&$arr)
		{
			return $arr[rand(0, sizeof($arr) - 1)];
		}
	}

	// 20 prefixes
	$prefix = ['aero', 'anti', 'auto', 'bi', 'bio',
	                'cine', 'deca', 'demo', 'dyna', 'eco',
	                'ergo', 'geo', 'gyno', 'hypo', 'kilo',
	                'mega', 'tera', 'mini', 'nano', 'duo'];

	// random suffixes
	$suffix = ['maki', 'ja', 'inen', 'us', 'ri', 'i', 'ka', 'in'];

	// vowel sounds
	$vowels =
			['a', 'o', 'e', 'i', 'y', 'u', 'uu', 'oi', 'ie', 'yy', 'ui', 'ai', 'ei', 'eu', 'ou', 'aa', 'uo', 'ii'];

	// random consonants
	$consonants = ['r', 't', 'p', 's', 'g', 'h', 'j', 'k', 'l', 'n', 'm'];

	$password = $use_prefix ? ae_arr($prefix) : '';

	$password_suffix = ae_arr($suffix);

	for ($i = 0; $i < $syllables; $i++) {
		// selecting random consonant
		$doubles = ['k', 't', 's', 'n', 'l', 'm', 'p'];
		$c = ae_arr($consonants);
		if (in_array($c, $doubles) && ($i != 0)) { // maybe double it
			if (rand(0, 2) == 1) // 33% probability
				$c .= $c;
		}
		$password .= $c;
		//

		// selecting random vowel
		$password .= ae_arr($vowels);

		if ($i == $syllables - 1) // if suffix begin with vovel
			if (in_array($password_suffix[0], $vowels)) // add one more consonant
				$password .= ae_arr($consonants);

	}

	// selecting random suffix
	$password .= $password_suffix;

	return $password;
}

/**
 * @param $value
 * @return string
 */
function bcd_encode($value)
{
	if (strlen($value) & 1) $value = '0' . $value;
	return pack('H*', $value);
}

/**
 * @param $value
 * @return mixed
 */
function bcd_decode($value)
{
	$r = unpack('H*result', $value);
	return $r['result'];
}

/**
 * @param $reference_id
 * @return int
 */
function calculateInvoiceChecksum($reference_id)
{
	$coefficient = [7, 3, 1];
	$numbers = array_reverse(str_split($reference_id));

	for ($i = $total = 0; $i < count($numbers); $i++)
		$total += $numbers[$i] * $coefficient[$i % 3];

	$verification = (10 - ($total % 10)) % 10;
	return $verification;
}

/**
 * Made originally for CTA purposes
 *
 * @param array $address_parts
 * @return string
 */
function generateAddressString($address_parts = [])
{
	return trim(
			(@$address_parts['_address_co'] ? 'C/O ' . $address_parts['_address_co'] . "\n" : "") .
			(@$address_parts['_address_pl'] ?
					'PL ' . preg_replace("/^PL[ ]*/i", "", $address_parts['_address_pl']) . "\n" :
					"") .
			(@$address_parts['_address_street'] ? $address_parts['_address_street'] . "\n" : "") .
			(@$address_parts['_address_postcode'] || @$address_parts['_address_street'] ?
					trim(@$address_parts['_address_postcode'] . ' ' . @$address_parts['_address_city']) . "\n" : ""));
}

/**
 * Converts n-dimensional array to XML (SimpleXMLElement)
 * Use $element_numbers = true to rename e.g LineItem_123 to LineItem
 *
 * @param array            $array
 * @param boolean          $name
 * @param SimpleXMLElement $xml
 * @param boolean          $element_numbers
 * @param bool             $allow_attributes
 * @return SimpleXMLElement $xml
 */
function array_to_simple_xml(&$array, $name = false, &$xml = null, $element_numbers = false, $allow_attributes = false)
{
	if (is_null($xml)) {
		if (!$name) $name = 'body';
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?><$name/>");
	}

	foreach ($array as $key => $value) {
		$attributes = [];

		if (is_array($value)) {
			if (is_numeric($key)) $key = "element";
			if ($element_numbers) $key = preg_replace('/_([0-9])+/', '', $key);
			$child = $xml->addChild($key);
			array_to_simple_xml($value, $key, $child, $element_numbers, $allow_attributes);
		}
		else {
			if (is_numeric($key)) {
				$name = $key;
				$key = "value";
			}
			else $name = false;
			if (!$key) $key = 'null';
			elseif ($allow_attributes) {
				$list = explode(' ', $key);
				$key = array_shift($list);
				foreach ($list as $attribute) {
					list($attribute_name, $attribute_value) = explode('=', $attribute);
					$attributes[trim($attribute_name)] = trim($attribute_value, " \"");
				}
			}
			else $key = str_replace(' ', '_', $key);
			$child = $xml->addChild($key, str_replace('&', '&amp;', $value));
			/** @var $child SimpleXMLElement */
			if ($name) $child->addAttribute('key', $name);
			if ($attributes) {
				foreach ($attributes as $attribute_name => $attribute_value) {
					$child->addAttribute($attribute_name, $attribute_value);
				}
			}
		}
	}
	return $xml;
}

function getProtocol()
{
	if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) $protocol = 'https://';
	else $protocol = "http://";

	return $protocol;
}

function getBaseUrl($force_https = false)
{
	if ($force_https) return 'https://' . $_SERVER['HTTP_HOST'];
	else return getProtocol() . $_SERVER['HTTP_HOST'];
}

/**
 *
 * Unzips a ZIP archive $zip to folder $folder
 *
 * @param $zip    string name of the ZIP archive
 * @param $folder string Destination folder
 * @throws Exception
 * @return array List of extracted files
 */
function unzip($zip, $folder)
{
	$zip = zip_open($zip);
	if (!is_resource($zip)) throw new Exception('Could not open ZIP file');

	$files = [];

	while ($zip_entry = zip_read($zip)) {
		$filename = zip_entry_name($zip_entry);

		// Create sub folders
		if (strpos($filename, '/') !== false) {
			$elements = explode('/', $filename);
			array_pop($elements);
			$new_folder = implode('/', $elements);
			if (!is_dir($folder . '/' . $new_folder))
				mkdir($folder . '/' . $new_folder, 0777, true);
		}
		$files[] = $folder . '/' . $filename;

		$fp = fopen($folder . '/' . $filename, 'w');

		if (zip_entry_open($zip, $zip_entry, 'r')) {
			fwrite($fp, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
			zip_entry_close($zip_entry);
			fclose($fp);
		}
	}
	zip_close($zip);

	return $files;
}

/**
 * assemble url out of parts and strip extra slashes (except the first one)
 * @param array|string $url_parts
 * @param string $_,... more url parts
 * @return string
 */
function url_combine($url_parts, $_ = '')
{
	if (!is_array($url_parts)) $url_parts = func_get_args();
	$first = true;
	$last_key = array_last_key($url_parts);
	foreach ($url_parts as $key => &$url_part) {
		if ($first && $key == $last_key) {
			$url_part = trim($url_part, " \n");
		}
		elseif ($first) {
			$url_part = trim($url_part, " \n");
			$url_part = rtrim($url_part, "/");
		}
		elseif ($key == $last_key) {
			$url_part = trim($url_part, " \n");
			$url_part = ltrim($url_part, "/");
		}
		else {
			$url_part = trim($url_part, "/ \n");
		}
		if (!$url_part) unset($url_parts[$key]);
		$first = false;
	}
	$result = implode('/', $url_parts);
	return $result;
}


/**
 * assemble url out of parts and strip extra slashes (except the first one)
 * @param array|string $path_parts
 * @param string $_,... more parts
 * @return string
 */
function path_combine($path_parts, $_ = '')
{
	if (!is_array($path_parts)) $path_parts = func_get_args();
	$first = true;
	$last_key = array_last_key($path_parts);
	foreach ($path_parts as $key => &$url_part) {
		if ($first && $key == $last_key) {
			$url_part = trim($url_part, " \n");
		}
		elseif ($first) {
			$url_part = trim($url_part, " \n");
			$url_part = rtrim($url_part, DIRECTORY_SEPARATOR);
		}
		elseif ($key == $last_key) {
			$url_part = trim($url_part, " \n");
			$url_part = ltrim($url_part, DIRECTORY_SEPARATOR);
		}
		else {
			$url_part = trim($url_part, DIRECTORY_SEPARATOR . " \n");
		}
		if (!$url_part) unset($path_parts[$key]);
		$first = false;
	}
	$result = implode(DIRECTORY_SEPARATOR, $path_parts);
	return $result;
}
/**
 *
 * Usage e.g getTempFile(), getTempFile('jpg'), getTempFile('test.jpg'), getTempFile('pictures/test.jpg')
 *
 * @param string $filename_or_extension
 * @param bool   $create_file
 * @throws Exception
 * @return string
 */
function getTempFile($filename_or_extension = '', $create_file = false)
{
	$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
	if (!is_writable($path)) throw new Exception("Temp path $path not writable!");

	$parts = explode('/', $filename_or_extension);
	if (count($parts) > 2) throw new Exception("Recursive creation of sub directories not supported");

	// Create sub folder if provided and does not already exist
	if (count($parts) == 2) {
		$sub_folder = $parts[0];
		if (!is_dir($path . $sub_folder)) {
			$result = mkdir($path . $sub_folder, 0777, true);
			if (!$result) throw new Exception('Could not create folder ' . $path . $sub_folder);
		}
		$path .= $sub_folder . '/';
		$filename_or_extension = $parts[1];
	}
	else $filename_or_extension = @$parts[0];

	// If a dot is found in the parameter, then the parameter is the full filename
	if (strpos($filename_or_extension, '.') !== false) {
		$filename = $path . $filename_or_extension;
	}
	// Otherwise create a unique filename (with extension, if provided)
	else {
		$filename = $path . uniqid(rand() . session_id(), true);
		if ($filename_or_extension) $filename = $filename . '.' . $filename_or_extension;
	}

	if ($create_file) {
		if (is_file($filename)) throw new Exception("File $filename already exists!");
		$fp = fopen($filename, 'w');
		fclose($fp);
	}

	return $filename;
}

/**
 * Indents a flat JSON string to make it more human-readable.
 * @param string $json The original JSON string to process.
 * @return string Indented version of the original JSON string.
 */
function json_format($json)
{
	$result = '';
	$pos = 0;
	$strLen = strlen($json);
	$indentStr = '   ';
	$newLine = "\n";
	$prevChar = '';
	$outOfQuotes = true;

	for ($i = 0; $i <= $strLen; $i++) {

		// Grab the next character in the string.
		$char = substr($json, $i, 1);

		// Are we inside a quoted string?
		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;

			// If this character is the end of an element,
			// output a new line and indent the next line.
		}
		else if (($char == '}' || $char == ']') && $outOfQuotes) {
			$result .= $newLine;
			$pos--;
			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		// Add the character to the result string.
		$result .= $char;

		// If the last character was the beginning of an element,
		// output a new line and indent the next line.
		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
			$result .= $newLine;
			if ($char == '{' || $char == '[') {
				$pos++;
			}

			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		$prevChar = $char;
	}

	return $result;
}

/**
 * @param      $filename
 * @param bool $fallback
 * @return bool|string
 */
function getMimeType($filename, $fallback = true)
{
	if (function_exists('finfo_file')) {
		$mime = finfo_open(FILEINFO_MIME_TYPE);
		return finfo_file($mime, $filename);
	}
	elseif (function_exists('mime_content_type')) return mime_content_type($filename);
	elseif ($fallback) return 'application/octet-stream'; // Fallback (suggested in RFC2616)
	else return false;
}

/**
 * Multibyte safe version of str_pad (https://gist.github.com/226350)
 *
 * @param        $input
 * @param        $pad_length
 * @param string $pad_string
 * @param int    $pad_type
 * @return string
 */
function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
{
	$diff = strlen($input) - mb_strlen($input);
	return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
}

/**
 * Return only whitelisted array elements
 *
 * @param array $array
 * @param array $whitelist
 * @return array
 */
function array_whitelist($array, $whitelist = [])
{
	if (!is_array($whitelist) && is_string($whitelist)) $whitelist = [$whitelist];
	else if (!is_array($whitelist)) return [];

	return array_intersect_key($array, array_flip($whitelist));
}

/**
 * Add a key value pair to the beginning of an array
 *
 * @param array $array
 * @param string $key
 * @param mixed $value
 */
function array_unshift_assoc(&$array, $key, $value = '')
{
	if (is_array($key)) {
		$value = reset($key);
		$key = array_first_key($key);
	}
	// Reverse the array
	$array = array_reverse($array, true);
	// Add to the end of the array
	$array[$key] = $value;
	// Reverse again
	$array = array_reverse($array, true);
}

/**
 * @param array $array
 * @param array $values_to_remove
 */
function array_remove_by_value(&$array, $values_to_remove = [])
{
	if (!is_array($values_to_remove)) $values_to_remove = [$values_to_remove];
	foreach ($values_to_remove as $value) {
		$key = array_search($value, $array);
		if ($key !== false) unset($array[$key]);
	}
}

/**
 * Pluck array values by key and return an array
 *
 * @param string $key Key
 * @param array $input
 * @return array
 */
function array_pluck($key, $input)
{
	if (is_array($key) || !is_array($input)) return [];
	$array = [];
	foreach ($input as $v) {
		if(array_key_exists($key, $v)) $array[]=$v[$key];
	}
	return $array;
}

/**
 * Calculate standard deviation for array of values
 *
 * @param array $aValues
 * @param bool $bSample
 * @return float
 */
function standard_deviation($aValues, $bSample = false)
{
	$fMean = array_sum($aValues) / count($aValues);
	$fVariance = 0.0;
	foreach ($aValues as $i) {
		$fVariance += pow($i - $fMean, 2);
	}
	$fVariance /= $bSample ? count($aValues) - 1 : count($aValues);
	return (float)sqrt($fVariance);
}

/**
 * Generate a checksum for a luhn number
 *
 * OBS! Do not use this for generating the last number, instead use calculate_luhn
 *
 * @param string $input
 * @return int Checksum
 */
function luhn_checksum($input)
{
	if (!is_numeric($input))
		$input = preg_replace("/\D/", "", $input);

	$input = (string)$input;

	$sum = 0;
	$odd = strlen($input) % 2;

	// Calculate sum of digits.
	for ($i = 0; $i < strlen($input); $i++) {
		$sum += $odd ? $input[$i] : (($input[$i] * 2 > 9) ? $input[$i] * 2 - 9 : $input[$i] * 2);
		$odd = !$odd;
	}

	// Check validity.
	return ($sum % 10);
}

/**
 * Check if string is valid luhn number
 *
 * @param string $input
 * @return bool True if valid
 */
function is_luhn_valid($input)
{
	return luhn_checksum($input) == 0 ? true : false;
}

/**
 * Calculate the luhn checksum number
 *
 * @param $partial_card_number
 * @return int The checksum number
 */
function calculate_luhn($partial_card_number)
{
	$check_digit = luhn_checksum((int) $partial_card_number * 10);
	if ($check_digit == 0) {
		return $check_digit;
	} else {
		return 10 - $check_digit;
	}
}

/**
 * @param array $trace
 * @return string
 */
function format_trace($trace)
{
	$trace_string = '';
	foreach ($trace as $key => $step) {
		//$arguments_string = implode(', ', $step['args']);
		//$arguments_string = list_to_string(@$step['args'], ', ', true);
		if (@$step['args']) $arguments_string = gettype($step['args']);
		else $arguments_string = '';
		$trace_string .= "#$key " . @$step['class'] . @$step['type'] . @$step['function']
						. "($arguments_string) called at [" . @$step['file'] . ":" . @$step['line'] . "]\n";
	}
	return $trace_string;
}

/**
 * Check if password is correct
 *
 * @param string $password Original password
 * @param string $hash Generated hash
 * @return bool True if password is correct, otherwise false
 */
function check_hashed_password($password, $hash)
{
	// first, compatibility mode: check md5
	if (md5($password) == $hash) {
		return true;
	}

	// second, check bcrypt
	if (password_verify($password, $hash) === true) {
		return true;
	}

	return false;
}

/**
 * Replace a column in array with another column, if another column is present
 *
 * Use case: replace name column with name_web column if (optional) name_web is present in fare products
 *
 * @param string $col_to_replace Col that is being replaced (e.g. name)
 * @param string $col_to_replace_with Col that contains the value that will be put into col_to_replace (e.g. name_web)
 * @param array $array Data array, e.g. mysql result set
 * @param bool $doReplace Commit replace, otherwise the col_to_replace_with is just being unset
 */
function array_override_column($col_to_replace, $col_to_replace_with, &$array, $doReplace = true)
{
	foreach ($array as $i => $v) {
		if ($doReplace && $doReplace === true
		    && isset($array[$i][$col_to_replace_with])
		    && !empty($array[$i][$col_to_replace_with])
		) {
			$array[$i][$col_to_replace] = $array[$i][$col_to_replace_with];
		}
		unset($array[$i][$col_to_replace_with]);
	}
}

/**
 * Check whether a string starts with a string
 *
 * @param string $haystack String to search from
 * @param string $needle String to search with
 * @return bool True if starts
 **/
function starts_with($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

/**
 * Array walk for recursively removing items
 *
 * @param array $array The input array.
 * @param callable $callback Function must return boolean value indicating whether to remove the node.
 * @param boolean $remove_empty_arrays remove empty root level arrays
 * @return array
 **/
function walk_recursive_remove (array $array, callable $callback, $remove_empty_arrays = false)
{
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			if (sizeof($v) > 0)
				$array[$k] = walk_recursive_remove($v, $callback);
			else
				if ($remove_empty_arrays) unset($array[$k]);
		} else {
			if ($callback($v, $k)) {
				unset($array[$k]);
			}
		}
	}

	return $array;
}

/**
 * A helper for HTML print_r
 *
 * @param mixed $r Any data
 **/
function pre_print_r ($r)
{
	print('<pre>');
	print_r($r);
	print('</pre>');
}

/**
 * A helper for HTML print_r
 *
 * @param mixed $r Any data
 **/
function pre_print_e ($r)
{
	print('<pre>');
	print_r($r);
	print('</pre>');
	exit;
}

/**
 * Html escape array
 *
 * @param array        $array  Data
 * @param string|array $fields Field(s) to escape, can be a string or array, if not set (null) then escape all
 * @return array
 */
function html_escape_array($array, $fields = null)
{
	if (!is_array($array)) return $array;

	foreach ($array as $k => $v) {
		if (!is_array($v)) continue;

		foreach ($v as $k2 => $v2) {
			if ($fields === null || $k2 == $fields || in_array($k2, $fields)) {
				$array[$k][$k2] = htmlspecialchars($v2);
			}
		}
	}

	return $array;
}

/**
 * The function accepts an integer and reverses it's bytes
 * @param string|int $num
 * @param int|bool       $number_of_bytes
 * @return number
 */
function change_byte_order($num, $number_of_bytes = false)
{
	$hex = dechex(0 + $num); // 0 + -> coercion to integer, seems to work better then casting (int)
	if (strlen($hex) <= 2) {
		return $num;
	}
	if (strlen($hex) % 2) $hex = '0' . $hex;
	if ($number_of_bytes) $hex = substr($hex, -($number_of_bytes * 2));
	$u = unpack("H*", strrev(pack("H*", $hex)));
	$r = hexdec($u[1]);
	return $r;
}


/**
 * The function accepts an integer and reverses it's bytes
 * @param string|int $num
 * @param int      $number_of_bytes
 * @return number
 */
function truncate_bytes($num, $number_of_bytes)
{
	$hex = dechex(0 + $num);
	if (strlen($hex) % 2) $hex = '0' . $hex;
	$hex = substr($hex, -($number_of_bytes * 2));
	return hexdec($hex);
}

/**
 * @param mixed $item
 * @param string $key
 */
function convert_json_data_type(&$item, /** @noinspection PhpUnusedParameterInspection */
                                $key)
{
	if (is_object($item)) {
		$item = get_object_vars($item);
		array_walk_recursive($item, 'convert_json_data_type');
	}

	if (
		// needs to be numeric
			is_numeric($item) &&

			// need to be less than JS max number
			(int)$item < 9007199254740992 &&

			// do not convert numbers that begin with zero (00500 would become 500); but convert "0" to (int) 0
			(strlen($item) == 1 || $item[0] != '0')
	) {
		$item = (int)$item;
	}
}

/**
 * Normalizes JSON (numerical type to int), etc
 *
 * @param string $json
 * @return array
 */
function normalize_json($json)
{
	if (!is_array($json)) {
		return $json;
	}
	array_walk_recursive($json, 'convert_json_data_type');

	return $json;
}

/**
 * Format cents-price to display price
 *
 * @param int $amount
 * @param array $params bool abs
 * @return string
 */
function format_amount($amount, $params = [])
{
	if (@$params['abs']) $amount = abs($amount);
	return number_format($amount / 100, 2, ',', '');
}


/**
 * @param array  $array
 * @param bool   $column_names_on_first_line
 * @param string $line_break
 * @param string $delimiter
 * @param string $enclosure
 * @param bool   $encloseAll
 * @param bool   $nullToMysqlNull
 * @return string
 */
function arrayToCsv(array $array, $column_names_on_first_line = true, $line_break = "\n", $delimiter = ';',
                    $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false)
{
	$r = '';

	if ($column_names_on_first_line) {
		$column_names = array_keys($array[0]);
		$r .= arrayToCsvLine($column_names, $delimiter, $enclosure, $encloseAll, $nullToMysqlNull) . $line_break;
	}

	foreach ($array as $row) {
		$r .= arrayToCsvLine($row, $delimiter, $enclosure, $encloseAll, $nullToMysqlNull) . $line_break;
	}
	return $r;
}


/**
 * @param array  $fields
 * @param string $delimiter
 * @param string $enclosure
 * @param bool   $encloseAll
 * @param bool   $nullToMysqlNull
 * @return string
 */
function arrayToCsvLine(array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false,
                        $nullToMysqlNull = false)
{
	$delimiter_esc = preg_quote($delimiter, '/');
	$enclosure_esc = preg_quote($enclosure, '/');

	$output = [];
	foreach ($fields as $field) {
		if ($field === null && $nullToMysqlNull) {
			$output[] = 'NULL';
			continue;
		}

		// Enclose fields containing $delimiter, $enclosure, end of line
		if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\n)/", $field)) {
			$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
		}
		else {
			$output[] = $field;
		}
	}

	return implode($delimiter, $output);
}

/**
 * checks if the array is associative
 * @param array $array
 * @return bool
 */
function is_assoc($array)
{
	return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * This function will always return an array, if the value contains a comma separated string the array will be split
 * @param int|string|array $value
 * @return array
 */
function make_array($value)
{
	if (!$value) $value = [];
	elseif (!is_array($value)) $value = explode(',', $value);
	return $value;
}