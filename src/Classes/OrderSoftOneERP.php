<?php
/**
 * 2007-2021 BelVG
 *
 * * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    BelVG
 * @copyright 2007-2021 BelVG
 * @license  https://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

namespace PrestaShop\Module\SoftOneERP\Classes;

use PrestaShop\Module\SoftOneERP\Interfaces\SoftOneErpClientInterface;
use Configuration;
use Order;
use Address;
use PrestaShop\PrestaShop\Adapter\Entity\Carrier;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use Context;
use Currency;

class OrderSoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
{
	/**
	 * Emulating get entities
	 * @return bool
	 */
	public function getEntities()
	{
		return true;
	}

	/**
	 * @param $idOrder
	 * @return array
	 */
	protected function generateOrderData($idOrder)
	{
		$orderData = array();
		$order = new Order($idOrder);

		$paramsAssocOrder = array(
			'id' => 'id',
			'invoice' => 'invoice_number',
            'notes' => 'reference',
            'created_on_utc' => 'date_add',
            'order_status' => 'current_state',
            'payment_method' => 'payment',
            'shipping_fee_incl_tax' => 'total_shipping_tax_incl',
            'shipping_fee_excl_tax' => 'total_shipping_tax_excl',
            'order_discount' => 'total_discounts_tax_incl',
            'order_total_excl_tax' => 'total_products',
            'order_total_incl_tax' => 'total_products_wt',
            'customer_id' => 'id_customer',
		);
        $orderVars = get_object_vars($order);
        foreach ($paramsAssocOrder as $k => $v) {
            if (array_key_exists($v, $orderVars)) {
                $orderData[$k] = $order->$v;
            }
        }
        $orderData['order_tax'] = $order->total_products_wt - $order->total_products;
        $orderData['shipping_method'] = (new Carrier($order->id_carrier))->name;
        $soeCustomerId = CustomerSendedObject::existCustomer($order->id_customer);
        if (!$soeCustomerId)  return false;
        $soeCustomer = new CustomerSendedObject($soeCustomerId);
        $orderData['Trdr'] = $soeCustomer->trdr;

        $address = new Address($order->id_address_delivery);
		$paramsAssocAddress = array(
			'country_code' => 'country',
			'city' => 'city',
			'address' => 'address1',
			'postal_code' => 'postcode',
			'phone_number' => 'phone',
			'cell_number' => 'phone_mobile'
		);

		$addressVars = get_object_vars($address);
		foreach ($paramsAssocAddress as $k => $v) {
			if (array_key_exists($v, $addressVars)) {
				$addressData[$k] = $address->$v;
			}
		}
        $customer = new Customer($order->id_customer);

        $addressData['first_name'] = $customer->firstname;
        $addressData['last_name'] = $customer->lastname;
        $addressData['email'] = $customer->email;

        $orderData['shipping_address'] = $addressData;
        Context::getContext()->currency = new Currency(1);
        $productData = array();
        $orderProducts = $order->getProducts();
        foreach ($orderProducts as $product) {
            $productData[] = array(
                'item_id' => $product['product_id'],
                'combination_sku' => '',
                'quantity' => $product['product_quantity'],
                'price_per_unit' => $product['product_price'],
                'discount_per_unit' => $product['reduction_amount'],
            );
        }
        $orderData['order_items'] = $productData;

		return $orderData;
	}

	protected function sendOrder($idOrder)
	{
		$orderData = $this->generateOrderData($idOrder);
		$response = $this->client->request(
			'POST',
			$this->baseUrl . 'js/RDCJS/setorder',
			[
				'json' => [
					'clientID' => $this->getClientId(),
					'data' => $orderData,
				]
			]);
        $this->saveToLog(print_r($orderData, true));

		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

       print_r($data);

		return $data;
	}


	/**
	 * Import Categories from API with confirmation received data
	 * @param false $onlyChanges
	 * @return false|mixed
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function save($onlyChanges = false)
	{
		$orders = Order::getOrdersWithInformations();
		foreach ($orders as $order) {
            if ($order['id_order']!= 4303) continue;

            $this->sendOrder($order['id_order']);
		}

	}
}