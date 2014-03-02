<?php

namespace Civi\Tools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeGen extends Command
{
  protected function configure()
  {
    $this->setName('generate')
         ->setDescription('Generate automatically generated parts of CiviCRM')
         ->addOption('cms', 'c', InputOption::VALUE_REQUIRED, 'The CMS to build code for (default: Drupal)')
         ->addOption('civi-version', 'r', InputOption::VALUE_REQUIRED, 'The version of CiviCRM to generate code for. Defaults to version in xml/version.xml')
         ->addOption('schema', 's', InputOption::VALUE_REQUIRED, 'The path to the XML schema file. Default xml/schema/Schema.xml');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $options = array();
    if ($input->getOption('cms')) {
      $options['cms'] = $input->getOption('cms');
    }
    if ($input->getOption('civi-version')) {
      $options['civi-version'] = $input->getOption('civi-version');
    }
    if (getenv('CIVICRM_GENCODE_DIGEST')) {
      $options['digest-path'] = getenv('CIVICRM_GENCODE_DIGEST');
    }
    if ($input->getOption('schema')) {
      $options['schema-path'] = $input->getOption('schema');
    }
    $code_generator = new \CRM_Core_CodeGen_Main($options);
    $code_generator->main();
  }
}
