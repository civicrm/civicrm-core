<?php
/**
 * @file
 *
 * Activate Civi components on the newly populated database.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));
    $model = $e->getModel();

    if (empty($model->components)) {
      $e->addError('system', 'components', "System must have at least one active component.");
      return;
    }
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    \Civi\Setup::log()->info('[InstallComponents.civi-setup.php] Activate components: ' . implode(" ", $e->getModel()->components));

    if (empty($e->getModel()->components)) {
      throw new \Exception("System must have at least one active component.");
    }

    \Civi::settings()->set('enable_components', $e->getModel()->components);
  }, \Civi\Setup::PRIORITY_LATE + 300);
