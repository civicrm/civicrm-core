<?php
/**
 * @file
 *
 * By default, enable the "standaloneusers" extension on "Standalone" UF. Setup credentials.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    if ($e->getModel()->cms !== 'Standalone') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    $e->getModel()->extensions[] = 'standaloneusers';
    if (empty($e->getModel()->extras['adminPass'])) {
      $toAlphanum = function ($bits) {
        return preg_replace(';[^a-zA-Z0-9];', '', base64_encode($bits));
      };
      $e->getModel()->extras['adminPass'] = $toAlphanum(random_bytes(8));
    }
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if (!in_array('standaloneusers', $e->getModel()->extensions)) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installDatabase'));

    $security = \Civi\Standalone\Security::singleton();
    // $security->se
  }, \Civi\Setup::PRIORITY_LATE);
