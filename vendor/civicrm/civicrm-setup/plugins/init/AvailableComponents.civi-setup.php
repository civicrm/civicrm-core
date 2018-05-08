<?php
/**
 * @file
 *
 * Build a list of available CiviCRM components.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    /**
     * @var \Civi\Setup\Model $m
     */
    $m = $e->getModel();
    $comps = array(
      'CiviContribute',
      'CiviEvent',
      'CiviMail',
      'CiviMember',
      'CiviCase',
      'CiviPledge',
      'CiviReport',
      'CiviCampaign',
      'CiviGrant',
    );
    $m->setField('components', 'options', array_combine($comps, $comps));

  }, \Civi\Setup::PRIORITY_PREPARE);
