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

class ResponseHelper
{
    // Constants
    // =========================================================================

    const Error = 'Error';
    const Warning = 'Warning';
    const Informational = 'Informational';
    const OK = 'OK';


    // Public Methods
    // =========================================================================

    /**
     * Returns a ResponseType string based on the TaxCloud ResponseType integer.
     *
     * Example: https://dev.taxcloud.com/taxcloud/resources/capturedresponse
     *
     * @return string
     */
    public static function getResponseType($int)
    {
        switch ($int) {
            case 0:
                return self::Error;

            case 1: 
                return self::Warning;

            case 2: 
                return self::Informational;

            case 3: 
                return self::OK;

            default: 
                return self::Error;
            }
    }

    /**
     * Returns an array of messages from the TaxCloud response.
     *
     * Example: https://dev.taxcloud.com/taxcloud/resources/capturedresponse
     *
     * @return array
     */
    public static function getMessages($arr)
    {
        $messages = [];

        foreach($arr as $message) {
            $messages[] = $message->Message;
        }

        return $messages;
    }
}
