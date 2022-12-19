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

use Lcobucci\JWT\Exception;
use PrestaShop\Module\SoftOneERP\Interfaces\SoftOneErpClientInterface;
use Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Order;

class OrderStatusSoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
{
	/**
	 * Getting categories from API
	 * @return false|mixed
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function getEntities()
	{
		$response = $this->client->request(
			'POST',
			$this->baseUrl . 'js/RDCJS/getorderstatus',
			[
				'json' => [
					'clientID' => $this->getClientId(),
					'onlyChanges' => $this->onlyChanges,
				]
			]);

		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

		$this->setRunId($data['RunId']);

		return $data;
	}

	/**
	 * Save categories from API
	 * @return array|false
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function saveOrderStatuses()
	{
		$this->errors = [];
		$orderStatusesSaved = [];
		$orderStatusesData = $this->getEntities();

		if (!$orderStatusesData['success']) {
			return false;
		}

		if (count($orderStatusesData['data']) < 1) {
			return false;
		}
//        print_r($orderStatusesData);
		foreach ($orderStatusesData['data'] as $id => $orderStatusInfo) {
            $orderStatusesSaved[$orderStatusInfo['Id']] = $orderStatusInfo['Id'];
            if ($orderStatusInfo['Id']!= 4303) continue;
            if (Order::getCartIdStatic($orderStatusInfo['Id'])) {
                $orderStatusesSaved[$orderStatusInfo['Id']] = $orderStatusInfo['Id'];
                $order = new Order($orderStatusInfo['Id']);

                print_r($orderStatusInfo);
                if (isset($orderStatusInfo['WebState']) && ((int)$orderStatusInfo['WebState'] != $order->getCurrentState())) {
                    echo("WebState = ".$orderStatusInfo['WebState'].'/r/n');
                    try {
                        $order->setCurrentState((int)$orderStatusInfo['WebState']);
                    } catch (\Exception $e) {
                        echo("Error = ".$e->getMessage().'/r/n');
                    }
                }
            }
		}

		return $orderStatusesSaved;
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
		$this->onlyChanges = $onlyChanges;

		$savedIds = $this->saveOrderStatuses();

		return $this->confirmationRequest($savedIds);

	}

	protected function resetId($currentId, $newId)
	{
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_group` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_lang` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_product` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_shop` SET id_category =' . $newId . ' where id_category =' . $currentId);
	}
}