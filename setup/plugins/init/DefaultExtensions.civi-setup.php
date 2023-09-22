<?php
/**
 * @file
 *
 * Choose some extensions to auto-install.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $e->getModel()->extensions[] = 'org.civicrm.search_kit';
    $e->getModel()->extensions[] = 'org.civicrm.afform';
    $e->getModel()->extensions[] = 'authx';

  });
