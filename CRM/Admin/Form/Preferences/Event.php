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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id: Display.php 36505 2011-10-03 14:19:56Z lobo $
 *
 */

/**
 * This class generates form components for the display preferences
 *
 */
class CRM_Admin_Form_Preferences_Event extends CRM_Admin_Form_Preferences {
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviEvent Component Settings'));
    // pass "wiki" as 6th param to docURL2 if you are linking to a page in wiki.civicrm.org
    $docLink = CRM_Utils_System::docURL2("CiviEvent Cart Checkout", NULL, NULL, NULL, NULL, "wiki");
    // build an array containing all selectable option values for show_events
    $optionValues = array();
    for ($i = 10; $i <= 100; $i += 10) {
      $optionValues[$i] = $i;
    }
    $this->_varNames = array(
      CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME => array(
        'enable_cart' => array(
          'html_type' => 'checkbox',
          'title' => ts('Use Shopping Cart Style Event Registration'),
          'weight' => 1,
          'description' => ts('This feature allows users to register for more than one event at a time. When enabled, users will add event(s) to a "cart" and then pay for them all at once. Enabling this setting will affect online registration for all active events. The code is an alpha state, and you will potentially need to have developer resources to debug and fix sections of the codebase while testing and deploying it. %1',
            array(1 => $docLink)),
        ),
        'show_events' => array(
          'html_type' => 'select',
          'title' => ts('Dashboard entries'),
          'weight' => 2,
          'description' => ts('Configure how many events should be shown on the dashboard. This overrides the default value of 10 entries.'),
          'option_values' => array('' => ts('- select -')) + $optionValues + array(-1 => ts('show all')),
        ),
      ),
    );

    parent::preProcess();
  }

}
