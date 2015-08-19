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
 * $Id$
 *
 */

/**
 * This class generates form components for Date Formatting
 *
 */
class CRM_Admin_Form_Setting_Date extends CRM_Admin_Form_Setting {

  public $_settings = array(
    'weekBegins' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'dateInputFormat' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'timeInputFormat' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'fiscalYearStart' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
  );

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Date'));

    $this->addElement('text', 'dateformatDatetime', ts('Complete Date and Time'));
    $this->addElement('text', 'dateformatFull', ts('Complete Date'));
    $this->addElement('text', 'dateformatPartial', ts('Month and Year'));
    $this->addElement('text', 'dateformatYear', ts('Year Only'));
    $this->addElement('text', 'dateformatTime', ts('Time Only'));

    parent::buildQuickForm();
  }

}
