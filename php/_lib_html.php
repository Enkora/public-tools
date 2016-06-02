<?php

/**
 * Reads, enables or disables html autoformat
 * @param int $enable
 * @return int
 */
function h_autoformat($enable = -1)
{
	global $h_autoformat;
	if ($enable == -1) return $h_autoformat;
	else $h_autoformat = $enable;
	return $h_autoformat;
}

/**
 * Any HTML element
 * @param string                 $element
 * @param string|boolean         $content
 * @param stdClass|array|boolean $attributes
 * @param bool|int               $auto_format
 * @return string
 */
function h_e($element, $content = false, $attributes = [], $auto_format = -1)
{
	if ($auto_format == -1) $auto_format = h_autoformat();

	if (is_object($attributes)) $attributes = get_object_vars($attributes);

	$attributes_string = '';
	if ($attributes) {
		if (isset($attributes['checked']) && $attributes['checked'])
			$attributes['checked'] = 'checked';
		else unset($attributes['checked']);

		if (isset($attributes['disabled']) && $attributes['disabled'])
			$attributes['disabled'] = 'disabled';
		else unset($attributes['disabled']);

		if ($attributes) {
			$attributes_string = pairs_to_string($attributes, '=', ' ', '"');
			if ($attributes_string) $attributes_string = " " . $attributes_string;
		}
	}

	if ($auto_format
	    && (strlen($element . $attributes_string . $content) > 120
	        && $element != 'textarea'
	        || $element == 'tr')
	) {
		$br = "\n";
		$indent = "\t";
	}
	else {
		$br = '';
		$indent = '';
	}
	if ($content === false) $s = "<$element$attributes_string />";
	else $s = "<$element$attributes_string>$br$indent$content$br</$element>$br";

	return $s;
}

function h_pre($content, $class = false)
{
	return h_e('pre', $content, ['class' => $class], false);
}

function h_p($content, $class = false)
{
	return h_e('p', $content, ['class' => $class]);
}

function h_div($content, $class = false, $id = false)
{
	return h_e('div', $content, ['class' => $class,
	                             'id'    => $id]);
}

function h_span($content, $class = false, $id = false)
{
	return h_e('span', $content, ['class' => $class,
	                              'id'    => $id]);
}

/*function h_a($link,$label, $class=false, $new_window=false)
{
	$class = $class?" class = \"$class\"":'';
	if ($new_window) $onclick = " onclick = \"window.open('$link','','width=800,height=600'); return false;\"";
	else $onclick="";
	return "<a href=\"$link\"$class{$onclick}>$label</a>";
}*/

function h_a($content, $link, $class = false, $new_window = false, $attributes = [])
{
	if ($new_window) $new_window = "window.open('$link','',''); return false;";
	else $new_window = false;
	return h_e('a', $content, array_merge($attributes, ['href'    => $link,
	                           'class'   => $class,
	                           'onclick' => $new_window]), false);
}

function h_ul($content, $class = false)
{
	return h_e('ul', $content, ['class' => $class]);
}

function h_li($content, $class = false)
{
	return h_e('li', $content, ['class' => $class]);
}

function h_td($content, $class = false, $column_span = false, $auto_format = true)
{
	return h_e('td', $content, ['class'   => $class,
	                            'colspan' => $column_span], $auto_format);
}

function h_tr($content, $class = false)
{
	return h_e('tr', $content, ['class' => $class]);
}

function h_table($content, $class = false, $style = false, $id = false)
{
	return h_e('table', $content, ['class' => $class,
	                               'style' => $style,
	                               'id'    => $id]);
}

function h_thead($content, $class = false)
{
	return h_e('thead', $content, ['class' => $class]);
}

function h_tbody($content, $class = false)
{
	return h_e('tbody', $content, ['class' => $class]);
}

function h_tfoot($content, $class = false)
{
	return h_e('tfoot', $content, ['class' => $class]);
}

function h_colgroup($content, $class = false)
{
	return h_e('colgroup', $content, ['class' => $class]);
}

function h_col($content, $class = false)
{
	return h_e('col', $content, ['class' => $class]);
}

function h_h1($content, $class = false)
{
	return h_e('h1', $content, ['class' => $class]);
}

function h_h2($content, $class = false)
{
	return h_e('h2', $content, ['class' => $class]);
}

function h_h3($content, $class = false)
{
	return h_e('h3', $content, ['class' => $class]);
}

function h_label($content, $for = false, $class = false)
{
	return h_e('label', $content, ['for'   => $for,
	                               'class' => $class]);
}

function nbsp_if_empty($variable)
{
	return (!empty($variable) && isset($variable)) ? $variable : '&nbsp;';
}

// ================ Form combined controls =================
