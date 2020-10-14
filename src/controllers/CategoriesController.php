<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\controllers;

use Craft;
use craft\web\Controller;
use craft\commerce\models\TaxCategory;
use craft\commerce\Plugin as CommercePlugin;
use surprisehighway\taxcloud\TaxCloud;
use surprisehighway\taxcloud\helpers\AddressHelper;
use surprisehighway\taxcloud\helpers\ResponseHelper;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Categories Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Surprise Highway
 * @package   TaxCloud
 * @since     1.0.0
 */
class CategoriesController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/taxcloud/categories/do-something
     *
     * @return mixed
     */
    public function actionPing(): Response
    {
        $this->requirePermission('commerce-manageTaxes');
        
        $response = TaxCloud::$plugin->getApi()->ping();

        if(ResponseHelper::getResponseType($response->ResponseType) == 'OK') {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['success' => false, 'messages' => ResponseHelper::getMessages($response->Messages)]);
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/taxcloud/categories/do-something
     *
     * @return mixed
     */
    public function actionSync(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('commerce-manageTaxes');

        try {
            $allCategories =  TaxCloud::$plugin->getApi()->getCategories();
        } catch (\Exception $exception) {
            return $this->asJson(['success' => false]);
        }

        // save categories
        foreach ($allCategories as $taxCloudCategory) {
            $handle = $taxCloudCategory->TICID === 0 ? '00000' : (string)$taxCloudCategory->TICID;
            $existing = CommercePlugin::getInstance()->getTaxCategories()->getTaxCategoryByHandle($handle);

            if (!$existing) {
                $newCategory = new TaxCategory();
                $newCategory->name = $handle . ' - ' . $taxCloudCategory->Description;
                $newCategory->description = '';
                $newCategory->handle = $handle;
                $newCategory->default = false;
                if (!CommercePlugin::getInstance()->getTaxCategories()->saveTaxCategory($newCategory)) {
                    Craft::error('Could not save tax category from TaxCloud.');
                    return $this->asJson(['success' => false]);
                }
            }
        }

        return $this->asJson(['success' => true, 'categories' => $allCategories]);
    }
}
