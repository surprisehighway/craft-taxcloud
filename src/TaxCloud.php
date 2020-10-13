<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\commerce\elements\Order;
use craft\commerce\events\TaxEngineEvent;
use craft\commerce\models\Address;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\services\Taxes;
use surprisehighway\taxcloud\services\Api;
use surprisehighway\taxcloud\models\Settings;
use surprisehighway\taxcloud\adjusters\TaxCloud as TaxCloudAdjuster;
use surprisehighway\taxcloud\engines\TaxCloud as TaxCloudEngine;
use surprisehighway\taxcloud\helpers\ResponseHelper;
use yii\base\Exception;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Surprise Highway
 * @package   TaxCloud
 * @since     1.0.0
 *
 * @property  ApiService $api
 */
class TaxCloud extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * TaxCloud::$plugin
     *
     * @var TaxCloud
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * TaxCloud::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'tax-cloud/categories';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'tax-cloud/categories/ping';
            }
        );

        // Replace the tax engine for commerce
        Event::on(Taxes::class, Taxes::EVENT_REGISTER_TAX_ENGINE, static function(TaxEngineEvent $event) {
            $event->engine = new TaxCloudEngine;
        });

        // Register order paid listener
        Event::on(
            Order::class, 
            Order::EVENT_AFTER_ORDER_PAID, 
            function(Event $event) {
                // @var Order $order
                $order = $event->sender;

                try {
                    $response = TaxCloud::$plugin->getApi()->authorizedAndCapture([
                        "cartID" => $order->number,
                        "customerID" => $order->customerId,
                        "orderID" => $order->number,
                        "dateAuthorized" => $order->datePaid->format('c'),
                        "dateCaptured" => $order->datePaid->format('c'),
                    ]);

                    if(ResponseHelper::getResponseType($response->ResponseType) == 'OK') {
                        $message = 'Order ' . $order->number . ': captured successfully.';
                        Craft::info($message, __METHOD__);
                    } else {
                        $errors = ResponseHelper::getMessages($response->Messages);

                        foreach ($errors as $message) {
                            Craft::error($message, __METHOD__);
                        }
                    }
                } catch (RequestException $e) {
                    $message = $e->getMessage() ?? 'An unknown error ocurred.';
                    Craft::error($message, __METHOD__);

                    throw $e;
                }
            }
        );


        // Keep our log files separate for debugging
        Craft::getLogger()->dispatcher->targets[] = new \craft\log\FileTarget([
            'logFile' => '@storage/logs/taxcloud.log',
            'categories' => ['surprisehighway\taxcloud\*']
        ]);
    }


    /**
     * Returns the api service
     *
     * @return Api The api service
     * @throws \yii\base\InvalidConfigException
     */
    public function getApi()
    {
        return $this->get('api');
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Sets the components of the plugin
     */
    private function _setPluginComponents()
    {
        $this->setComponents([
            'api' => Api::class,
        ]);
    }
}

