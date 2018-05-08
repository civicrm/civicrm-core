<?php
/**
 * @file
 *
 * Specify default components.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $e->getModel()->components = array('CiviEvent', 'CiviContribute', 'CiviMember', 'CiviMail', 'CiviReport');

  }, \Civi\Setup::PRIORITY_PREPARE);
