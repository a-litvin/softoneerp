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

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
	exit;
}

use PrestaShop\Module\SoftOneERP\Install\InstallerFactory;
use PrestaShopBundle\Security\Annotation\ModuleActivated;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Class SoftOneERP
 *
 * @ModuleActivated(moduleName="softoneerp", redirectRoute="admin_module_manage")
 */
class SoftOneERP extends Module
{

	/**
	 * @var array[]
	 */
	public $tabs = array(
		array(
			'name' => 'Soft1 ERP',
			'class_name' => 'AdminSoftOneERP',
			'visible' => true,
			'parent_class_name' => 'AdminAdvancedParameters',
		));

	/**
	 * SoftOneERP constructor.
	 */
	public function __construct()
	{
		$this->name = 'softoneerp';
		$this->tab = 'administration';
		$this->version = '1.0.0';
		$this->author = 'BelVG LLC';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = [
			'min' => '1.7',
			'max' => _PS_VERSION_
		];
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->trans('SoftOne ERP');
		$this->description = $this->trans('Module for integration ERP system from Soft1 Web Services');

		$this->confirmUninstall = $this->trans('Are you sure you want to uninstall?');


	}

	/**
	 * @return bool
	 */
	public function install()
	{
		if (!parent::install()) {
			return false;
		}

		$installer = InstallerFactory::create();

		return $installer->install($this);
	}

	/**
	 * @return bool
	 */
	public function uninstall()
	{
		$installer = InstallerFactory::create();

		return $installer->uninstall() && parent::uninstall();
	}

	/**
	 * @throws PrestaShopException
	 */
	public function getContent()
	{
		Tools::redirectAdmin(
			$this->context->link->getAdminLink('AdminSoftOneERP')
		);
	}

}