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

use PrestaShop\Module\FacetedSearch\Hook\AttributeGroup;
use PrestaShop\Module\FacetedSearch\Hook\Category;
use PrestaShop\Module\SoftOneERP\Interfaces\SoftOneErpClientInterface;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;


class ProductSoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
{
    /**
     * Association array Tax Group
     */
    const TAX_GROUPS = array(
        1 => 6,
        2 => 13,
        3 => 6.5,
        4 => 16,
        5 => 8,
        6 => 4,
        7 => 24
    );

	/**
	 * Getting products from API
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
			$this->baseUrl . 'js/RDCJS/getitems',
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
	 * Import Products from API with confirmation received data
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
		$savedIds = $this->saveProducts();
        if (count($savedIds) > 0)
            $this->refreshOldPrices();
		return $this->confirmationRequest($savedIds);

	}

	/**
	 * Save products from API
	 * @return array|false
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function saveProducts()
	{

		$this->errors = [];
		$productsSaved = [];
		if (!$this->onlyChanges) $this->truncateTable();

		$productsData = $this->getEntities();

		if (!$productsData['success']) {
			return false;
		}

		if (count($productsData['data']) < 1) {
			return false;
		}
		$int0 = 0;
		foreach ($productsData['data'] as $id => $productInfo) {

			if ($res = $this->saveProductOne($productInfo)) {
				$productsSaved[] = $res;
			}
			$int0++;
			if ($int0 > 100) break;
		}

		return $productsSaved;
	}

    /**
     * Save one entity
     * @param $info
     * @return int
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
	public function saveProductOne($info)
	{
		$id_product = (int)$info['id'];
		$product = new \Product();
		if ($id_product && \Product::existsInDatabase((int)$id_product, 'product')) {
			$product = new \Product($id_product);
		}
		$id_lang_en = \Language::getIdByIso('en');
		$id_lang_gr = \Language::getIdByIso('el');

		$default_values = [
			'name' => [
				$id_lang_en => $info['name_en'],
				$id_lang_gr => $info['name_gr'],
			],
			'description' => [
				$id_lang_en => $info['LongDescrEn'],
				$id_lang_gr => $info['LongDescr'],
			],
			'description_short' => [
				$id_lang_en => $info['ShortDescrEn'],
				$id_lang_gr => $info['ShortDescr'],
			],
			'active' => $info['Active'],
			'reference' => $info['sku'],
			'supplier_reference' => '',
			'ean13' => '',
			'upc' => '',
			'mpn' => '',
			'wholesale_price' => 0,
            'price' => $info['Price'] ? number_format(floatval($info['Price'] / ((100 + $info['Vat']) / 100)), 4, '.', '') : 0,
            'id_tax_rules_group' => array_search($info['Vat'], self::TAX_GROUPS),
			'ecotax' => 0,
			'quantity' => 0,
			'minimal_quantity' => 1,
			'low_stock_threshold' => null,
			'low_stock_alert' => false,
			'weight' => 0,
			'default_on' => null,
			'advanced_stock_management' => 0,
			'depends_on_stock' => 0,
			'available_date' => date('Y-m-d'),
		];
        print_r($default_values);


		$members = get_object_vars($product);
		foreach ($default_values as $k => $v) {
			if (array_key_exists($k, $members)) {
				$product->$k = $v;
			}
		}

		$categories = array(2);
		foreach ($info['categories'] as $category) {
			if ($category['is_master']) $product->id_category_default = $category['id'] + 2;
            $categoryObj = new \Category($category['id'] + 2);
            $categoryObjParents = $categoryObj->getAllParents();
            foreach ($categoryObjParents as $categoryObjParent) {
                if ($categoryObjParent->id == 1) continue;
                $categories[] = $categoryObjParent->id;
            }
			$categories[] = $category['id'] + 2;
		}

        $categories = array_unique($categories);

		$res = $product->save();
		if (!$res) {
			$this->errors[] = sprintf(
				\Context::getContext()->getTranslator()->trans('%1$s (ID: %2$s) cannot be saved', [], 'Admin.Advparameters.Notification'),
				!empty($info['name_en']) ? \Tools::safeOutput($info['name_en']) : 'No Name',
				!empty($info['id']) ? \Tools::safeOutput($info['id']) : 'No ID'
			);
            echo(sprintf(
                \Context::getContext()->getTranslator()->trans('%1$s (ID: %2$s) cannot be saved', [], 'Admin.Advparameters.Notification'),
                !empty($info['name_en']) ? \Tools::safeOutput($info['name_en']) : 'No Name',
                !empty($info['id']) ? \Tools::safeOutput($info['id']) : 'No ID'
            ));
		} else {
			if ($product->id != $info['id']) $this->resetId($product->id, $info['id']);
			$productSaved = (int)$info['id'];
			$product->id = $info['id'];
			$product->addToCategories($categories);

			$result = \Db::getInstance()->update(
				'product_shop',
				array(
					'price' => isset($info['Price'])?$info['Price']:0
				),
				'id_product = ' . (int)$info['id']
			);

			if (isset($info['alternative_skus'])) {
				$this->importSKU($info['id'], $info['alternative_skus']);
			}

			$this->importAttributes($id_product, $info['product_variations']);
		}

		return $productSaved;
	}

    /**
     * @param $id_product
     * @param $productVariations
     * @return void
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
	protected function importAttributes($id_product, $productVariations)
	{
		$id_lang_en = \Language::getIdByIso('en');
		$id_lang_gr = \Language::getIdByIso('el');
        print_r($productVariations);
		$productAttributes = array();
		foreach ($productVariations as $attributeInfo) {
			if (isset($attributeInfo['id'])) {
				if (!\AttributeGroup::existsInDatabase($attributeInfo['id'], 'attribute_group')) {
					$attributeGroup = new \AttributeGroup();
					$attributeGroup->name = [
						$id_lang_en => $attributeInfo['name_en'],
						$id_lang_gr => $attributeInfo['name_gr'],
					];
					$attributeGroup->public_name = [
						$id_lang_en => $attributeInfo['name_en'],
						$id_lang_gr => $attributeInfo['name_gr'],
					];
					$attributeGroup->group_type = 'select';
					$resAG = $attributeGroup->add();
					if ($resAG) {
						$this->resetId($attributeGroup->id, $attributeInfo['id'], 'attribute_group');
					}
				}
				foreach ($attributeInfo['product_variation_options'] as $attribute) {
					if (isset($attribute['Id'])) {
						$compileId = str_replace("_", "", $attribute['Id']);

						$productAttributes[$attributeInfo['id']][] = $compileId;
						if (!\Attribute::existsInDatabase($compileId, 'attribute')) {
							$attributeObj = new \Attribute();
							$attributeObj->name = [
								$id_lang_en => $attribute['DimNameEn'],
								$id_lang_gr => $attribute['DimName'],
							];
							$attributeObj->id_attribute_group = $attributeInfo['id'];
							$resA = $attributeObj->add();
							if ($resA) {
								$this->resetId($attributeObj->id, $compileId, 'attribute');
							}
						}
					}
				}
			}

			if (count($productAttributes)) {

				foreach ($productAttributes as $group1 => $attributes1) {
					foreach ($attributes1 as $attribute1)
						foreach ($productAttributes as $group2 => $attributes2) {
							if ($group2 == $group1) continue;
							foreach ($attributes2 as $attributes2) {


								$combinationModel = new \Combination();
								$combinationModel->id_product = $id_product;

								$res = $combinationModel->add(false);

								if ($res) {
									\Db::getInstance()->execute('
										INSERT IGNORE INTO ' . _DB_PREFIX_ . 'product_attribute_combination (id_attribute, id_product_attribute)
										VALUES (' . (int)$attribute1 . ',' . (int)$combinationModel->id . ')', false);
									\Db::getInstance()->execute('
										INSERT IGNORE INTO ' . _DB_PREFIX_ . 'product_attribute_combination (id_attribute, id_product_attribute)
										VALUES (' . (int)$attributes2 . ',' . (int)$combinationModel->id . ')', false);

								}
							}
						}
					break;
				}

			}
		}
	}

    /**
     * @param $productId
     * @param $alternativeSKUs
     * @return void
     * @throws \PrestaShopException
     */
	protected function importSKU($productId, $alternativeSKUs)
	{
		$container = SymfonyContainer::getInstance();
		foreach ($alternativeSKUs as $alternativeSKU) {
			/**
			 * @var SkuObject $skuObject
			 */
			$skuObject = $container->get('prestashop.module.softoneerp_classes.sku');
			if (!$skuObject->existSKU($productId, $alternativeSKU['id'])) {
				$skuObject->id_product = $productId;
				$skuObject->sku = $alternativeSKU['id'];
				$skuObject->save();
			}
		}
	}

