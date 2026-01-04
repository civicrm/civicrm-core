<?php
if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    if (!$e->getModel()->syncUsers) {
      $e->getModel()->syncUsers = TRUE;
    }

  }, \Civi\Setup::PRIORITY_START);


\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Register blocks', basename(__FILE__)));

    /**
     * @var \Civi\Setup\UI\SetupController $ctrl
     */
    $ctrl = $e->getCtrl();

    $ctrl->blocks['sync-users'] = array(
      'is_active' => ($e->getModel()->cms !== 'Standalone'),
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'sync-users.tpl.php',
      'class' => 'if-no-errors',
      'weight' => 55,
    );

    if ($e->getMethod() === 'POST') {
      $e->getModel()->syncUsers = !empty($e->getField('syncUsers'));
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
