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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Page for invoking report instances.
 */
class CRM_Report_Page_InstanceList extends CRM_Core_Page {

  public static $_links = NULL;

  public static $_exceptions = ['logging/contact/detail'];

  /**
   * Name of component if report list is filtered.
   *
   * @var string
   */
  protected $_compName = NULL;

  /**
   * ID of component if report list is filtered.
   *
   * @var int
   */
  protected $compID = NULL;

  /**
   * ID of grouping if report list is filtered.
   *
   * @var int
   */
  protected $grouping = NULL;

  /**
   * ID of parent report template if list is filtered by template.
   *
   * @var int
   */
  protected $ovID = NULL;

  /**
   * Title of parent report template if list is filtered by template.
   *
   * @var string
   */
  protected $_title = NULL;

  /**
   * @var string
   */
  protected $myReports;

  /**
   * Retrieves report instances, optionally filtered.
   *
   * Filtering available by parent report template ($ovID) or by component ($compID).
   *
   * @return array
   */
  public function info() {
    $report = '';
    $queryParams = [];

    // Needed later for translating component names
    $components = CRM_Core_Component::getComponents();

    if ($this->ovID) {
      $report .= " AND v.id = %1 ";
      $queryParams[1] = [$this->ovID, 'Integer'];
    }

    if ($this->compID) {
      if ($this->compID == 99) {
        $report .= " AND v.component_id IS NULL ";
        $this->_compName = 'Contact';
      }
      else {
        $report .= " AND v.component_id = %2 ";
        $queryParams[2] = [$this->compID, 'Integer'];
        $cmpName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component', $this->compID,
          'name', 'id'
        );
        $this->_compName = substr($cmpName, 4);
        if ($this->_compName == 'Contribute') {
          $this->_compName = 'Contribution';
        }
      }
    }
    elseif ($this->grouping) {
      $report .= " AND v.grouping = %3 ";
      $queryParams[3] = [$this->grouping, 'String'];
    }
    elseif ($this->myReports) {
      $report .= " AND inst.owner_id = %4 ";
      $queryParams[4] = [CRM_Core_Session::getLoggedInContactID(), 'Integer'];
    }

    $sql = "
        SELECT inst.id, inst.title, inst.report_id, inst.description,  inst.owner_id, v.label, v.grouping, v.name as class_name,
        CASE
          WHEN comp.name IS NOT NULL THEN SUBSTRING(comp.name, 5)
          WHEN v.grouping IS NOT NULL THEN v.grouping
          ELSE 'Contact'
          END as compName
          FROM civicrm_option_group g
          LEFT JOIN civicrm_option_value v
                 ON v.option_group_id = g.id AND
                    g.name  = 'report_template'
          LEFT JOIN civicrm_report_instance inst
                 ON v.value = inst.report_id
          LEFT JOIN civicrm_component comp
                 ON v.component_id = comp.id

          WHERE v.is_active = 1 {$report}
                AND inst.domain_id = %9
          ORDER BY  v.weight ASC, inst.title ASC";
    $queryParams[9] = [CRM_Core_Config::domainID(), 'Integer'];

