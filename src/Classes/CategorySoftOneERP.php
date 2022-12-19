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

class CategorySoftOneERP extends AbstractClientSoftOneERP implements SoftOneErpClientInterface
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
        if (empty($this->onlyChanges)) $this->onlyChanges = false;
		$response = $this->client->request(
			'POST',
			$this->baseUrl . 'js/RDCJS/getcategories',
			[
				'json' => [
					'clientID' => $this->getClientId(),
					'onlyChanges' => $this->onlyChanges,
				]
			]);

		$statusCode = $response->getStatusCode();
        print_r([
            'clientID' => $this->getClientId(),
            'onlyChanges' => $this->onlyChanges,
        ]);
		if (200 !== $statusCode) {
            print_r($response);
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

        print_r($data);

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
	public function saveCategories()
	{
		$categoryAssociations = [];
		$this->errors = [];
		$categorySaved = [];
		$categoiesData = $this->getEntities();

		if (!$categoiesData['success']) {
			return false;
		}

		if (count($categoiesData['Data']) < 1) {
			return false;
		}

		foreach ($categoiesData['Data'] as $id => $categoryInfo) {
			$idCategory = (int)($categoryInfo['Id'] + 2);
			$category = new \Category();

			if ($idCategory && \Category::existsInDatabase((int)$idCategory, 'category')) {
				$category = new \Category($idCategory);
			}

			$id_lang = \Language::getIdByIso('en');
			$category->name = [
				$id_lang => $categoryInfo['NameEn'],
				\Language::getIdByIso('el') => $categoryInfo['Name'],
			];

			$categoryRewrite = \Tools::link_rewrite($category->name[$id_lang], true);
			$category->link_rewrite = [
				$id_lang => $categoryRewrite,
				\Language::getIdByIso('el') => $categoryRewrite,
			];

			$category->id_parent = \Configuration::get('PS_HOME_CATEGORY');
			if (isset($categoryInfo['ParentId']) && isset($categoryAssociations[$categoryInfo['ParentId'] + 2])) {
				$category->id_parent = $categoryAssociations[$categoryInfo['ParentId'] + 2];
			}

			if (($category->validateFields(false, true)) === true &&
				($category->validateFieldsLang(false, true)) === true &&
				$category->save()) {
				if ($category->id != $idCategory) $this->resetId($category->id, $idCategory);
				$categorySaved[] = $idCategory;
				$categoryAssociations[$idCategory] = $category->id;
                $category->addGroupsIfNoExist(1);
			} else {

				$this->errors[] = \Context::getContext()->getTranslator()->trans(
					'%category_name% (ID: %id%) cannot be saved',
					[
						'%category_name%' => \Tools::htmlentitiesUTF8($category->name),
						'%id%' => !empty($categoryInfo['Id']) ? \Tools::htmlentitiesUTF8($categoryInfo['Id']) : 'null',
					],
					'Admin.Advparameters.Notification'
				);
			}

		}

		return $categorySaved;
	}

	protected function truncateTable()
	{
		$res = true;

		$res &= \Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category`
                        WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)\Configuration::get('PS_ROOT_CATEGORY') . ')');
		$res &= \Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_lang`
                        WHERE id_category NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)\Configuration::get('PS_ROOT_CATEGORY') . ')');
		$res &= \Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_shop`
                        WHERE `id_category` NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)\Configuration::get('PS_ROOT_CATEGORY') . ')');
		$res &= \Db::getInstance()->execute('
                        DELETE FROM `' . _DB_PREFIX_ . 'category_group`
                        WHERE `id_category` NOT IN (' . (int)Configuration::get('PS_HOME_CATEGORY') . ', ' . (int)\Configuration::get('PS_ROOT_CATEGORY') . ')');
		$res &= \Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'category` AUTO_INCREMENT = 3');
		$res &= \Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'group_reduction`');

		foreach (scandir(_PS_CAT_IMG_DIR_) as $d) {
			if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $d)) {
				unlink(_PS_CAT_IMG_DIR_ . $d);
			}
		}

		return $res;
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

		$savedIds = $this->saveCategories();
		foreach ($savedIds as $id => &$item)
		{
			$item-=2;
		}

		return $this->confirmationRequest($savedIds);

	}

	protected function resetId($currentId, $newId)
	{
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_group` SET id_category =' . $newId . ' where id_category =' . $currentId);
//		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_lang` SET id_category =' . $newId . ' where id_category =' . $currentId);
//		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_product` SET id_category =' . $newId . ' where id_category =' . $currentId);
		\Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'category_shop` SET id_category =' . $newId . ' where id_category =' . $currentId);
	}
}