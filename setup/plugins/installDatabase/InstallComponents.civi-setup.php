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
    if (empty($e->getModel()->components)) {
      throw new \Exception("System must have at least one active component.");
    }

    // Components now have inter-dependencies with extensions (e.g. `CiviCase` depends on `afform`).
    // Defer installation to the next step (InstallExtensions.civi-setup.php) which handles both concurrently.
    $components = CRM_Core_Component::getComponents();
    $extensions = array_map(fn($c) => $components[$c]->getExtensionName(), $e->getModel()->components);

    \Civi\Setup::log()->info(sprintf('[InstallComponents.civi-setup.php] Mapped components (%s) to extensions (%s)',
      implode(" ", $e->getModel()->components),
      implode(" ", $extensions)
    ));

    \Civi::settings()->set('enable_components', []);
    $e->getModel()->extensions = array_unique(array_merge($e->getModel()->extensions, $extensions));

  }, \Civi\Setup::PRIORITY_LATE + 300);
