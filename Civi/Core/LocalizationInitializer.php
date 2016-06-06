<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

/**
 * Class LocalizationInitializer
 * @package Civi\Core
 */
class LocalizationInitializer {

  /*
   * Load the locale settings based on the installation language
   *
   * @param \Civi\Core\Event\SystemInstallEvent $event
   * @throws \CRM_Core_Exception
   */
  public static function initialize(SystemInstallEvent $event) {
    // get the current installation language
    $seedLanguage = 'fr_CA';
    $resourceDir = \CRM_Core_I18n::getResourceDir();
    //\CRM_Core_Config::singleton()->userSystem->getCiviSourceStorage();

    // get the corresponding settings file if any
    $settingsParams = array();
    $fileName = $resourceDir . $seedLanguage . DIRECTORY_SEPARATOR . 'settings.json';
    if (file_exists($fileName)) {
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
          // TODO: add support for currencies_enabled which is an OptionGroup
        }
      }
    }
    $settingsParams['domain_id'] = 'current_domain';
    //$settingsParams['lcMessages'] = $seedLanguage;
watchdog('debug', 'params -- ' . print_r($settingsParams,1));

    // apply the config
    civicrm_api3('Setting', 'create', $settingsParams);
  }

}
