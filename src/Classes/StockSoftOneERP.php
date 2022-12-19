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
use PrestaShop\PrestaShop\Adapter\Entity\StockAvailable;
use PrestaShop\PrestaShop\Adapter\Entity\StockManagerFactory;
use PrestaShop\PrestaShop\Adapter\Entity\Stock;

class StockSoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
{
	/**
	 * Getting stokes from API
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
			$this->baseUrl . 'js/RDCJS/getproductcombinationstock',
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
	 * Save stokes from API
	 * @return array|false
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function saveStocks()
	{
		$this->errors = [];
		$stockSaved = [];
		$stocksData = $this->getEntities();

		if (!$stocksData['success']) {
			return false;
		}

		if (count($stocksData['data']) < 1) {
			return false;
		}

		foreach ($stocksData['data'] as $id => $stockInfo) {

			$query = new \DbQuery();
			$query->select('id_product_attribute');
			$query->from('product_attribute');
			$query->where('id_product = ' . bqSQL($stockInfo['id']));
			if ($idsAttributeProduct = \Db::getInstance()->executeS($query)) {
				$idsAttributeProduct = array_column($idsAttributeProduct, 'id_product_attribute');
                print_r($stockInfo);
				foreach ($stockInfo['product_combination_stock'] as $combinations) {
					$combinationsIds = [];
					foreach ($combinations['product_combination_options'] as $option) {
						$compileId = str_replace("_", "", $option['product_variation_option_id']);
						$idsAttributeSql = 'SELECT `id_product_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute_combination` WHERE `id_attribute` = ' . bqSQL($compileId) ;
						$idsAttribute = \Db::getInstance()->executeS($idsAttributeSql);
						$combinationsIds[] = array_column($idsAttribute, 'id_product_attribute');;
					}
                    if (count($combinationsIds) > 1)
                        $combinationsIds = array_intersect($combinationsIds[0], $combinationsIds[1]);
                    else
                        $combinationsIds = array_shift($combinationsIds);

					$idAttribute = array_intersect($idsAttributeProduct, $combinationsIds);
					$idAttribute = array_shift($idAttribute);
                    $productId = \Product::getIdByReference(str_pad($stockInfo['sku'], 6, 0, STR_PAD_LEFT));
                    print_r($productId);
                    $stockAvailableId = StockAvailable::getStockAvailableIdByProductId($productId,$idAttribute);
                    print_r($stockAvailableId);
                    if (!empty($stockAvailableId))
					    $stock = new \StockAvailable($stockAvailableId);
                    else
                        $stock = new \StockAvailable();
					$stock_params = [
						'id_product_attribute' => (empty($idAttribute))?0:$idAttribute,
						'id_product' => $productId,
						'id_shop' => 1,
						'quantity' => ($combinations['stock_quantity'] > 0) ? $combinations['stock_quantity'] : 0,
					];
					$attributeProduct = new \Attribute($idAttribute);

					// saves stock in warehouse
					$stock->hydrate($stock_params);
					$stock->save();
					if (!isset($stockSaved[$stockInfo['id']])) $stockSaved[$stockInfo['id']] = $stockInfo['id'];
				}

			} else {
                print_r($stockInfo);
                $productId = \Product::getIdByReference(str_pad($stockInfo['sku'], 6, 0, STR_PAD_LEFT));
                print_r($productId);
                $stock_params = [
                    'id_product_attribute' => 0,
                    'id_product' => $productId,
                    'id_shop' => 1,
                    'quantity' => ($stockInfo['stock_quantity'] > 0) ? $stockInfo['stock_quantity'] : 0,
                ];
                $stockAvailableId = StockAvailable::getStockAvailableIdByProductId($productId,0);
                print_r($stockAvailableId);
                if (!empty($stockAvailableId))
                    $stock = new \StockAvailable($stockAvailableId);
                else
                    $stock = new \StockAvailable();
                $stock->hydrate($stock_params);
                $stock->save();
                if (!isset($stockSaved[$stockInfo['id']])) $stockSaved[$stockInfo['id']] = $stockInfo['id'];
            }
		}

		return $stockSaved;
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
		if (!$onlyChanges) $this->truncateTable();
		$savedIds = $this->saveStocks();

		return $this->confirmationRequest($savedIds);

	}

	/**
	 * @return bool
	 * @throws \PrestaShopDatabaseException
	 */
	protected function truncateTable()
	{
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_available`');

		return true;
	}


}