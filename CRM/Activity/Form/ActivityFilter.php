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

/**
 * This class generates form components for Activity Filter.
 */
class CRM_Activity_Form_ActivityFilter extends CRM_Core_Form {
  public function buildQuickForm() {
    // add activity search filter
    $activityOptions = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    asort($activityOptions);

    $this->add('select', 'activity_type_filter_id', ts('Include'), array('' => ts('- all activity type(s) -')) + $activityOptions);
    $this->add('select', 'activity_type_exclude_filter_id', ts('Exclude'), array('' => ts('- select activity type -')) + $activityOptions);
    CRM_Core_Form_Date::buildDateRange(
      $this, 'activity_date', 1,
      '_low', '_high', ts('From:'),
      FALSE, array(), 'searchDate',
      FALSE, array('class' => 'crm-select2 medium')
    );
    $this->addSelect('status_id',
      array('entity' => 'activity', 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );

    $this->assign('suppressForm', TRUE);
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    // CRM-11761 retrieve user's activity filter preferences
    $defaults = array();
    if (Civi::settings()->get('preserve_activity_tab_filter') && ($userID = CRM_Core_Session::getLoggedInContactID())) {
      $defaults = Civi::service('settings_manager')
        ->getBagByContact(NULL, $userID)
        ->get('activity_tab_filter');
    }
    // set Activity status 'Scheduled' by default only for dashlet
    elseif (strstr(CRM_Utils_Array::value('q', $_GET), 'dashlet')) {
      $defaults['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled');
    }
    return $defaults;
  }

}
