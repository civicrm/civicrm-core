<?php
if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Register blocks', basename(__FILE__)));

    /**
     * @var \Civi\Setup\UI\SetupController $ctrl
     */
    $ctrl = $e->getCtrl();

    $ctrl->blocks['sample-data'] = [
      'is_active' => TRUE,
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'sample-data.tpl.php',
      'class' => 'if-no-errors',
      'weight' => 50,
    ];

    if ($e->getMethod() === 'POST') {
      $e->getModel()->loadGenerated = !empty($e->getField('loadGenerated'));
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
