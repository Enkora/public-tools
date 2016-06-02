<?php

class libdbTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ::make_and_conditions
	 */
	public function testMakeAndConditions()
	{
		$bind = ['a.a' => 1, 'b' => 'abc', 'c' => '0', 'd' => 0, 'e' => [1, 2, 3],
		         'f' => [], 'g' => 'abcd', 'h' => '', 'i' => null];
		$conditions = make_and_conditions($bind, ['a.a', 'b', 'c', 'd', 'e', 'f', 'h', 'i']);
		$this->assertEquals("a.a = :a_a AND b = :b AND c = :c AND d = :d AND e IN (1,2,3)", $conditions);
		$this->assertEquals(['a_a' => 1, 'b' => 'abc', 'c' => '0', 'd' => 0], $bind);

		$bind = ['a.a' => 1, 'b' => 'abc', 'c' => '1', 'd' => 'aaa', 'e' => '', 'f' => null];
		$conditions = make_and_conditions($bind, ['a.a', 'b', 'c', 'e', 'f'], true);
		$this->assertEquals("a.a = 1 AND b = 'abc' AND c = 1", $conditions);
		$this->assertEquals(['a.a' => 1, 'b' => 'abc', 'c' => '1', 'd' => 'aaa', 'e' => '', 'f' => null], $bind);

		$r = [
				"äö'" => "moi",
		];

		$this->assertEquals("äö' = :äö'", make_and_conditions($r, ["äö'"]));
		$this->assertEquals("", make_and_conditions($r, ["äö"]));
	}

	/**
	 * @covers ::make_and_conditions
	 * @expectedException Exception
	 */
	public function testMakeAndConditionsException()
	{
		$r = [
				"payment_type_id = 1; DELETE FROM translation; SELECT * FROM payment_type WHERE " => "moi",
				"payment_type_id" => "moi"
		];

		make_and_conditions($r);
	}
}