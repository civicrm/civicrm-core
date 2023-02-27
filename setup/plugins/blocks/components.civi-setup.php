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

    $ctrl->blocks['components'] = array(
      'is_active' => TRUE,
      'file' => __DIR__ . DIRECTORY_SEPARATOR . 'components.tpl.php',
      'class' => 'if-no-errors',
      'weight' => 50,
      'component_labels' => array(
        'CiviContribute' => ts('Accept donations and payments'),
        'CiviEvent' => ts('Accept event registrations'),
        'CiviMail' => ts('Send email blasts and newsletters'),
        'CiviMember' => ts('Manage recurring memberships'),
        'CiviCase' => ts('Track case histories'),
        'CiviPledge' => ts('Accept pledges'),
        'CiviReport' => ts('Generate reports'),
        'CiviCampaign' => ts('Organize campaigns, surveys, and petitions'),
      ),
    );

    if ($e->getMethod() === 'POST' || is_array($e->getField('components'))) {
      $e->getModel()->components = array_keys($e->getField('components'));
    }

  }, \Civi\Setup::PRIORITY_PREPARE);
