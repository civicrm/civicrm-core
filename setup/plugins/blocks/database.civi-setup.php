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

    $ctrl->blocks['database'] = [
      'is_active' => ($e->getModel()->cms === 'Standalone'),
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'database.tpl.php',
      'class' => '',
      'weight' => 15,
    ];
    if (empty($ctrl->blocks['database']['is_active'])) {
      return;
    }

    $webDefault = ['server' => '127.0.0.1:3306', 'database' => 'civicrm', 'username' => '', 'password' => ''];

    if ($e->getMethod() === 'GET') {
      $e->getModel()->db = $webDefault;
    }
    elseif ($e->getMethod() === 'POST') {
      $db = $e->getField('db', $webDefault);

      foreach (['server', 'database', 'username', 'password'] as $field) {
        $e->getModel()->db[$field] = $db[$field];
      }
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
