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
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class CustomerSoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
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
	 * @param $idCustomer
	 * @return array
	 */
	protected function generateCustomerData($idCustomer)
	{
		$customerData = array();
		$customer = new \Customer($idCustomer);
		$addressId = \Address::getFirstCustomerAddressId($idCustomer);
		$address = new \Address($addressId);
		$paramsAssocCustomer = array(
			'id' => 'id',
			'invoice' => '',
			'tax_office' => '',
			'profession' => '',
			'first_name' => 'firstname',
			'last_name' => 'lastname',
			'email' => 'email',
		);
		$paramsAssocAddress = array(
			'company' => 'company',
			'vat_number' => 'vat_number',
			'country_code' => 'country',
			'city' => 'city',
			'address' => 'address1',
			'postal_code' => 'postcode',
			'phone_number' => 'phone',
			'cell_number' => 'phone_mobile'
		);
		$customerVars = get_object_vars($customer);
		foreach ($paramsAssocCustomer as $k => $v) {
			if (array_key_exists($v, $customerVars)) {
				$customerData[$k] = $customer->$v;
			}
		}
		$addressVars = get_object_vars($address);
		foreach ($paramsAssocAddress as $k => $v) {
			if (array_key_exists($v, $addressVars)) {
				$customerData[$k] = $address->$v;
			}
		}

		return $customerData;
	}

	protected function sendCustomer($idCustomer)
	{
		$customerData = $this->generateCustomerData($idCustomer);
		$response = $this->client->request(
			'POST',
			$this->baseUrl . 'js/RDCJS/setcustomer',
			[
				'json' => [
					'clientID' => $this->getClientId(),
					'data' => $customerData,
				]
			]);

		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

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
		$customers = \Customer::getCustomers();
		foreach ($customers as $customer) {

            if (CustomerSendedObject::existCustomer($customer['id_customer']) !== false)  continue;
			$data = $this->sendCustomer($customer['id_customer']);
            print_r($customer);
            print_r($data);
			if ($data['success']) {
				$this->saveCustomerSended(new Customer($customer['id_customer']), $data['id']);
			} else {
				return $data;
			}
		}

	}

	protected function saveCustomerSended(\Customer $customer, $trdr)
	{
		$container = SymfonyContainer::getInstance();
		/** @var CustomerSendedObject $customerSended */
		$customerSended = $container->get('prestashop.module.softoneerp_classes.customer_sended');
		$customerSended->id_customer = $customer->id;
        $customerSended->id_soe_customers = null;
        $customerSended->id = null;
		$customerSended->date_upd = $customer->date_upd;
		$customerSended->trdr = $trdr;

		$customerSended->save();
	}
}