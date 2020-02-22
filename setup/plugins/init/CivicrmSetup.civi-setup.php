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

    if (!$e->getModel()->setupPath) {
      $e->getModel()->setupPath = dirname(dirname(__DIR__));
    }

  }, \Civi\Setup::PRIORITY_START);
