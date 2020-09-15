<?php
/**
 * @file
 *
 * Configure settings on the newly populated database.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->lang) {
      \Civi\Setup::log()->info('[SetLanguage.civi-setup.php] Set default language to ' . $e->getModel()->lang);
      \Civi::settings()->set('lcMessages', $e->getModel()->lang);

      // Ensure that post-install messages are displayed in the new locale.
      // Note: This arguably shouldn't be necessary since `$tsLocale` is generally setup before installation,
      // but it may get trampled during bootstrap.
      $domain = CRM_Core_BAO_Domain::getDomain();
      \CRM_Core_BAO_ConfigSetting::applyLocale(\Civi::settings($domain->id), $domain->locales);
    }
  }, \Civi\Setup::PRIORITY_LATE + 400);
