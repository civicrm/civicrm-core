<?php
/**
 * @file
 *
 * Re-enforce any PHP-PECL requirements from `composer.{json,lock}` as part of the installer.
 * This speaks to platforms like SA, WP, BD, D7 where admins do not directly run `composer install`.
 */

use Composer\InstalledVersions;

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.checkRequirements', function (\Civi\Setup\Event\CheckRequirementsEvent $e) {

    $model = $e->getModel();
    $lockFile = $model->srcPath . '/composer.lock';
    if (!file_exists($lockFile)) {
      \Civi\Setup::log()->warning(sprintf('[%s] Skip Composer requirements. Missing civicrm-core:composer.lock', basename(__FILE__)));
      return;
    }

    $lock = json_decode(file_get_contents($lockFile), TRUE);
    if (empty($lock['platform'])) {
      \Civi\Setup::log()->warning(sprintf('[%s] Skip Composer requirements. The civicrm-core:composer.lock does not declare valid platform requirements.', basename(__FILE__)));
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Check Composer requirements', basename(__FILE__)));
    foreach ($lock['platform'] as $key => $constraint) {
      if (str_starts_with($key, 'ext-')) {
        $extension = substr($key, 4);
        $status = _composer_adapter_checkExtension($extension);
        switch ($status) {
          case 'installed':
            $e->addInfo('system', $key, sprintf('The PHP extension "%s" is installed.', $extension));
            break;

          case 'polyfill':
            $e->addWarning('system', $key, sprintf('For optimal performance, install the PHP extension "%s".', $extension));
            break;

          default:
            $e->addError('system', $key, sprintf('The PHP extension "%s" is not installed.', $extension));
        }
      }
    }
  });

function _composer_adapter_checkExtension(string $extension): string {
  $loadedExtensions = get_loaded_extensions();
  $polyfills = [
    // Unfortunately, Composer\InstalledVersions doesn't seem know about 'provide' metdata. So we need some hints.
    'mbstring' => ['symfony/polyfill-mbstring'],
  ];

  if (in_array($extension, $loadedExtensions)) {
    return 'installed';
  }

  if (isset($polyfills[$extension])) {
    foreach ($polyfills[$extension] as $polyfill) {
      if (InstalledVersions::isInstalled($polyfill)) {
        return 'polyfill';
      }
    }
  }

  return 'missing';
}
