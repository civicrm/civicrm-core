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

    $toAlphanum = function($bits) {
      return preg_replace(';[^a-zA-Z0-9];', '', base64_encode($bits));
    };

  if (!empty($e->getModel()->siteKey)) {
    // skip
  }
  elseif (function_exists('random_bytes')) {
    $e->getModel()->siteKey = $toAlphanum(random_bytes(32));
  }
  elseif (function_exists('openssl_random_pseudo_bytes')) {
    $e->getModel()->siteKey = $toAlphanum(openssl_random_pseudo_bytes(32));
  }
  else {
    throw new \RuntimeException("Failed to generate a random site key");
  }

    \Civi\Setup::log()->info(sprintf('[%s] Done %s', basename(__FILE__), 'installFiles'));

  }, \Civi\Setup::PRIORITY_PREPARE);
