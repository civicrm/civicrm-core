<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: Display.php 36505 2011-10-03 14:19:56Z lobo $
 *
 */

/**
 * This class generates form components for the display preferences
 *
 */
class CRM_Admin_Form_Preferences_Campaign extends CRM_Admin_Form_Preferences {
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviCampaign Component Settings'));
    $this->_varNames = array(
      CRM_Core_BAO_Setting::CAMPAIGN_PREFERENCES_NAME =>
      array(
        'tag_unconfirmed' => array(
          'html_type' => 'text',
          'title' => ts('Tag for Unconfirmed Petition Signers'),
          'weight' => 1,
          'description' => ts('If set, new contacts that are created when signing a petition are assigned a tag of this name.'),
        ),
        'petition_contacts' => array(
          'html_type' => 'text',
          'title' => ts('Petition Signers Group'),
          'weight' => 2,
          'description' => ts('All contacts that have signed a CiviCampaign petition will be added to this group. The group will be created if it does not exist (it is required for email verification).'),
        ),
      ),
    );

    parent::preProcess();
  }
}

