<?php
/**
 * @file
 *
 * Generate the signing key(s).
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

  if (empty($e->getModel()->signKeys)) {
    $e->getModel()->signKeys = ['jwt-hs256:hkdf-sha256:' . $toAlphanum(random_bytes(40))];
    // toAlpanum() occasionally loses a few bits of entropy, but random_bytes() has significant excess, so it's still more than ample for 256 bit hkdf.
  }

  if (is_string($e->getModel()->signKeys)) {
    $e->getModel()->signKeys = [$e->getModel()->signKeys];
  }

    \Civi\Setup::log()->info(sprintf('[%s] Done %s', basename(__FILE__), 'installFiles'));

  }, \Civi\Setup::PRIORITY_PREPARE);
