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

    if (!$model->cmsBaseUrl || !preg_match('/^https?:/', $model->cmsBaseUrl)) {
      $e->addError('system', 'cmsBaseUrl', "The \"cmsBaseUrl\" ($model->cmsBaseUrl) is unavailable or malformed. Consider setting it explicitly.");
      return;
    }

    if (PHP_SAPI === 'cli' && strpos($model->cmsBaseUrl, dirname($_SERVER['PHP_SELF'])) !== FALSE) {
      $e->addError('system', 'cmsBaseUrl', "The \"cmsBaseUrl\" ($model->cmsBaseUrl) is unavailable or malformed. Consider setting it explicitly.");
      return;
    }

    $e->addInfo('system', 'cmsBaseUrl', 'The "cmsBaseUrl" appears well-formed.');
  });
