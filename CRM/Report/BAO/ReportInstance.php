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
 * $Id$
 *
 */
class CRM_Report_BAO_ReportInstance extends CRM_Report_DAO_ReportInstance {

  /**
   * takes an associative array and creates an instance object
   *
   * the function extract all the params it needs to initialize the create a
   * instance object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Report_DAO_ReportInstance object
   * @access public
   * @static
   */
  static function add(&$params) {
    $instance = new CRM_Report_DAO_ReportInstance();
    if (empty($params)) {
      return NULL;
    }

    $instanceID = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('instance_id', $params));

    // convert roles array to string
    if (isset($params['grouprole']) && is_array($params['grouprole'])) {
      $grouprole_array = array();
      foreach ($params['grouprole'] as $key => $value) {
        $grouprole_array[$value] = $value;
      }
      $params['grouprole'] = implode(CRM_Core_DAO::VALUE_SEPARATOR,
        array_keys($grouprole_array)
      );
    }

    if (!$instanceID || !isset($params['id'])) {
      $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);
      $params['domain_id'] = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());
    }

    if ($instanceID) {
      CRM_Utils_Hook::pre('edit', 'ReportInstance', $instanceID, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ReportInstance', NULL, $params);
    }

    $instance = new CRM_Report_DAO_ReportInstance();
    $instance->copyValues($params);

    if (CRM_Core_Config::singleton()->userFramework == 'Joomla') {
      $instance->permission = 'null';
    }

    // explicitly set to null if params value is empty
    if (!$instanceID && empty($params['grouprole'])) {
      $instance->grouprole = 'null';
    }

    if ($instanceID) {
      $instance->id = $instanceID;
    }

    if (! $instanceID) {
      if ($reportID = CRM_Utils_Array::value('report_id', $params)) {
        $instance->report_id = $reportID;
      } else if ($instanceID) {
        $instance->report_id = CRM_Report_Utils_Report::getValueFromUrl($instanceID);
      } else {
        // just take it from current url
        $instance->report_id = CRM_Report_Utils_Report::getValueFromUrl();
      }
    }

    $instance->save();

    if ($instanceID) {
      CRM_Utils_Hook::pre('edit', 'ReportInstance', $instance->id, $instance);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ReportInstance', $instance->id, $instance);
    }
    return $instance;
  }

  /**
   * Function to create instance
   * takes an associative array and creates a instance object and does any related work like permissioning, adding to dashboard etc.
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array   $params      (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Report_BAO_ReportInstance object
   * @access public
   * @static
   */
  static function &create(&$params) {
    if (isset($params['report_header'])) {
      $params['header']    = CRM_Utils_Array::value('report_header',$params);
    }
    if (isset($params['report_footer'])) {
      $params['footer']    = CRM_Utils_Array::value('report_footer',$params);
    }

    // build navigation parameters
    if (!empty($params['is_navigation'])) {
      if (!array_key_exists('navigation', $params)) {
        $params['navigation'] = array();
      }
      $navigationParams =& $params['navigation'];

      $navigationParams['permission'] = array();
      $navigationParams['label'] = $params['title'];
      $navigationParams['name']  = $params['title'];

      $navigationParams['current_parent_id'] = CRM_Utils_Array::value('parent_id', $navigationParams);
      $navigationParams['parent_id'] = CRM_Utils_Array::value('parent_id', $params);
      $navigationParams['is_active'] = 1;

      if ($permission = CRM_Utils_Array::value('permission', $params)) {
        $navigationParams['permission'][] = $permission;
      }

      // unset the navigation related elements, not used in report form values
      unset($params['parent_id']);
      unset($params['is_navigation']);
    }

    // add to dashboard
    $dashletParams = array();
    if (!empty($params['addToDashboard'])) {
      $dashletParams = array(
        'label' => $params['title'],
        'is_active' => 1,
      );
      if ($permission = CRM_Utils_Array::value('permission', $params)) {
        $dashletParams['permission'][] = $permission;
      }
    }

    $transaction = new CRM_Core_Transaction();

    $instance = self::add($params);
    if (is_a($instance, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $instance;
    }

    // add / update navigation as required
    if (!empty($navigationParams)) {
      if (empty($params['id']) && empty($params['instance_id']) && !empty($navigationParams['id'])) {
        unset($navigationParams['id']);
      }
      $navigationParams['url'] = "civicrm/report/instance/{$instance->id}?reset=1";
      $navigation = CRM_Core_BAO_Navigation::add($navigationParams);

      if (!empty($navigationParams['is_active'])) {
        //set the navigation id in report instance table
        CRM_Core_DAO::setFieldValue('CRM_Report_DAO_ReportInstance', $instance->id, 'navigation_id', $navigation->id);
      }
      else {
        // has been removed from the navigation bar
        CRM_Core_DAO::setFieldValue('CRM_Report_DAO_ReportInstance', $instance->id, 'navigation_id', 'NULL');
      }
      //reset navigation
      CRM_Core_BAO_Navigation::resetNavigation();
    }

    // add to dashlet
    if (!empty($dashletParams)) {
      $section = 2;
      $chart  = '';
      if (!empty($params['charts'])) {
        $section = 1;
        $chart   = "&charts=" . $params['charts'];
      }
      $limitResult = NULL;
      if (CRM_Utils_Array::value('row_count', $params)) {
        $limitResult = '&rowCount=' . $params['row_count'];
      }
      $dashletParams['name'] = "report/{$instance->id}";
      $dashletParams['url'] = "civicrm/report/instance/{$instance->id}?reset=1&section={$section}&snippet=5{$chart}&context=dashlet" . $limitResult;
      $dashletParams['fullscreen_url'] = "civicrm/report/instance/{$instance->id}?reset=1&section={$section}&snippet=5{$chart}&context=dashletFullscreen" . $limitResult;
      $dashletParams['instanceURL'] = "civicrm/report/instance/{$instance->id}";
      CRM_Core_BAO_Dashboard::addDashlet($dashletParams);
    }
    $transaction->commit();

    return $instance;
  }

  /**
   * Delete the instance of the Report
   *
   * @param null $id
   *
   * @return mixed $results no of deleted Instance on success, false otherwise@access public
   */
  static function del($id = NULL) {
    $dao = new CRM_Report_DAO_ReportInstance();
    $dao->id = $id;
    return $dao->delete();
  }

  /**
   * @param $params
   * @param $defaults
   *
   * @return CRM_Report_DAO_ReportInstance|null
   */
  static function retrieve($params, &$defaults) {
    $instance = new CRM_Report_DAO_ReportInstance();
    $instance->copyValues($params);

    if ($instance->find(TRUE)) {
      CRM_Core_DAO::storeValues($instance, $defaults);
      $instance->free();
      return $instance;
    }
    return NULL;
  }
}
