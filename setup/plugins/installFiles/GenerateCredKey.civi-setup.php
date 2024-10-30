<?php
/**
 * @file
 *
 * Generate the credential key(s).
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installFiles', function (\Civi\Setup\Event\InstallFilesEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installFiles'));

    $toAlphanum = function($bits) {
      return preg_replace(';[^a-zA-Z0-9];', '', base64_encode($bits));
    };

  if (empty($e->getModel()->credKeys)) {
    $e->getModel()->credKeys = ['aes-cbc:hkdf-sha256:' . $toAlphanum(random_bytes(37))];
  }

  if (is_string($e->getModel()->credKeys)) {
    $e->getModel()->credKeys = [$e->getModel()->credKeys];
  }

  if (empty($e->getModel()->deployID)) {
    $e->getModel()->deployID = $toAlphanum(random_bytes(10));
  }

    \Civi\Setup::log()->info(sprintf('[%s] Done %s', basename(__FILE__), 'installFiles'));

  }, \Civi\Setup::PRIORITY_PREPARE);
