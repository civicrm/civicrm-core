<?php

/**
 * @file
 *
 * Default settings depending on the locale.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    // Get the current installation language
    $seedLanguage = $e->getModel()->lang;
    \Civi\Setup::log()->info(sprintf('[%s] Setup localization %s', basename(__FILE__), $seedLanguage));

    if (!$seedLanguage || $seedLanguage == 'en_US') {
      return;
    }

    // Get the corresponding settings file if any
    $fileName = \Civi::paths()->getPath("[civicrm.root]/settings/l10n/$seedLanguage/settings.default.json");
    $settingsParams = [];

    if (file_exists($fileName)) {
      // load the file and parse it
      $json = file_get_contents($fileName);
      $settings = json_decode($json, TRUE);

      if (!empty($settings)) {
        // get all valid settings
        $results = civicrm_api3('Setting', 'getfields', []);
        $validSettings = array_keys($results['values']);
        // add valid settings to params to send to api
        foreach ($settings as $setting => $value) {
          if (in_array($setting, $validSettings)) {
            $settingsParams[$setting] = $value;
          }
        }

        // ensure we don't mess with multilingual
        unset($settingsParams['languageLimit']);

        // support for enabled languages (option group)
        if (isset($settings['languagesOption']) && count($settings['languagesOption']) > 0) {
          \CRM_Core_BAO_OptionGroup::setActiveValues('languages', $settings['languagesOption']);
        }

        // set default currency in currencies_enabled (option group)
        if (isset($settings['defaultCurrency'])) {
          \CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies([$settings['defaultCurrency']], $settings['defaultCurrency']);
        }
      }
    }
    else {
      \Civi\Setup::log()->info(sprintf('[%s] No defaults settings found for %s (you can send a PR to add them to settings/l10n)', basename(__FILE__), $seedLanguage));
    }

    // in any case, enforce the seedLanguage as the default language
    $settingsParams['lcMessages'] = $seedLanguage;

    // apply the config
    civicrm_api3('Setting', 'create', $settingsParams);
  }, \Civi\Setup::PRIORITY_LATE - 60);
