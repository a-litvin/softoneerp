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

namespace PrestaShop\Module\SoftOneERP\Command;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use PrestaShop\Module\SoftOneERP\Classes\AbstractClientSoftOneERP;
use Symfony\Component\Console\Input\InputArgument;

class SoftoneerpCommand extends Command
{
	protected $type;

    protected $onlyChanges = false;

	/**
	 * @var string
	 */
	protected static $defaultName = 'softoneerp:cron';

	protected function configure(): void
	{
		$this
			->addArgument('type', InputArgument::REQUIRED, 'The type of the command.')
            ->addArgument('onlyChanges', InputArgument::OPTIONAL, 'False if we want full update of entities.')
		;
	}

	public function __construct() {
		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->type = $input->getArgument('type');
        $this->onlyChanges = !empty($input->getArgument('onlyChanges'))?filter_var($input->getArgument('onlyChanges'), FILTER_VALIDATE_BOOLEAN):false;
		$io = new SymfonyStyle($input, $output);

		$container = SymfonyContainer::getInstance();
		$container->set('prestashop.module.softoneerp_classes.client', $container->get('prestashop.module.softoneerp_classes.client.'.$this->type));

		/** @var AbstractClientSoftOneERP $conector */
		$conector = $container->get('prestashop.module.softoneerp_classes.client');
		$clientData = $conector->authenticate();
		$io->text(var_export($clientData));

        $entitiesData = $conector->save($this->onlyChanges);
		$io->text(var_export($entitiesData));

		$io->title('Entity '.$this->type.' import has done!');
	}

}