	/**
	 * @return bool
	 * @throws \PrestaShopDatabaseException
	 */
	protected function truncateTable()
	{
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_shop`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'feature_product`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_lang`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'category_product`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_tag`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'specific_price_priority`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_carrier`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'cart_product`');
		if (count(\Db::getInstance()->executeS('SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'favorite_product\' '))) { //check if table exist
			\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'favorite_product`');
		}
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attachment`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_country_tax`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_download`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_group_reduction_cache`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_sale`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_supplier`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'warehouse_product_location`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_available`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'stock_mvt`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'customization_field`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'supply_order_detail`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'attribute_impact`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_shop`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_combination`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_attribute_image`');
		\Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'pack`');

		return true;
	}

	/**
	 * Reset ID for saving association with ERP data
	 * @param $currentId
	 * @param $newId
	 * @param string $entity
	 */
	protected function resetId($currentId, $newId, $entity = 'product')
	{
		switch ($entity) {
			case 'product':
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'product` SET id_product =' . $newId . ' where id_product =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'product_shop` SET id_product =' . $newId . ' where id_product =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'product_lang` SET id_product =' . $newId . ' where id_product =' . $currentId);
				break;
			case 'attribute_group':
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute_group` SET id_attribute_group =' . $newId . ' where id_attribute_group =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute_group_shop` SET id_attribute_group =' . $newId . ' where id_attribute_group =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute_group_lang` SET id_attribute_group =' . $newId . ' where id_attribute_group =' . $currentId);
				break;
			case 'attribute':
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute` SET id_attribute =' . $newId . ' where id_attribute =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute_shop` SET id_attribute =' . $newId . ' where id_attribute =' . $currentId);
				\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'attribute_lang` SET id_attribute =' . $newId . ' where id_attribute =' . $currentId);
				break;
		}

	}

    /**
     * Update prices
     * @return void
     */
    protected function refreshOldPrices()
    {
        \Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'product_shop as f set f.price = (select s.price FROM ' . _DB_PREFIX_ . 'product as s WHERE s.id_product=f.id_product) where 1;');
    }

}