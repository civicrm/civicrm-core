<?php
if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.run', function (\Civi\Setup\UI\Event\UIBootEvent $e) {

    \Civi\Setup::log()->info(sprintf('[%s] Parse inputs', basename(__FILE__)));

    /**
     * @var \Civi\Setup\UI\SetupController $ctrl
     */
    $ctrl = $e->getCtrl();
    $values = $e->getField('advanced', array());

    $placeholderDb = 'mysql://USER:PASS@HOST/DB';

    if (empty($values['db']) || $values['db'] === $placeholderDb) {
      $e->getModel()->extras['advanced']['db'] = $placeholderDb;
    }
    else {
      $e->getModel()->extras['advanced']['db'] = trim($values['db']);
      $e->getModel()->db = \Civi\Setup\DbUtil::parseDsn(trim($values['db']));
    }

  }, \Civi\Setup::PRIORITY_LATE);

\Civi\Setup::dispatcher()
  ->addListener('civi.setupui.boot', function (\Civi\Setup\UI\Event\UIBootEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Register blocks', basename(__FILE__)));

    /**
     * @var \Civi\Setup\UI\SetupController $ctrl
     */
    $ctrl = $e->getCtrl();

    $ctrl->blocks['advanced'] = array(
      'is_active' => TRUE,
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'advanced.tpl.php',
      'class' => '',
      'weight' => 60,
    );
  }, \Civi\Setup::PRIORITY_PREPARE);
