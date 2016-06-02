<?php

class libcommonTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringBasic()
	{
		$this->assertEquals('123', list_to_string([1, 2, 3]), 'Concatenate all array values without separator');
	}

	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringWithString()
	{
		$this->assertEquals('123', list_to_string('123'), 'If a string is passed should return the string');
	}

	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringSeparator()
	{
		$this->assertEquals('1, 2, 3', list_to_string([1, 2, 3], ', '),
		                    'Concatenate all array values with separator');
	}

	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringDoNotIncludeEmptyValue()
	{
		$this->assertEquals('1, 3', list_to_string([1, '', 3], ', '),
		                    'Concatenate all non-empty array values with separator');
	}

	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringIncludeEmptyValue()
	{
		$this->assertEquals('1, , 3', list_to_string([1, '', 3], ', ', true),
		                    'Concatenate all array values with separator, including empty');
	}

	/**
	 * @covers ::list_to_string
	 */
	public function testListToStringIncludeEmptyValueWithFillValue()
	{
		$this->assertEquals('1|2|3', list_to_string(['a' => 1, 'b' => null, 'c' => 3], '|', true, 2),
		                    'Concatenate all array values with separator, '
		                    . 'including empty, using fill value for empty fields');
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringEmptyArray()
	{
		$this->assertEquals('', pairs_to_string([]), 'Test empty array parameter');
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringZeroString()
	{
		$this->assertEquals('0', pairs_to_string('0'));
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringZero()
	{
		$this->assertEquals('0', pairs_to_string(0));
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringObject()
	{
		$o = new stdClass();
		$o->a = 1;
		$o->b = 2;
		$this->assertEquals('a1,b2', pairs_to_string($o));
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringNotArray()
	{
		$this->assertEquals('test', pairs_to_string('test'), 'Test string parameter');
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringBasic()
	{
		$this->assertEquals('a=1;c=3', pairs_to_string(['a' => 1,
		                                                'b' => '',
		                                                'c' => 3], '=', ';'), 'Concatenate with = and ;');
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringWithEmpty()
	{
		$this->assertEquals('a=1;b=;c=3', pairs_to_string(['a' => 1,
		                                                   'b' => '',
		                                                   'c' => 3], '=', ';', '', true),
		                    'Concatenate with empty values');
	}

	/**
	 * @covers ::pairs_to_string
	 */
	public function testPairsToStringWithEnclosures()
	{
		$this->assertEquals('a="1";b="";c="3"', pairs_to_string(['a' => 1,
		                                                         'b' => '',
		                                                         'c' => 3], '=', ';', '"', true),
		                    'Concatenate with enclosures');
	}

	/**
	 * @covers ::get_column
	 */
	public function testGetColumn()
	{
		$data = [
				['a' => 1,
				 'b' => 2,
				 'c' => 3,
				 'd' => '',
				 'e' => 0,
				 'f' => ''],
				['a' => 2,
				 'b' => 2,
				 'c' => 3,
				 'd' => '',
				 'e' => 0,
				 'f' => 0],
				['a' => 3,
				 'b' => 2,
				 'c' => 3,
				 'd' => '',
				 'e' => 0,
				 'f' => ''],
				['a' => 4,
				 'b' => 3,
				 'c' => 3,
				 'd' => '',
				 'e' => 0,
				 'f' => 0],
				['b' => 4,
				 'c' => 3,
				 'd' => '',
				 'e' => 0,
				 'f' => false]
		];

		$this->assertEquals([1, 2, 3, 4, null], get_column($data, 'a'));
		$this->assertEquals([2, 2, 2, 3, 4], get_column($data, 'b'));
		$this->assertEquals([2 => 2,
		                     3 => 3,
		                     4 => 4], get_column($data, 'b', true));
		$this->assertEquals([3 => 3], get_column($data, 'c', true));
		$this->assertEquals(['' => ''], get_column($data, 'd', true));
		$this->assertEquals([0 => 0], get_column($data, 'e', true));
		$this->assertEquals(['' => '',
		                     0  => 0], get_column($data, 'f', true));
	}
	

	/**
	 * @covers ::url_combine
	 */
	public function testUrlCombine()
	{
		$this->assertEquals('http://test1/test2/test3', url_combine('http://test1/', 'test2/', '/test3'),
		                    'Test params as individual function parameters');
		$this->assertEquals('/test1/test2/test3', url_combine([' /test1/', 'test2/ ', '/test3 ']),
		                    'Test params as one array parameter');
		$this->assertEquals('/test1/test2/test3/', url_combine(' /test1/', '/test2//', '/test3/'),
		                    'Test trailing slashes to remain');
		$this->assertEquals('/test1/', url_combine([' /test1/  ']),
		                    'Test trailing slashes to remain when one part as array');
		$this->assertEquals('/test1/', url_combine(' /test1/  '),
		                    'Test trailing slashes to remain when one');
		$this->assertEquals('/test1/test2/test3/?abcd', url_combine(' /test1/', '/test2//', '//test3/?abcd   '),
		                    'Test trailing slashes to remain with url params');
	}

	/**
	 * @covers ::mb_strlen_max
	 */
	public function testMbStrlenMax()
	{
		$this->assertEquals(2, mb_strlen_max('1', '12'));
		$this->assertEquals(2, mb_strlen_max(1, 12));
		$this->assertEquals(2, mb_strlen_max(1, '00'));
		$this->assertEquals(3, mb_strlen_max(1, 0.1));
	}

	/**
	 * @covers ::array_reduce_column
	 */
	public function testArrayReduceColumn()
	{
		$array[] = ['a' => 2, 'b' => 3, '' => 1];
		$array[] = ['a' => 0.3, 'b' => 2, '' => 1];
		$array[] = ['a' => '0.123', 'b' => '34', '' => 1];
		$array[] = ['a' => 786, 'b' => 19854, '' => 2];
		$this->assertEquals(0.123, array_reduce_column($array, 'a', 'min', ENK_MAXINT));
		$this->assertEquals(786, array_reduce_column($array, 'a', 'max'));
		$this->assertEquals(1, array_reduce_column($array, '', 'min', ENK_MAXINT));
	}

	/**
	 * @covers ::array_filter_keys
	 */
	public function testArrayFilterKeys()
	{
		$array = ['' => '', 'a' => 'a', 'b' => 'b', 'c' => 'c'];
		array_filter_keys($array, ['', 'b']);
		$this->assertEquals(['' => '', 'b' => 'b'], $array);

		$array = ['' => '', 'a' => 'a', 'b' => 'b', 'c' => 'c'];
		array_filter_keys($array, false, ['', 'b']);
		$this->assertEquals(['a' => 'a', 'c' => 'c'], $array);

		$array = ['' => '', 'a' => 'a', 'b' => 'b', 'c' => 'c'];
		array_filter_keys($array, ['', 'b'], ['', 'b']);
		$this->assertEquals([], $array);
	}

	/**
	 * @covers ::array_pluck
	 */
	public function testArrayPluck()
	{
		$array = [['name' => 'moi'], ['name' => 'hei'], ['hei' => 'moi']];
		$fields = array_pluck('name', $array);
		$this->assertEquals(['moi', 'hei'], $fields);
		$this->assertEquals([], array_pluck('field', []));
		$this->assertEquals([], array_pluck('field', "testString"));
	}

	/**
	 * @covers ::calculate_luhn
	 */
	public function testCalculateLuhn()
	{
		$this->assertEquals('8', calculate_luhn(124124897));
		$this->assertEquals('4', calculate_luhn(4124));
		$this->assertEquals('8', calculate_luhn(1));
		$this->assertEquals('0', calculate_luhn(19));
	}

	/**
	 * @covers ::calculate_luhn
	 */
	public function testLuhnChecksum()
	{
		$this->assertEquals(true, is_luhn_valid(1241248978));
		$this->assertEquals(true, is_luhn_valid(190));
		$this->assertEquals(false, is_luhn_valid(500));
	}

	/**
	 * @covers ::calculate_luhn
	 */
	public function testStandardDeviation()
	{
		$this->assertEquals(0.0, standard_deviation([5, 5, 5]));
		$this->assertEquals(0.81649658092773, standard_deviation([-1, 0, 1]));
		$this->assertEquals(40.824829046386, standard_deviation([150, 200, 250]));
	}

	/**
	 * @covers ::calculateInvoiceChecksum
	 */
	public function testCalculateInvoiceChecksum()
	{
		$this->assertEquals(7, calculateInvoiceChecksum(1234555998));
		$this->assertEquals(8, calculateInvoiceChecksum(1234990000));
		$this->assertEquals(3, calculateInvoiceChecksum(1));
		$this->assertEquals(0, calculateInvoiceChecksum(99));
	}

	/**
	 * @covers ::array_override_column
	 */
	public function testArrayOverrideColumn()
	{
		$array = [['name' => 'hei', 'name_web' => 'moi'], ['name' => 'hei2', 'name_web' => 'moi2'], ['name' => 'hei3']];
		$array_copy_1 = $array_copy_2 = $array;
		array_override_column('name', 'name_web', $array_copy_1, true);
		array_override_column('name', 'name_web', $array_copy_2, false);

		$this->assertEquals([['name' => 'moi'], ['name' => 'moi2'], ['name' => 'hei3']], $array_copy_1);
		$this->assertEquals([['name' => 'hei'], ['name' => 'hei2'], ['name' => 'hei3']], $array_copy_2);
	}

	/**
	 * @covers ::change_byte_order
	 */
	public function testChangeByteOrder()
	{
		$this->assertEquals(1539215241, change_byte_order(2307898971));
		$this->assertEquals('1539215241', change_byte_order('2307898971'));
		$this->assertEquals(2307898971, change_byte_order(1539215241));

		$this->assertEquals(1850101507, change_byte_order(55527022));
		$this->assertEquals('1850101507', change_byte_order('55527022'));
		$this->assertEquals(55527022, change_byte_order(1850101507));

		$this->assertEquals(671817924, change_byte_order(3290434344));
		$this->assertEquals('671817924', change_byte_order('3290434344'));
		$this->assertEquals(3290434344, change_byte_order(671817924));

		if (PHP_INT_SIZE == 8)
			$this->assertEquals(72472634, change_byte_order("36105003610951940", 4));
	}

	/**
	 * @covers ::truncate_bytes
	 */
	public function testTruncateBytes()
	{
		$this->assertEquals(52501, truncate_bytes(123456789, 2));
		if (PHP_INT_SIZE == 8)
			$this->assertEquals(987255044, truncate_bytes(36105003610951940, 4));
	}

	/**
	 * @covers ::normalize_json
	 */
	public function testNormalizeJson()
	{
		if (PHP_INT_SIZE < 8) $this->markTestSkipped('The tests are made for 64-bit php');

		$this->assertSame([123], normalize_json(["123"]));
		$this->assertSame(["0123"], normalize_json(["0123"]));

		$d = new stdClass();
		$d->a = "0";

		$c6 = new stdClass();
		$c6->a = true;
		$c6->b = "123";
		$c6->c = "9007199254740993";
		$c6->d = $d;

		$this->assertSame(["a" => ["b" => [
			"c" => 123,
			"c1" => "9007199254740993",
			"c1.5" => 9007199254740991,
			"c2" => "0000123",
			"c3" => 0,
			"c4" => 554,
			"c5" => true,
			"c6" => ["a" => true, "b"=> 123, "c"=>"9007199254740993", "d" => ["a" => 0]]
		]], "d" => 5662, "e" => "hei"], normalize_json(["a" => ["b" => [
				"c" => "123",
				"c1" => "9007199254740993",
				"c1.5" => "9007199254740991",
				"c2" => "0000123",
				"c3" => "0",
				"c4" => 554,
				"c5" => true,
				"c6" => $c6,
		]], "d" => "5662", "e" => "hei"]));
	}

	/**
	 * @covers ::format_amount
	 */
	public function testFormatAmount()
	{
		$this->assertEquals("1,00", format_amount(100));
		$this->assertEquals("1,00", format_amount(-100, ['abs' => true]));
		$this->assertEquals("-1,00", format_amount(-100));
		$this->assertEquals("0,00", format_amount("0"));
		$this->assertEquals("1000,00", format_amount(100000));
		$this->assertEquals("231000,00", format_amount(23100000));

		// TODO figure out what should happen in these cases...
		//$this->assertEquals("0,00", format_amount(""));
		//$this->assertEquals("0,00", format_amount("0,9"));
		//$this->assertEquals("0,00", format_amount("wrong"));
	}

	/**
	 * @covers ::arrayToCsv
	 */
	public function testArrayToCsv()
	{
		$csv = arrayToCsv([
				                  ['name' => 'blabla', 'price' => 123.12, 'text' => "blabla;\"''"],
				                  ['name' => 'fds', 'price' => 123.12, 'text' => "blabla;\""],
				                  ['name' => '63', 'price' => '', 'text' => "blabla;''"],
				                  ['name' => '63', 'price' => '', 'text' => "bla\nbla"]
		                  ]);

		$expected =
				"name;price;text\n"
				. "blabla;123.12;\"blabla;\"\"''\"\n"
				. "fds;123.12;\"blabla;\"\"\"\n"
				. "63;;\"blabla;''\"\n"
				. "63;;\"bla\nbla\"\n";

		$this->assertEquals($expected, $csv);
	}

	/**
	 * @covers ::array_whitelist
	 */
	public function testArrayWhitelist()
	{
		$arr = ["hei" => "moi", "test" => "123"];

		$this->assertSame([], array_whitelist($arr));
		$this->assertSame(['hei' => 'moi'], array_whitelist($arr, ['hei']));
		$this->assertSame(['hei' => 'moi'], array_whitelist($arr, 'hei'));
	}

	/**
	 * @covers ::make_array
	 */
	public function testMakeArray()
	{
		$this->assertEquals([], make_array(false));
		$this->assertEquals([1], make_array(1));
		$this->assertEquals([1], make_array('1'));
		$this->assertEquals([1, 2], make_array([1, 2]));
		$this->assertEquals([1, 2], make_array('1,2'));
	}
}