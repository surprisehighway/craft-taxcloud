<?php
/**
 * TaxCloud plugin for Craft CMS 3.x
 *
 * TaxCloud integration for Craft Commerce
 *
 * @link      https://surprisehighway.com
 * @copyright Copyright (c) 2020 Surprise Highway
 */

namespace surprisehighway\taxcloud\adjusters;

use Craft;
use craft\base\Component;
use craft\commerce\adjusters\Shipping;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\elements\Address;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\Plugin;
use GuzzleHttp\Exception\RequestException;
use surprisehighway\taxcloud\TaxCloud as TaxCloudPlugin;
use surprisehighway\taxcloud\helpers\ResponseHelper;
use surprisehighway\taxcloud\helpers\AddressHelper;
use yii\base\Exception;

class TaxCloud extends Component implements AdjusterInterface
{
	const ADJUSTMENT_TYPE = 'tax';

	/**
	 * @var Order
	 */
	private $_order;

	/**
	 * @var Address
	 */
	private $_address;

	/**
	 * @var Verified Address
	 */
	private $_verifiedAddress;


	/**
	 * @inheritdoc
	 */
	public function adjust(Order $order): array
	{
		$this->_order = $order;

		$this->_address = $this->_order->getShippingAddress();

		if (Plugin::getInstance()->getSettings()->useBillingAddressForTax) {
			$this->_address = $this->_order->getBillingAddress();
		}

		
		// Notes: - Estimated shipping address fails without a City
		//        - TaxCloud only supports address verification and tax lookup for US destinations

		$country = (isset($this->_address)) ? $this->_address->getCountryCode() : 'US';

		if (!$this->_address || $country !== 'US' || empty($order->getLineItems())) {
			return [];
		}

		$orderTaxes = $this->_getSalesTax();
		$adjustments = [];
		$index = 0;
		$totalTax = 0;

		if(isset($orderTaxes->CartItemsResponse)) {
			
			foreach ($orderTaxes->CartItemsResponse as $taxLineItem) {
				$totalTax += $taxLineItem->TaxAmount;
			}
			
			$adjustment = new OrderAdjustment();
			$adjustment->type = self::ADJUSTMENT_TYPE;
			$adjustment->name = Craft::t('commerce', 'Tax');
			$adjustment->amount = $totalTax;
			$adjustment->description = '';
			$adjustment->sourceSnapshot = ['taxcloud' => json_decode(json_encode($orderTaxes), true)];
			$adjustment->setOrder($this->_order);

			$adjustments[] = $adjustment;
		}

		return $adjustments;
	}

	/**
	 * Determine a hash based on order attributes
	 *
	 * @return string
	 */
	private function _getOrderHash()
	{
		$number = $this->_order->number;
		$lineItems = '';
		$address = '';
		$index = 0;
		$shipping = $this->_order->getTotalShippingCost();
		$discount = $this->_order->getTotalDiscount();
		$price = $this->_order->getTotalPrice();

		foreach ($this->_order->getLineItems() as $lineItem) {
			$lineItems .= $index . ':' . $lineItem->purchasable->productId . ':' . $lineItem->qty . ':' . $lineItem->getSubtotal();
			$index++;
		}

		if($this->_address) {
			$address .= $this->_address->getAddressLine1();
			$address .= $this->_address->getAddressLine2();
			$address .= $this->_address->getPostalCode();
			$address .= $this->_address->getAdministrativeArea();
			$address .= $this->_address->getCountryCode();
		}

		return md5($number . ':' . $lineItems . ':' . $address . ':' . $shipping . ':' . $discount . ':'  . $price);
	}

	private function _getItemDiscount($productId, $qty)
	{

		foreach ($this->_order->adjustments as $adjustment) {
			if($adjustment->type === 'discount') {
				if ($adjustmentLineItem = $adjustment->getLineItem()) {
					if($productId === $adjustmentLineItem->purchasable->productId) {
						return $adjustment->amount / $qty;
					}
				}
			}
		}
		return false;
	}

