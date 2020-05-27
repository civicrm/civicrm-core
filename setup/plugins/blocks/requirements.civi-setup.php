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

    $ctrl->blocks['requirements'] = array(
      'is_active' => TRUE,
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'requirements.tpl.php',
      'class' => 'if-problems',
      'weight' => 20,
      'severity_labels' => array(
        'info' => ts('Info'),
        'warning' => ts('Warning'),
        'error' => ts('Error'),
      ),
      'section_labels' => array(
        'database' => ts('Database'),
        'system' => ts('System'),
      ),
    );
  }, \Civi\Setup::PRIORITY_PREPARE);
