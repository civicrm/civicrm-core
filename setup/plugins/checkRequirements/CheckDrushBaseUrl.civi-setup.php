<?php
/**
 * @file
 *
 * Verify that the CMS base URL is well-formed.
 *
 * Ex: When installing via CLI, the URL cannot be determined automatically.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {
    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'checkRequirements'));
    $model = $e->getModel();

    if (!$model->cmsBaseUrl) {
      return;
    }

    if (\Civi\Setup\DrupalUtil::isDrush() && preg_match(';^https?://default/?;', $model->cmsBaseUrl)) {
      // If you run "drush8 en civicrm", it may fabricate the URL as "http://default/". Not good enough b/c this will be stored for future use..
      $e->addError('system', 'drushUrl', "Please specify a realistic site URL (Ex: drush -l http://example.com:456 ...).");
    }
  });
