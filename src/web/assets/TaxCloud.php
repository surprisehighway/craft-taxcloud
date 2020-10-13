<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\web\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class TaxCloud extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@surprisehighway/taxcloud/web/assets/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/TaxCloud.js',
        ];

        parent::init();
    }
}
