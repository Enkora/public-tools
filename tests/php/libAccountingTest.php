<?php

class libAccountingTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ::vat_amount
	 */
	public function testVatAmountWithRounding()
	{
		$this->assertEquals(45, vat_amount(495, 10));
		$this->assertEquals(10.7818181818181, vat_amount(118.60, 10, false));

		$this->assertEquals(12.0350877192982, vat_amount(98, 14, false));
		$this->assertEquals(14.5649122807017, vat_amount(118.60, 14, false));

		$this->assertEquals(0.774193548387096, vat_amount(4, 24, false));
		$this->assertEquals(1693.93548387096, vat_amount(8752, 24, false));

		$this->assertEquals(0, vat_amount(1234, 0, false));
		$this->assertEquals(0, vat_amount(5935.12, 0, false));
	}

	/**
	 * @covers ::net_amount
	 */
	public function testNetAmountWithRounding()
	{
		$this->assertEquals(352.72727272727275, net_amount(388, 10, false));
		$this->assertEquals(75.554545454545448, net_amount(83.11, 10, false));

		$this->assertEquals(16.666666666666668, net_amount(19, 14, false));
		$this->assertEquals(139.64912280701753, net_amount(159.20, 14, false));

		$this->assertEquals(5.645161290322581, net_amount(7, 24, false));
		$this->assertEquals(10.887096774193548, net_amount(13.50, 24, false));

		$this->assertEquals(5483, net_amount(5483, 0, false));
		$this->assertEquals(124.11, net_amount(124.11, 0, false));
	}

	/**
	 * @covers ::vat_amount
	 */
	public function testVatAmountWithOutRounding()
	{
		$this->assertEquals('45.00', (string)vat_amount(495, 10));
		$this->assertEquals('10.78', (string)vat_amount(118.60, 10));

		$this->assertEquals('12.04', (string)vat_amount(98, 14));
		$this->assertEquals('14.56', (string)vat_amount(118.60, 14));

		$this->assertEquals('0.77', (string)vat_amount(4, 24));
		$this->assertEquals('1693.94', (string)vat_amount(8752, 24));

		$this->assertEquals(0, vat_amount(1234, 0));
		$this->assertEquals(0, vat_amount(5935.12, 0));
	}

	/**
	 * @covers ::net_amount
	 */
	public function testNetAmountWithOutRounding()
	{
		$this->assertEquals('352.73', (string)net_amount(388, 10));
		$this->assertEquals('75.55', (string)net_amount(83.11, 10));

		$this->assertEquals('16.67', (string)net_amount(19, 14));
		$this->assertEquals('139.65', (string)net_amount(159.20, 14));

		$this->assertEquals('5.65', (string)net_amount(7, 24));
		$this->assertEquals('10.89', (string)net_amount(13.50, 24));

		$this->assertEquals(5483, net_amount(5483, 0));
		$this->assertEquals(124.11, net_amount(124.11, 0));
	}
}
