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

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

class SkuObject extends ObjectModel
{
	/**
	 * Primary key
	 * @var int
	 */
	public $id_sku;

	/**
	 * Product ID
	 * @var int
	 */
	public $id_product;

	/**
	 * Associative sku
	 * @var string
	 */
	public $sku;

	/**
	 * @var array
	 */
	public static $definition = array(
		'table' => 'soe_skus',
		'primary' => 'id_sku',
		'fields' => array(
			'id_product' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			'sku' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255),
		),
	);

	/**
	 * Get exist Sku by Id
	 *
	 * @param $id_sku
	 * @return SkuObject
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function getById($id_sku)
	{
		return new SkuObject($id_sku);
	}

	public function existSKU($productId, $sku)
	{
		$query = new \DbQuery();
		$query->select('id_sku');
		$query->from(self::$definition['table']);
		$query->where('id_product = '.(int)$productId. ' AND sku="'.$sku.'"');

		if ($skus = \Db::getInstance()->executeS($query)) {
			return true;
		}

		return false;
	}
}
