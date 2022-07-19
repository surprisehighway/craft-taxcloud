<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\services;

use Craft;
use craft\base\Component;
use GuzzleHttp\Exception\RequestException;
use surprisehighway\taxcloud\TaxCloud;
use surprisehighway\taxcloud\models\MessageType;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Api Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Surprise Highway
 * @package   TaxCloud
 * @since     1.0.0
 */
class Api extends Component
{

    const API_URL = 'https://api.taxcloud.net/1.0/TaxCloud';

    /**
     * @var string
     */
    public $apiId;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var array
     */
    public $auth;

    /**
     * @var array
     */
    private $_client;



    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     TaxCloud::$plugin->api->exampleService()
     *
     * @return mixed
     */
    public function init(): void
    {
        $this->apiId = TaxCloud::$plugin->getSettings()->apiId;
        $this->apiKey = TaxCloud::$plugin->getSettings()->apiKey;

        $this->auth = [
            'apiLoginId' => $this->apiId,
            'apiKey' => $this->apiKey,
        ];

        $this->_client = $this->createGuzzleClient();
    }

    /* 
     * Verify delivery address
     *
     * This is not yet implemented
     *
     * @see https://dev.taxcloud.com/taxcloud/reference/1-getting-started/verify-address
     */
    public function verifyAddress($parameters = [])
    {
        $options['json'] = array_merge($this->auth, $parameters);

        $response = $this->_client->request('POST', 'VerifyAddress', $options);

        return json_decode($response->getBody());
    }

    /* 
     * Basic sales tax lookup
     *
     * @see https://dev.taxcloud.com/taxcloud/reference/1-getting-started/basic-lookup
     */
    public function lookup($parameters = [])
    {
        $options['json'] = array_merge($this->auth, $parameters);

        $response = $this->_client->request('POST', 'Lookup', $options);

        return json_decode($response->getBody());
    }

    /* 
     * Authorized and capture
     *
     * This is called by the commerce order complete event to record the transaction for reporting with TaxCloud
     *
     * @see https://dev.taxcloud.com/taxcloud/reference/1-getting-started/basic-auth-cap
     */
    public function authorizedAndCapture($parameters = [])
    {
        $options['json'] = array_merge($this->auth, $parameters);

        $response = $this->_client->request('POST', 'AuthorizedWithCapture', $options);

        return json_decode($response->getBody());
    }

    /* 
     * Get TaxCloud Taxability Codes (TICs)
     *
     * This is called by the category sync controller action
     *
     * @see https://dev.taxcloud.com/taxcloud/reference/4-taxability-codes-tics/get-all-tics
     */
    public function getCategories()
    {
        $options['json'] = array_merge($this->auth, []);

        $response = $this->_client->request('POST', 'GetTICs', $options);

        return json_decode($response->getBody())->TICs;     
    }

    /* 
     * Ping
     *
     * You can debug connectivity by loading teh cp url: /admin/actions/taxcloud/categories/ping
     *
     * @see https://dev.taxcloud.com/taxcloud/reference/1-getting-started/ping
     */
    public function ping()
    {
        $options['json'] = array_merge($this->auth, []);

        $response = $this->_client->request('POST', 'Ping', $options);

        return json_decode($response->getBody());     
    }

    /* 
     * Create Guzzle Client
     *
     * @returns \GuzzleHttp\Client
     */
    public function createGuzzleClient()
    {
        $options = [
            'base_uri' => self::API_URL . '/'
        ];

        return Craft::createGuzzleClient($options);
    }
}
