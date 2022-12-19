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
namespace PrestaShop\Module\SoftOneERP\Controller\Admin\Improve\AdvancedParameters;

use PrestaShop\Module\SoftOneERP\Form\SoftOneERPFormDataProvider;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use PrestaShop\Module\SoftOneERP\Classes\AbstractClientSoftOneERP;

/**
 * Class AdminSoftOneERPController.
 *
 * @ModuleActivated(moduleName="softoneerp", redirectRoute="admin_module_manage")
 */
class AdminSoftOneERPController extends FrameworkBundleAdminController
{
	/**
	 *
	 * @param Request $request
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	public function formAction(Request $request)
	{
		$this->get('prestashop.module.softoneerp_block.form_provider')->getInstance();
		$form = $this->get('prestashop.module.softoneerp_block.form_handler')->getForm();

		return $this->render('@Modules/softoneerp/views/templates/admin/form.html.twig', [
			'blockForm' => $form->createView(),
			'enableSidebar' => true,
			'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
		]);

	}

	/**
	 *
	 * @param Request $request
	 *
	 * @return RedirectResponse|Response
	 *
	 * @throws \Exception
	 */
	public function saveProcessAction(Request $request)
	{
		return $this->processForm($request, 'Successful save.');
	}

	/**
	 * @param Request $request
	 * @param string $successMessage
	 *
	 * @return Response|RedirectResponse
	 *
	 * @throws \Exception
	 */
	private function processForm(Request $request, $successMessage)
	{
		/** @var SoftOneERPFormDataProvider $formProvider */
		$formProvider = $this->get('prestashop.module.softoneerp_block.form_provider');
		$formProvider->getInstance();

		/** @var FormHandlerInterface $formHandler */
		$formHandler = $this->get('prestashop.module.softoneerp_block.form_handler');
		$form = $formHandler->getForm();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$saveErrors = $formHandler->save($data);

			if (0 === count($saveErrors)) {
				$this->addFlash('success', $this->trans($successMessage, 'Admin.Notifications.Success'));

				return $this->redirectToRoute('admin_softoneerp_block_form');
			}

			$this->flashErrors($saveErrors);
		}

		return $this->render('@Modules/softoneerp/views/templates/admin/form.html.twig', [
			'blockForm' => $form->createView(),
			'enableSidebar' => true,
			'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
		]);
	}

}