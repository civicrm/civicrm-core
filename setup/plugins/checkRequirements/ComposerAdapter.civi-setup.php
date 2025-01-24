<?php
/**
 * @file
 *
 * Re-enforce any PHP-PECL requirements from `composer.{json,lock}` as part of the installer.
 * This speaks to platforms like SA, WP, BD, D7 where admins do not directly run `composer install`.
 */

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
    $loadedExtensions = get_loaded_extensions();
    foreach ($lock['platform'] as $key => $constraint) {
      if (str_starts_with($key, 'ext-')) {
        $extension = substr($key, 4);

        if (!in_array($extension, $loadedExtensions)) {
          $e->addError('system', $key, sprintf('The PHP extension "%s" is not installed.', $extension));
        }
        else {
          $e->addInfo('system', $key, sprintf('The PHP extension "%s" is installed.', $extension));
        }

        // The above (basic existence check) is much better than status quo.
        // It would be nicer to use composer/semver's `Semver::satisfies`, but then you'd need to deal with conflict-y risks across platforms (eg J4/J5).
        //
        // $version = phpversion($extension);
        // if (!\Composer\Semver\Semver::satisfies($version, $constraint)) { .... }
        //   $e->addError('system', 'php_' . $extension, sprintf('The PHP extension \"%s\" (%s) does not satisfy constraint (%s).', $extension, $version, $constraint));
        // }
      }
    }
  });
