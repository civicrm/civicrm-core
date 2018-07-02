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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
