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

class CombinationSKUObject extends ObjectModel
{
	/**
	 * Primary key
	 * @var int
	 */
	public $id_soe_combination;

	/**
	 * Customer ID
	 * @var int
	 */
	public $id_customer;

	/**
	 * date update customer
	 * @var string
	 */
	public $date_upd;

	/**
	 * Return value from ERP
	 * @var int
	 */
	public $trdr;

	/**
	 * @var array
	 */
	public static $definition = array(
		'table' => 'soe_customers',
		'primary' => 'id_soe_customers',
		'fields' => array(
			'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			'trdr' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
		),
	);


	/**
	 * @param $customerId
	 * @return bool
	 * @throws \PrestaShopDatabaseException
	 */
	public static function existCustomer($customerId)
	{
		$query = new \DbQuery();
		$query->select('id_soe_customers');
		$query->from(self::$definition['table']);
		$query->where('id_customer = '.(int)$customerId);

		if ($soe = \Db::getInstance()->executeS($query)) {
			return $soe['id_soe_customers'];
		}

		return false;
	}

}
