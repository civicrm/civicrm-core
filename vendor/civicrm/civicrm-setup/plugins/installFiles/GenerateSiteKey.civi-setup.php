<?php
/**
 * @file
 *
 * Generate the site key.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));

    $e->getModel()->siteKey = \CRM_Utils_String::createRandom(32, \CRM_Utils_String::ALPHANUMERIC);

  }, \Civi\Setup::PRIORITY_PREPARE);
