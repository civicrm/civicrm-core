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
 * This class generates form components for multi site preferences
 *
 */
class CRM_Admin_Form_Preferences_Multisite extends CRM_Admin_Form_Preferences {
  public function preProcess() {
    $msDoc = CRM_Utils_System::docURL2('Multi Site Installation', NULL, NULL, NULL, NULL, "wiki");
    CRM_Utils_System::setTitle(ts('Multi Site Settings'));
    $this->_varNames = array(
      CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME => array(
        'is_enabled' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable Multi Site Configuration'),
          'weight' => 1,
          'description' => ts('Make CiviCRM aware of multiple domains. You should configure a domain group if enabled') . ' ' . $msDoc,
        ),
        /** Remove this checkbox until some one knows what this setting does
         * 'uniq_email_per_site' => array(
         * 'html_type' => 'checkbox',
         * 'title' => ts('Ensure multi sites have a unique email per site'),
         * 'weight' => 2,
         * 'description' => NULL,
         * ),
         */
        'domain_group_id' => array(
          'html_type' => 'entity_reference',
          'title' => ts('Domain Group'),
          'weight' => 3,
          'options' => array('entity' => 'group', 'select' => array('minimumInputLength' => 0)),
          'description' => ts('Contacts created on this site are added to this group'),
        ),
        /** Remove this checkbox until some one knows what this setting does
         * 'event_price_set_domain_id' => array(
         * 'html_type' => 'text',
         * 'title' => ts('Domain for event price sets'),
         * 'weight' => 4,
         * 'description' => NULL,
         * ),
         */
      ),
    );

    parent::preProcess();
  }

}
