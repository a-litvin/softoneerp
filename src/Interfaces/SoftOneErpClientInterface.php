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

namespace PrestaShop\Module\SoftOneERP\Interfaces;

/**
 * Interface FileWriterInterface.
 */
interface SoftOneErpClientInterface
{
	/**
	 * Authorize API function
	 * @return false|mixed
	 **/
	public function authenticate();

	/**
	 * Get Entities from ERP
	 * @return mixed
	 */
	public function getEntities();

	/**
	 * Send data to ERP
	 * @param $onlyChanges
	 * @return mixed
	 */
	public function save($onlyChanges);


}
