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
use PrestaShop\Module\SoftOneERP\Classes\CustomerSendedObject;
use PrestaShop\Module\SoftOneERP\Classes\SkuObject;
use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

/**
 * Class responsible for modifications needed during installation/uninstallation of the module.
 */
class InstallDatabase
{
	/**
	 * array of instanced ObjectModel
	 */
	protected $tablesDefinition = array();

	/**
	 * Associations var type and sql type
	 * @var string[]
	 */
	protected $typeParams = array(
		'1' => 'int(11)',
		'2' => 'tinyint(1)',
		'3' => 'varchar(254)',
		'4' => 'decimal(5,2)',
		'5' => 'datetime',
		'6' => 'text'
	);

	public function __construct()
	{
		$this->tablesDefinition = [new SkuObject(), new CustomerSendedObject()];
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
		if (!$this->installTables()) {
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
		if (!$this->uninstallTables()) {
			return false;
		}

		return true;
	}


	/** Install the database modifications required for this module.
	 *
	 * @return bool
	 */
	protected function installTables(): bool
	{
		$queries = array();
		foreach ($this->tablesDefinition as $definition) {
			/**
			 * @var $definition ObjectModel
			 */
			$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $definition::$definition['table'] .
				'` (`' . $definition::$definition['primary'] . '` int(11) NOT NULL AUTO_INCREMENT,';

			foreach ($definition::$definition['fields'] as $name => $field) {
				$sql .= '`' . $name . '` ' . $this->typeParams[$field['type']] . ' ' . ($field['required'] ? 'NOT' : '') . ' NULL,';
			}
			$sql .= 'PRIMARY KEY (`' . $definition::$definition['primary'] . '`) ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

			$queries[] = $sql;
		}
		return $this->executeQueries($queries);
	}

	/**
	 * Uninstall database modifications.
	 *
	 * @return bool
	 */
	protected function uninstallTables(): bool
	{
		$queries = array();
		foreach ($this->tablesDefinition as $definition) {
			/**
			 * @var $definition ObjectModel
			 */
			$sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $definition::$definition['table'] . '`;';

			$queries[] = $sql;
		}

		return $this->executeQueries($queries);
	}

	/**
	 * A helper that executes multiple database queries.
	 *
	 * @param array $queries
	 *
	 * @return bool
	 */
	private function executeQueries(array $queries): bool
	{
		foreach ($queries as $query) {
			if (!Db::getInstance()->execute($query)) {
				return false;
			}
		}

		return true;
	}

}