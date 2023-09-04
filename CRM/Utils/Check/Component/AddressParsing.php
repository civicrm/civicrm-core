<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_AddressParsing extends CRM_Utils_Check_Component {

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkLocaleSupportsAddressParsing() {

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    );

    $messages = [];

    if ($addressOptions['street_address_parsing']) {
      if (!CRM_Core_BAO_Address::isSupportedParsingLocale()) {
        $config = CRM_Core_Config::singleton();
        $url_address = CRM_Utils_System::url('civicrm/admin/setting/preferences/address', 'reset=1');
        $url_localization = CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1');
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('<a %1>Street address parsing</a> is enabled but not supported by <a %2>your language settings</a> (%3).', [1 => "href='$url_address'", 2 => "href='$url_localization'", 3 => $config->lcMessages]),
          ts('Street address parsing'),
          \Psr\Log\LogLevel::WARNING,
          'fa-address-card'
        );
      }
    }

    return $messages;
  }

}
