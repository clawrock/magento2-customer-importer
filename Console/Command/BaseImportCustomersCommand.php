<?php

namespace ClawRock\CustomerImporter\Console\Command;

use ClawRock\CustomerImporter\Api\CustomerImporterSettingsInterface;
use ClawRock\CustomerImporter\Model\CustomerImporter;
use function sprintf;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseImportCustomersCommand extends Command
{
    const OPTION_CUSTOMERS_PATH = 'customers';

    /** @var CustomerImporter  */
    protected $customerImporter;

    /** @var \Magento\Framework\App\State  */
    protected $state;

    public function __construct(
        CustomerImporter $customerImporter,
        \Magento\Framework\App\State $state
    ) {
        $this->customerImporter = $customerImporter;
        $this->state = $state;

        parent::__construct('clawrock:import:customers');
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Import customers');
        $this->setDefinition([
            new InputOption(static::OPTION_CUSTOMERS_PATH, null, InputOption::VALUE_REQUIRED, 'Customers path'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->emulateAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND, function () use ($input) {
            $this->customerImporter->import($this->prepareSettings($input));

            echo sprintf('Import time: %fs', $this->customerImporter->getImportTime());
        });
    }

    abstract public function prepareSettings(InputInterface $input): CustomerImporterSettingsInterface;
}
