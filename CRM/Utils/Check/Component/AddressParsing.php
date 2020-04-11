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

  public static function checkLocaleSupportsAddressParsing() {

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options'
    );

    $messages = [];

    if ($addressOptions['street_address_parsing']) {
      if (!CRM_Core_BAO_Address::isSupportedParsingLocale()) {
        $config = CRM_Core_Config::singleton();
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts(
            '<a href=' .
            CRM_Utils_System::url('civicrm/admin/setting/preferences/address', 'reset=1') .
            '">Street address parsing</a> is enabled but not supported by <a href="' .
            CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1') .
            '">your locale</a> (%1).',
            [1 => $config->lcMessages]
          ),
          ts('Street address parsing'),
          \Psr\Log\LogLevel::WARNING,
          'fa-address-card'
        );
      }
    }

    return $messages;
  }

}
