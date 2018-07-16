<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core;

use Civi;
use Civi\Core\Event\SystemInstallEvent;

/**
 * Class LocalizationInitializer
 * @package Civi\Core
 */
class LocalizationInitializer {

  /**
   * Load the locale settings based on the installation language
   *
   * @param \Civi\Core\Event\SystemInstallEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function initialize(SystemInstallEvent $event) {

    // get the current installation language
    global $tsLocale;
    $seedLanguage = $tsLocale;
    if (!$seedLanguage) {
      return;
    }

    // get the corresponding settings file if any
    $localeDir = \CRM_Core_I18n::getResourceDir();
    $fileName = $localeDir . $seedLanguage . DIRECTORY_SEPARATOR . 'settings.default.json';

    // initalization
    $settingsParams = array();

    if (file_exists($fileName)) {

      // load the file and parse it
      $json = file_get_contents($fileName);
      $settings = json_decode($json, TRUE);

      if (!empty($settings)) {
        // get all valid settings
        $results = civicrm_api3('Setting', 'getfields', array());
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
          \CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies(array($settings['defaultCurrency']), $settings['defaultCurrency']);
        }

      }

    }

    // in any case, enforce the seedLanguage as the default language
    $settingsParams['lcMessages'] = $seedLanguage;

    // apply the config
    civicrm_api3('Setting', 'create', $settingsParams);

  }

}
