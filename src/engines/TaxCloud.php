<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\engines;

use Craft;
use craft\base\Component;
use craft\commerce\base\TaxEngineInterface;
use surprisehighway\taxcloud\adjusters\TaxCloud as TaxCloudAdjuster;
use surprisehighway\taxcloud\web\assets\TaxCloud as TaxCloudAsset;


class TaxCloud extends Component implements TaxEngineInterface
{
	/**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return 'TaxCloud Engine';
    }

    /**
     * @inheritDoc
     */
    public static function isSelectable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function taxAdjusterClass(): string
    {
        return TaxCloudAdjuster::class;
    }

    /**
     * @inheritDoc
     */
    public function viewTaxCategories(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteTaxCategories(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function createTaxCategories(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function editTaxCategories(): bool
    {
        return true;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function taxCategoryActionHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(TaxCloudAsset::class);

        return '<a href="#" class="taxcloud-sync-categories-btn btn reload icon">Sync TaxCloud Categories</a>';
    }

    /**
     * @inheritDoc
     */
    public function viewTaxZones(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function editTaxZones(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function createTaxZones(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function deleteTaxZones(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function taxZoneActionHtml(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function viewTaxRates(): bool
    {
        return false;
    }

    public function editTaxRates(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function createTaxRates(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function deleteTaxRates(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function taxRateActionHtml(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function cpTaxNavSubItems(): array
    {
        return [];
    }
}