    $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);

    $rows = [];
    $url = 'civicrm/report/instance';
    $my_reports_grouping = 'My';
    while ($dao->fetch()) {
      if (in_array($dao->report_id, self::$_exceptions)) {
        continue;
      }

      $enabled = CRM_Core_Component::isEnabled("Civi{$dao->compName}");
      if ($dao->compName == 'Contact' || $dao->compName == $dao->grouping) {
        $enabled = TRUE;
      }

      // filter report listings for private reports
      if (!empty($dao->owner_id) && CRM_Core_Session::getLoggedInContactID() != $dao->owner_id) {
        continue;
      }

      //filter report listings by permissions
      if (!($enabled && CRM_Report_Utils_Report::isInstancePermissioned($dao->id))) {
        continue;
      }
      //filter report listing by group/role
      if (!($enabled && CRM_Report_Utils_Report::isInstanceGroupRoleAllowed($dao->id))) {
        continue;
      }
      if (trim($dao->title ?? '')) {
        if ($this->ovID) {
          $this->assign('pageTitle', ts("Report(s) created from the template: %1", [1 => $dao->label]));
        }

        $report_grouping = $dao->compName;
        if ($dao->owner_id != NULL) {
          $report_grouping = $my_reports_grouping;
        }

        // Display a translated label, if possible
        if (empty($rows[$report_grouping]['label'])) {
          $label = $dao->compName;
          if (!empty($components['Civi' . $report_grouping])) {
            $label = $components['Civi' . $dao->compName]->info['translatedName'] ?? $dao->compName;
          }
          if ($report_grouping == 'Contact') {
            $label = ts('Contacts');
          }
          $rows[$report_grouping]['label'] = $label;
        }

        $rows[$report_grouping]['list'][$dao->id]['title'] = $dao->title;
        $rows[$report_grouping]['list'][$dao->id]['label'] = $dao->label;
        $rows[$report_grouping]['list'][$dao->id]['description'] = $dao->description;
        $rows[$report_grouping]['list'][$dao->id]['url'] = CRM_Utils_System::url("{$url}/{$dao->id}", "reset=1&output=criteria");
        $rows[$report_grouping]['list'][$dao->id]['viewUrl'] = CRM_Utils_System::url("{$url}/{$dao->id}", 'force=1&reset=1');
        $rows[$report_grouping]['list'][$dao->id]['actions'] = $this->getActionLinks($dao->id, $dao->class_name);
      }
    }
    // Move My Reports to the beginning of the reports list
    if (isset($rows[$my_reports_grouping])) {
      $my_reports = $rows[$my_reports_grouping];
      unset($rows[$my_reports_grouping]);
      $rows = [$my_reports_grouping => $my_reports] + $rows;
    }
    return $rows;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    //Filters by source report template or by component
    $this->ovID = CRM_Utils_Request::retrieve('ovid', 'Positive', $this);
    $this->myReports = CRM_Utils_Request::retrieve('myreports', 'String', $this);
    $this->compID = CRM_Utils_Request::retrieve('compid', 'Positive', $this);
    $this->grouping = CRM_Utils_Request::retrieve('grp', 'String', $this);

    $rows = $this->info();
    $this->assign('list', $rows);
    if ($this->ovID or $this->compID) {
      // link to view all reports
      $reportUrl = CRM_Utils_System::url('civicrm/report/list', "reset=1");
      if (!$this->ovID) {
        CRM_Utils_System::setTitle(ts('%1 Reports', [1 => $this->_compName]));
      }
    }
    $this->assign('reportUrl', $reportUrl ?? FALSE);
    // assign link to template list for users with appropriate permissions
    if (CRM_Core_Permission::check('administer Reports')) {
      if ($this->compID) {
        $newButton = ts('New %1 Report', [1 => $this->_compName]);
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1&compid={$this->compID}");
      }
      else {
        $newButton = ts('New Report');
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1");
      }
      $this->assign('newButton', $newButton);
      $this->assign('compName', $this->_compName);
    }
    $this->assign('myReports', $this->myReports);
    $this->assign('templateUrl', $templateUrl ?? NULL);
    return parent::run();
  }

  /**
   * Get action links.
   *
   * @param int $instanceID
   * @param string $className
   *
   * @return array
   */
  protected function getActionLinks($instanceID, $className) {
    $urlCommon = 'civicrm/report/instance/' . $instanceID;
    $actions = [
      'copy' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&output=copy'),
        'label' => ts('Save a Copy'),
        'confirm_message' => NULL,
        'weight' => CRM_Core_Action::getWeight(\CRM_Core_Action::COPY),
      ],
      'pdf' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=pdf'),
        'label' => ts('View as pdf'),
        'confirm_message' => NULL,
        'weight' => CRM_Core_Action::getWeight(\CRM_Core_Action::EXPORT),
      ],
      'print' => [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=print'),
        'label' => ts('Print report'),
        'confirm_message' => NULL,
        'weight' => CRM_Core_Action::getWeight(\CRM_Core_Action::EXPORT),
      ],
    ];
    // Hackery, Hackera, Hacker ahahahahahaha a super nasty hack.
    // Almost all report classes support csv & loading each class to call the method seems too
    // expensive. We also have on our later list 'do they support charts' which is instance specific
    // e.g use of group by might affect it. So, lets just skip for the few that don't for now.
    $csvBlackList = [
      'CRM_Report_Form_Contact_Detail',
      'CRM_Report_Form_Event_Income',
    ];
    if (!in_array($className, $csvBlackList)) {
      $actions['csv'] = [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=csv'),
        'label' => ts('Export to csv'),
        'confirm_message' => NULL,
        'weight' => CRM_Core_Action::getWeight(\CRM_Core_Action::EXPORT),
      ];
    }
    if (CRM_Core_Permission::check('administer Reports')) {
      $actions['delete'] = [
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&action=delete'),
        'label' => ts('Delete report'),
        'confirm_message' => ts('Are you sure you want delete this report? This action cannot be undone.'),
        'weight' => CRM_Core_Action::getWeight(\CRM_Core_Action::DELETE),
      ];
    }
    CRM_Utils_Hook::links('view.report.links',
      $className,
      $instanceID,
      $actions
    );

    return $actions;
  }

}
