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

declare(strict_types=1);

namespace PrestaShop\Module\SoftOneERP\Install;

use Db;
use Module;

/**
 * Class responsible for modifications needed during installation/uninstallation of the module.
 */
class Installer
{
	protected $databaseInstaller;

	public function __construct()
	{
		$this->databaseInstaller = new InstallDatabase();
	}

	/**
	 * Module's installation entry point.
	 *
	 * @param Module $module
	 *
	 * @return bool
	 */
	public function install(Module $module): bool
	{
		if (!$this->registerHooks($module) || !$this->databaseInstaller->install($module)) {
			return false;
		}

		return true;
	}

	/**
	 * Module's uninstallation entry point.
	 *
	 * @return bool
	 */
	public function uninstall(): bool
	{
		if (!$this->databaseInstaller->uninstall()) {
			return false;
		}
		return true;
	}

	/**
	 * Register hooks for the module.
	 *
	 * @param Module $module
	 *
	 * @return bool
	 */
	public function registerHooks(Module $module): bool
	{
		// Hooks available in the order view page.
		$hooks = [
			'displayHeader',
		];

		return (bool)$module->registerHook($hooks);
	}

}