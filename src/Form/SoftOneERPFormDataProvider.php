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
 *  @author    BelVG
 *  @copyright 2007-2021 BelVG
 *  @license  https://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

namespace PrestaShop\Module\SoftOneERP\Form;


use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

/**
 * Class SoftOneERPFormDataProvider.
 */
class SoftOneERPFormDataProvider implements FormDataProviderInterface
{

	/**
	 * @var array
	 */
	private $languages;

	/**
	 * @var int
	 */
	private $shopId;

	/**
	 * SoftOneERPFormDataProvider constructor.
	 *
	 * @param array $languages
	 * @param int $shopId
	 */
	public function __construct(
		array $languages,
		$shopId
	)
	{
		$this->languages = $languages;
		$this->shopId = $shopId;
	}

	/**
	 * @return array
	 *
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function getData()
	{
		$setting = json_decode(\Configuration::get('MODULE_SOFT_ONE_ERP_SETTING'), true);

		return ['softoneerp_block' => [
			'mode' => $setting['mode'],
			'login' => $setting['login'] ?? '',
			'password' => $setting['password'] ?? '',
			'baseurl' => $setting['baseurl'] ?? '',
			'appid' => $setting['appid'] ?? '',
			'company' => $setting['company'] ?? '',
			'branch' => $setting['branch'] ?? '',
			'module' => $setting['module'] ?? '',
			'refid' => $setting['refid'] ?? '',
		]];
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 *
	 * @throws \PrestaShop\PrestaShop\Adapter\Entity\PrestaShopDatabaseException
	 */
	public function setData(array $data)
	{
		$setting = json_encode($data['softoneerp_block']);
		\Configuration::updateValue('MODULE_SOFT_ONE_ERP_SETTING', $setting);

		return [];
	}

	/**
	 *
	 * @return SoftOneERPFormDataProvider
	 */
	public function getInstance()
	{
		return $this;
	}

}
