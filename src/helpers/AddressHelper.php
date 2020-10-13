<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\helpers;

class AddressHelper
{
	// Public Methods
    // =========================================================================
    
	public static function getZip4($zip)
	{
		$arr = explode('-', $zip);

		return isset($arr[1]) ? $arr[1] : '';
	}

	public static function getZip5($zip)
	{
		return substr($zip, 0, 5);
	}

}