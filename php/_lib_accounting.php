<?php

function vat_amount($gross_amount, $vat_percentage, $round = true)
{
	$vat_amount = $gross_amount * $vat_percentage / (100 + $vat_percentage);
	return $round ? round($vat_amount, 2) : $vat_amount;
}

function net_amount($gross_amount, $vat_percentage, $round = true)
{
	$net_amount = $gross_amount - vat_amount($gross_amount, $vat_percentage, false);
	return $round ? round($net_amount, 2) : $net_amount;
}