<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\models;

use surprisehighway\taxcloud\TaxCloud;

use Craft;
use craft\base\Model;

/**
 * Settings Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Surprise Highway
 * @package   TaxCloud
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

        /**
     * @var string
     */
    public $apiId;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var bool
     */
    public $verifyAddress = false;

    /**
     * @var string
     */
    public $defaultShippingTic;


    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['apiId', 'string'],
            ['apiKey', 'string'],
            ['verifyAddress', 'boolean'],
            ['verifyAddress', 'default', 'value' => false],
            ['defaultShippingTic', 'string'],
            ['defaultShippingTic', 'default', 'value' => '11010'],
        ];
    }
}
