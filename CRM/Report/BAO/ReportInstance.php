<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Report_BAO_ReportInstance extends CRM_Report_DAO_ReportInstance {

  /**
   * Takes an associative array and creates an instance object.
   *
   * the function extract all the params it needs to initialize the create a
   * instance object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Report_DAO_ReportInstance
   */
  public static function add(&$params) {
    if (empty($params)) {
      return NULL;
    }

    $instanceID = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('instance_id', $params));

    // convert roles array to string
    if (isset($params['grouprole']) && is_array($params['grouprole'])) {
      $grouprole_array = [];
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
      // CRM-17256 set created_id on report creation.
      $params['created_id'] = isset($params['created_id']) ? $params['created_id'] : CRM_Core_Session::getLoggedInContactID();
    }

    if ($instanceID) {
      CRM_Utils_Hook::pre('edit', 'ReportInstance', $instanceID, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ReportInstance', NULL, $params);
    }

    $instance = new CRM_Report_DAO_ReportInstance();
    $instance->copyValues($params, TRUE);

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

    if (!$instanceID) {
      if ($reportID = CRM_Utils_Array::value('report_id', $params)) {
        $instance->report_id = $reportID;
      }
      elseif ($instanceID) {
        $instance->report_id = CRM_Report_Utils_Report::getValueFromUrl($instanceID);
      }
      else {
        // just take it from current url
        $instance->report_id = CRM_Report_Utils_Report::getValueFromUrl();
      }
    }

    $instance->save();

    if ($instanceID) {
      CRM_Utils_Hook::post('edit', 'ReportInstance', $instance->id, $instance);
    }
    else {
      CRM_Utils_Hook::post('create', 'ReportInstance', $instance->id, $instance);
    }
    return $instance;
  }

  /**
   * Create instance.
   *
   * takes an associative array and creates a instance object and does any related work like permissioning, adding to dashboard etc.
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Report_BAO_ReportInstance
   */
  public static function &create(&$params) {
    if (isset($params['report_header'])) {
      $params['header'] = CRM_Utils_Array::value('report_header', $params);
    }
    if (isset($params['report_footer'])) {
      $params['footer'] = CRM_Utils_Array::value('report_footer', $params);
    }

    // build navigation parameters
    if (!empty($params['is_navigation'])) {
      if (!array_key_exists('navigation', $params)) {
        $params['navigation'] = [];
      }
      $navigationParams =& $params['navigation'];

      $navigationParams['permission'] = [];
      $navigationParams['label'] = $params['title'];
      $navigationParams['name'] = $params['title'];

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

    $viewMode = !empty($params['view_mode']) ? $params['view_mode'] : FALSE;
    if ($viewMode) {
      // Do not save to the DB - it's saved in the url.
      unset($params['view_mode']);
    }

    // add to dashboard
    $dashletParams = [];
    if (!empty($params['addToDashboard'])) {
      $dashletParams = [
        'label' => $params['title'],
        'is_active' => 1,
      ];
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
      $navigationParams['url'] = "civicrm/report/instance/{$instance->id}" . ($viewMode == 'view' ? '?reset=1&force=1' : '?reset=1&output=criteria');
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
      $chart = $limitResult = '';
      if (!empty($params['charts'])) {
        $section = 1;
        $chart = "&charts=" . $params['charts'];
      }
      if (!empty($params['row_count']) && CRM_Utils_Rule::positiveInteger($params['row_count'])) {
        $limitResult = '&rowCount=' . $params['row_count'];
      }
      if (!empty($params['cache_minutes']) && CRM_Utils_Rule::positiveInteger($params['cache_minutes'])) {
        $dashletParams['cache_minutes'] = $params['cache_minutes'];
      }
      $dashletParams['name'] = "report/{$instance->id}";
      $dashletParams['url'] = "civicrm/report/instance/{$instance->id}?reset=1&section={$section}{$chart}&context=dashlet" . $limitResult;
      $dashletParams['fullscreen_url'] = "civicrm/report/instance/{$instance->id}?reset=1&section={$section}{$chart}&context=dashletFullscreen" . $limitResult;
      $dashletParams['instanceURL'] = "civicrm/report/instance/{$instance->id}";
      CRM_Core_BAO_Dashboard::addDashlet($dashletParams);
    }
    $transaction->commit();

    return $instance;
  }

  /**
   * Delete the instance of the Report.
   *
   * @param int $id
   *
   * @return mixed
   *   $results no of deleted Instance on success, false otherwise
   */
  public static function del($id = NULL) {
    $navId = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $id, 'navigation_id', 'id');
    $dao = new CRM_Report_DAO_ReportInstance();
    $dao->id = $id;
    $result = $dao->delete();

    // Delete navigation if exists.
    if ($navId) {
      CRM_Core_BAO_Navigation::processDelete($navId);
      CRM_Core_BAO_Navigation::resetNavigation();
    }
    return $result;
  }

  /**
   * Retrieve instance.
   *
   * @param array $params
   * @param array $defaults
   *
   * @return CRM_Report_DAO_ReportInstance|null
   */
  public static function retrieve($params, &$defaults) {
    $instance = new CRM_Report_DAO_ReportInstance();
    $instance->copyValues($params);

    if ($instance->find(TRUE)) {
      CRM_Core_DAO::storeValues($instance, $defaults);
      $instance->free();
      return $instance;
    }
    return NULL;
  }

  /**
   * Check if report is private.
   *
   * @param int $instance_id
   *
   * @return bool
   */
  public static function reportIsPrivate($instance_id) {
    $owner_id = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $instance_id, 'owner_id', 'id');
    if ($owner_id) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the logged in user is the owner.
   *
   * @param int $instance_id
   *
   * @return TRUE if contact owns the report, FALSE if not
   */
  public static function contactIsOwner($instance_id) {
    $session = CRM_Core_Session::singleton();
    $contact_id = $session->get('userID');
    $owner_id = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $instance_id, 'owner_id', 'id');
    if ($contact_id === $owner_id) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the logged in contact can administer the report.
   *
   * @param int $instance_id
   *
   * @return bool
   *   True if contact can edit the private report, FALSE if not.
   */
  public static function contactCanAdministerReport($instance_id) {
    if (self::reportIsPrivate($instance_id)) {
      if (self::contactIsOwner($instance_id) || CRM_Core_Permission::check('access all private reports')) {
        return TRUE;
      }
    }
    elseif (CRM_Core_Permission::check('administer Reports')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete a report instance wrapped in handling for the form layer.
   *
   * @param int $instanceId
   * @param string $bounceTo
   *   Url to redirect the browser to on fail.
   * @param string $successRedirect
   */
  public static function doFormDelete($instanceId, $bounceTo = 'civicrm/report/list?reset=1', $successRedirect = NULL) {
    if (!CRM_Core_Permission::check('administer Reports')) {
      $statusMessage = ts('You do not have permission to Delete Report.');
      CRM_Core_Error::statusBounce($statusMessage, $bounceTo);
    }

    CRM_Report_BAO_ReportInstance::del($instanceId);

    CRM_Core_Session::setStatus(ts('Selected report has been deleted.'), ts('Deleted'), 'success');
    if ($successRedirect) {
      CRM_Utils_System::redirect(CRM_Utils_System::url($successRedirect));
    }
  }

  /**
   * Get the metadata of actions available for this entity.
   *
   * The thinking here is to describe the various actions on the BAO and then functions
   * can add a mix of actions from different BAO as appropriate. The crm.SearchForm.js code
   * transforms the 'confirm_mesage' into a message that needs to be confirmed.
   * confirm_refresh_fields need to be reviewed & potentially updated at the confirm stage.
   *
   * Ideas not yet implemented:
   *  - supports_modal task can be loaded in a popup, theoretically worked, not attempted.
   *  - class and or icon - per option icons or classes (I added these in addTaskMenu::addTaskMenu
   *    but I didn't have the right classes). ie adding  'class' => 'crm-i fa-print' to print / class looked
   *    wrong, but at the php level it worked https://github.com/civicrm/civicrm-core/pull/8529#issuecomment-227639091
   *  - general script-add.
   */
  public static function getActionMetadata() {
    $actions = [];
    if (CRM_Core_Permission::check('save Report Criteria')) {
      $actions['report_instance.save'] = ['title' => ts('Save')];
      $actions['report_instance.copy'] = [
        'title' => ts('Save a Copy'),
        'data' => [
          'is_confirm' => TRUE,
          'confirm_title' => ts('Save a copy...'),
          'confirm_refresh_fields' => json_encode([
            'title' => [
              'selector' => '.crm-report-instanceForm-form-block-title',
              'prepend' => ts('(Copy) '),
            ],
            'description' => [
              'selector' => '.crm-report-instanceForm-form-block-description',
              'prepend' => '',
            ],
            'parent_id' => [
              'selector' => '.crm-report-instanceForm-form-block-parent_id',
              'prepend' => '',
            ],
          ]),
        ],
      ];
    }
    $actions['report_instance.print'] = ['title' => ts('Print Report')];
    $actions['report_instance.pdf'] = ['title' => ts('Print to PDF')];
    $actions['report_instance.csv'] = ['title' => ts('Export as CSV')];

    if (CRM_Core_Permission::check('administer Reports')) {
      $actions['report_instance.delete'] = [
        'title' => ts('Delete report'),
        'data' => [
          'is_confirm' => TRUE,
          'confirm_message' => ts('Are you sure you want delete this report? This action cannot be undone.'),
        ],
      ];
    }
    return $actions;
  }

}
