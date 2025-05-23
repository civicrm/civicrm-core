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

use Civi\Core\Event\PreEvent;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Report_BAO_ReportInstance extends CRM_Report_DAO_ReportInstance implements Civi\Core\HookInterface {

  /**
   * Function ought to be deprecated in favor of self::writeRecord() once the fixmes are addressed.
   *
   * @param array $params
   * @return CRM_Report_DAO_ReportInstance
   */
  public static function add($params) {
    if (empty($params)) {
      return NULL;
    }

    if (empty($params['grouprole'])) {
      // an empty array is getting stored as '' but it needs to be null
      $params['grouprole'] = NULL;
    }

    if (!isset($params['id'])) {
      $params['domain_id'] ??= CRM_Core_Config::domainID();
      // CRM-17256 set created_id on report creation.
      $params['created_id'] ??= CRM_Core_Session::getLoggedInContactID();
      $params['grouprole'] ??= '';
      // FIXME: This probably belongs in the form layer
      $params['report_id'] ??= CRM_Report_Utils_Report::getValueFromUrl();
    }
    // Fixme: Why is this even necessary?
    if (CRM_Core_Config::singleton()->userFramework == 'Joomla') {
      $params['permission'] = '';
    }

    return self::writeRecord($params);
  }

  /**
   * Create report instance.
   *
   * Does any related work like creating navigation, adding to dashboard etc.
   *
   * @param array $params
   *
   * @return CRM_Report_DAO_ReportInstance
   */
  public static function create(array $params) {
    // Transform nonstandard field names used by quickform
    $params['id'] ??= ($params['instance_id'] ?? NULL);
    if (isset($params['report_header'])) {
      $params['header'] = $params['report_header'];
    }
    if (isset($params['report_footer'])) {
      $params['footer'] = $params['report_footer'];
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

      $navigationParams['current_parent_id'] = $navigationParams['parent_id'] ?? NULL;
      $navigationParams['parent_id'] = $params['parent_id'] ?? NULL;
      $navigationParams['is_active'] = 1;

      $permission = $params['permission'] ?? NULL;
      if ($permission) {
        $navigationParams['permission'][] = $permission;
      }

      // unset the navigation related elements, not used in report form values
      unset($params['parent_id']);
      unset($params['is_navigation']);
    }

    $viewMode = !empty($params['view_mode']);
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
      $permission = $params['permission'] ?? NULL;
      if ($permission) {
        $dashletParams['permission'][] = $permission;
      }
    }

    $transaction = new CRM_Core_Transaction();

    $instance = self::add($params);

    // add / update navigation as required
    if (!empty($navigationParams)) {
      if (empty($params['id']) && !empty($navigationParams['id'])) {
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
   * @deprecated
   * @return mixed
   */
  public static function del($id = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    self::deleteRecord(['id' => $id]);
    return 1;
  }

  /**
   * Event fired prior to modifying a ReportInstance.
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(PreEvent $event): void {
    if ($event->action === 'delete' && $event->id) {
      // When deleting a report, also delete from navigation menu
      $navId = CRM_Core_DAO::getFieldValue('CRM_Report_DAO_ReportInstance', $event->id, 'navigation_id');
      if ($navId) {
        CRM_Core_BAO_Navigation::deleteRecord(['id' => $navId]);
        CRM_Core_BAO_Navigation::resetNavigation();
      }
    }
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
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
  public static function doFormDelete($instanceId, $bounceTo = 'civicrm/report/list?reset=1', $successRedirect = NULL): void {
    if (!CRM_Core_Permission::check('administer Reports')) {
      $statusMessage = ts('You do not have permission to Delete Report.');
      CRM_Core_Error::statusBounce($statusMessage, $bounceTo);
    }

    CRM_Report_BAO_ReportInstance::deleteRecord(['id' => $instanceId]);

    CRM_Core_Session::setStatus(ts('Selected report has been deleted.'), ts('Deleted'), 'success');
    if ($successRedirect) {
      CRM_Utils_System::redirect(CRM_Utils_System::url($successRedirect));
    }
  }

  /**
   * Apply permission field check to ReportInstance.
   *
   * Note that we just check all the individual found permissions & then use the
   * 'OK' ones as a filter. The volume should be low enough for this to be OK
   * and the table holds exactly one permission for each instance.
   *
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   *
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $permissions = CRM_Core_DAO::executeQuery('SELECT DISTINCT permission FROM civicrm_report_instance');
    $validPermissions = [];
    while ($permissions->fetch()) {
      $permission = $permissions->permission;
      if ($permission && CRM_Core_Permission::check($permission)) {
        $validPermissions[] = $permission;
      }
    }
    if (!$validPermissions) {
      return ['permission' => ['IS NULL']];
    }
    return ['permission' => ['IN ("' . implode('", "', $validPermissions) . '")']];
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
  public static function getActionMetadata(): array {
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

  /**
   * Pseudoconstant callback for the `grouprole` field
   * @return array
   */
  public static function getGrouproleOptions(): array {
    return (array) CRM_Core_Config::singleton()->userSystem->getRoleNames();
  }

}
