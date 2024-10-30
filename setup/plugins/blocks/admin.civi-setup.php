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

    $ctrl->blocks['admin'] = [
      'is_active' => ($e->getModel()->cms === 'Standalone'),
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'admin.tpl.php',
      'class' => 'if-no-errors',
      'weight' => 35,
    ];

    if ($ctrl->blocks['admin']['is_active'] && $e->getMethod() === 'POST') {
      if ($e->getField('adminUser')) {
        $e->getModel()->extras['adminUser'] = $e->getField('adminUser');
      }
      if ($e->getField('adminPass')) {
        $e->getModel()->extras['adminPassWasSpecified'] = TRUE;
        $e->getModel()->extras['adminPass'] = $e->getField('adminPass');
      }
      if ($e->getField('adminEmail')) {
        $e->getModel()->extras['adminEmail'] = $e->getField('adminEmail');
      }
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
