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

namespace PrestaShop\Module\SoftOneERP\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslateTextType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SoftOneERPBlockType extends TranslatorAwareType
{
	/**
	 * @var array
	 */
	private $hookChoices;

	/**
	 * @var array
	 */
	private $cmsPageChoices;

	/**
	 * @var array
	 */
	private $productPageChoices;

	/**
	 * @var array
	 */
	private $staticPageChoices;

	/**
	 * LinkBlockType constructor.
	 *
	 * @param TranslatorInterface $translator
	 * @param array $locales
	 */
	public function __construct(
		TranslatorInterface $translator,
		array $locales
	) {
		parent::__construct($translator, $locales);
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('mode', HiddenType::class, [
//				'choices' => ['Test mode' => 0,'Work mode' => 1],
//				'label' => $this->trans('Mode', 'Modules.SoftOneERP.Admin'),
//				'multiple' => false,
//				'expanded' => true,
			])
			->add('baseurl', TextType::class, [
				'required' => true,
				'label' => $this->trans('API url', 'Modules.GroupUsers.Admin'),
			])
			->add('login', TextType::class, [
				'required' => true,
				'label' => $this->trans('Login', 'Modules.GroupUsers.Admin'),
			])
			->add('password', TextType::class, [
				'required' => true,
				'label' => $this->trans('Password', 'Modules.GroupUsers.Admin'),
			])
			->add('appid', TextType::class, [
				'required' => true,
				'label' => $this->trans('AppID', 'Modules.GroupUsers.Admin'),
			])
			->add('company', TextType::class, [
				'required' => true,
				'label' => $this->trans('Company', 'Modules.GroupUsers.Admin'),
			])
			->add('branch', TextType::class, [
				'required' => true,
				'label' => $this->trans('Branch', 'Modules.GroupUsers.Admin'),
			])
			->add('module', TextType::class, [
				'required' => true,
				'label' => $this->trans('Module', 'Modules.GroupUsers.Admin'),
			])
			->add('refid', TextType::class, [
				'required' => true,
				'label' => $this->trans('RefID', 'Modules.GroupUsers.Admin'),
			])
		;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'label' => false,
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix()
	{
		return 'module_softoneerp_block';
	}
}
