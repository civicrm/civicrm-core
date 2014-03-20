<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
/**
 * This class generates actitvity form elements
 *
 */
class CRM_Case_Form_ActivityForm {

  function activityform () {
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($this->_contactID, $this->_caseID);   
    //build reporter select
    $reporters = array("" => ts(' - any reporter - '));
    foreach ($caseRelationships as $key => & $value) {
      $reporters[$value['cid']] = $value['name'] . " ( {$value['relation']} )";
    }
    $this->add('select', 'reporter_id', ts('Reporter/Role'), $reporters);

    // take all case activity types for search filter, CRM-7187
    $aTypesFilter = array();
    $allCaseActTypes = CRM_Case_PseudoConstant::caseActivityType();
    foreach ($allCaseActTypes as $typeDetails) {
      if (!in_array($typeDetails['name'], array(
                                                'Open Case'))) {
        $aTypesFilter[$typeDetails['id']] = CRM_Utils_Array::value('label', $typeDetails);
      }
    }
    asort($aTypesFilter);
    $this->add('select', 'activity_type_filter_id', ts('Activity Type'), array('' => ts('- select activity type -')) + $aTypesFilter);

    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $this->add('select', 'status_id', ts('Status'), array("" => ts(' - any status - ')) + $activityStatus);

    // activity dates
    $this->addDate('activity_date_low', ts('Activity Dates - From'), FALSE, array('formatType' => 'searchDate'));
    $this->addDate('activity_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->add('checkbox', 'activity_deleted', ts('Deleted Activities'));
    }
  }
}