	private function _getSalesTax()
	{
		$orderHash = $this->_getOrderHash();
		$storeLocation = Plugin::getInstance()->getStore()->getStore()->getLocationAddress();

		// Cart line items
		$lineItems = [];
		$index = 0;

		foreach ($this->_order->getLineItems() as $lineItem) {
			$category = Plugin::getInstance()->getTaxCategories()->getTaxCategoryById($lineItem->taxCategoryId);
			$discount = $this->_getItemDiscount($lineItem->purchasable->productId, $lineItem->qty);
			$lineItems[] = [
					"Index" => $index,
					"ItemID" => $lineItem->purchasable->productId,
					"Price" => $discount ? $lineItem->salePrice + $discount : $lineItem->salePrice,
					"Qty" => $lineItem->qty,
					"TIC" => $category ? $category->handle : null,
				];
			$index++;
		}

		// Shipping line item
		$lineItems[] = [
			"Index" => $index,
			"ItemId" => 'SHIPPING',
			"Price" => $this->_order->getTotalShippingCost(),
			"Qty" => 1,
			"TIC" => TaxCloudPlugin::$plugin->getSettings()->defaultShippingTic,
		];

		$cacheKey = 'taxcloud-' . $orderHash;

		$response = Craft::$app->getCache()->get($cacheKey);

		if($response) {
			Craft::info(
	            'Order ' . $this->_order->number . ': Cached order found. ' . $cacheKey,
	            __METHOD__
	    	);
		}

		if (!$response) {

			Craft::info(
	            'Order ' . $this->_order->number . ': Cached order not found, creating api request. ' . $cacheKey,
	            __METHOD__
        	);

			try {
				$response = TaxCloudPlugin::$plugin->getApi()->lookup([
					"cartID" => $this->_order->number,
					"cartItems" => $lineItems,
					"customerID" => $this->_order->customerId,
					"deliveredBySeller" => false,
					"destination" => $this->_getAddress(),
					"origin" => [
						"Address1" => $storeLocation->getAddressLine1(),
						"Address2" => $storeLocation->getAddressLine2(),
						"City" => $storeLocation->getLocality(),
						"State" => $storeLocation->getAdministrativeArea(),
						"Zip4" => $storeLocation->getPostalCode() ? AddressHelper::getZip4($storeLocation->getPostalCode()) : '',
						"Zip5" => $storeLocation->getPostalCode() ? AddressHelper::getZip5($storeLocation->getPostalCode()) : '',
					]
				]);

				if(ResponseHelper::getResponseType($response->ResponseType) == 'OK') {
					// Save the results only if valid
					Craft::$app->getCache()->set($cacheKey, $response);

					Craft::info('Order ' . $this->_order->number . ': Tax lookup success. ' . json_encode($response),__METHOD__);
				} else {
					$errors = ResponseHelper::getMessages($response->Messages);

                    foreach ($errors as $message) {
                        Craft::error('Order ' . $this->_order->number . ': ' . $message, __METHOD__);
                    }

                    foreach ($errors as $message) {
						throw new Exception($message);
					}
				}

			} catch (RequestException $e) {
				$message = $e->getMessage() ?? 'An unknown error ocurred.';
				Craft::error('Order ' . $this->_order->number . ': ' . $message, __METHOD__);

				return [];
			}

		}

		return $response;
	}

	/**
	 * Determine a hash based on order address attributes
	 *
	 * @return string
	 */
	private function _getAddressHash()
	{
		$number = $this->_order->number;
		$address = '';

		if($this->_address) {
			$address .= $this->_address->getAddressLine1();
			$address .= $this->_address->getAddressLine2();
			$address .= $this->_address->getPostalCode();
			$address .= $this->_address->getAdministrativeArea();
			$address .= $this->_address->getCountryCode();
		}

		return md5($number . ':' . $address . ':');
	}

	/**
	 * Format the desination address and optionally verify it with TaxCloud
	 *
	 * @return array
	 */
	private function _getAddress()
	{
		$addressHash = $this->_getAddressHash();
		$cacheKey = 'taxcloud-address-' . $addressHash;

		$address = [
			"Address1" => $this->_address->getAddressLine1(),
			"Address2" => $this->_address->getAddressLine2(),
			"City" => $this->_address->getLocality(),
			"State" => $this->_address->getAdministrativeArea(),
			"Zip4" => $this->_address->getPostalCode() ? AddressHelper::getZip4($this->_address->getPostalCode()) : '',
			"Zip5" => $this->_address->getPostalCode() ? AddressHelper::getZip5($this->_address->getPostalCode()) : '',
		];

		$this->_verifiedAddress = $address;

		if(TaxCloudPlugin::$plugin->getSettings()->verifyAddress !== true) {
			// Skip address validation unless enabled in settings
			Craft::info('Order ' . $this->_order->number . ': Address verification disabled, skipping. ', __METHOD__);
			return $this->_verifiedAddress;
		}

		// Verification is enabled, try to use the verified address if possible

		$response = Craft::$app->getCache()->get($cacheKey);

		if(!$response) {
			try {
				$response = TaxCloudPlugin::$plugin->getApi()->verifyAddress($address);

				if($response->ErrNumber == 0) {
					// Save the results only if valid
					Craft::$app->getCache()->set($cacheKey, $response);

					Craft::info('Order ' . $this->_order->number . ': Address verified. ' . json_encode($response), __METHOD__);
				} else {
					Craft::info('Order ' . $this->_order->number . ': Address verification failed, continuing to lookup. ' . json_encode($response), __METHOD__);
				}
			} catch (RequestException $e) {
				// continue to lookup with provided information even if not verified
			}
		}

		if($response && isset($response->ErrNumber) && $response->ErrNumber == 0) {
			$this->_verifiedAddress = [
				"Address1" => $response->Address1 ?? '',
				"Address2" => $response->Address2 ?? '',
				"City" => $response->City ?? '',
				"State" => $response->State ?? '',
				"Zip4" => $response->Zip4 ?? '',
				"Zip5" => $response->Zip5 ?? '',
			];

			Craft::info('Order ' . $this->_order->number . ': Using verfied address. ' . json_encode($this->_verifiedAddress), __METHOD__);
		}

		return $this->_verifiedAddress;
	}
}