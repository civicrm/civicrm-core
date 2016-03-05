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
 */

/**
 * Page for invoking report instances.
 */
class CRM_Report_Page_InstanceList extends CRM_Core_Page {

  static $_links = NULL;

  static $_exceptions = array('logging/contact/detail');

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
  protected $_compID = NULL;

  /**
   * ID of grouping if report list is filtered.
   *
   * @var int
   */
  protected $_grouping = NULL;

  /**
   * ID of parent report template if list is filtered by template.
   *
   * @var int
   */
  protected $_ovID = NULL;

  /**
   * Title of parent report template if list is filtered by template.
   *
   * @var string
   */
  protected $_title = NULL;

  /**
   * Retrieves report instances, optionally filtered.
   *
   * Filtering available by parent report template ($ovID) or by component ($compID).
   *
   * @return array
   */
  public function info() {

    $report = '';
    if ($this->ovID) {
      $report .= " AND v.id = {$this->ovID} ";
    }

    if ($this->compID) {
      if ($this->compID == 99) {
        $report .= " AND v.component_id IS NULL ";
        $this->_compName = 'Contact';
      }
      else {
        $report .= " AND v.component_id = {$this->compID} ";
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
      $report .= " AND v.grouping = '{$this->grouping}' ";
    }

    $sql = "
        SELECT inst.id, inst.title, inst.report_id, inst.description, v.label, v.grouping, v.name as class_name,
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
                AND inst.domain_id = %1
          ORDER BY  v.weight";

    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array(CRM_Core_Config::domainID(), 'Integer'),
    ));

    $config = CRM_Core_Config::singleton();
    $rows = array();
    $url = 'civicrm/report/instance';
    while ($dao->fetch()) {
      if (in_array($dao->report_id, self::$_exceptions)) {
        continue;
      }

      $enabled = in_array("Civi{$dao->compName}", $config->enableComponents);
      if ($dao->compName == 'Contact' || $dao->compName == $dao->grouping) {
        $enabled = TRUE;
      }
      //filter report listings by permissions
      if (!($enabled && CRM_Report_Utils_Report::isInstancePermissioned($dao->id))) {
        continue;
      }
      //filter report listing by group/role
      if (!($enabled && CRM_Report_Utils_Report::isInstanceGroupRoleAllowed($dao->id))) {
        continue;
      }

      if (trim($dao->title)) {
        if ($this->ovID) {
          $this->title = ts("Report(s) created from the template: %1", array(1 => $dao->label));
        }
        $rows[$dao->compName][$dao->id]['title'] = $dao->title;
        $rows[$dao->compName][$dao->id]['label'] = $dao->label;
        $rows[$dao->compName][$dao->id]['description'] = $dao->description;
        $rows[$dao->compName][$dao->id]['url'] = CRM_Utils_System::url("{$url}/{$dao->id}", "reset=1&output=criteria");
        $rows[$dao->compName][$dao->id]['viewUrl'] = CRM_Utils_System::url("{$url}/{$dao->id}", 'force=1&reset=1');
        $rows[$dao->compName][$dao->id]['actions'] = $this->getActionLinks($dao->id, $dao->class_name);
      }
    }
    return $rows;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    //Filters by source report template or by component
    $this->ovID = CRM_Utils_Request::retrieve('ovid', 'Positive', $this);
    $this->compID = CRM_Utils_Request::retrieve('compid', 'Positive', $this);
    $this->grouping = CRM_Utils_Request::retrieve('grp', 'String', $this);

    $rows = $this->info();

    $this->assign('list', $rows);
    if ($this->ovID OR $this->compID) {
      // link to view all reports
      $reportUrl = CRM_Utils_System::url('civicrm/report/list', "reset=1");
      $this->assign('reportUrl', $reportUrl);
      if ($this->ovID) {
        $this->assign('title', $this->title);
      }
      else {
        CRM_Utils_System::setTitle(ts('%1 Reports', array(1 => $this->_compName)));
      }
    }
    // assign link to template list for users with appropriate permissions
    if (CRM_Core_Permission::check('administer Reports')) {
      if ($this->compID) {
        $newButton = ts('New %1 Report', array(1 => $this->_compName));
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1&compid={$this->compID}");
      }
      else {
        $newButton = ts('New Report');
        $templateUrl = CRM_Utils_System::url('civicrm/report/template/list', "reset=1");
      }
      $this->assign('newButton', $newButton);
      $this->assign('templateUrl', $templateUrl);
      $this->assign('compName', $this->_compName);
    }
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
    $actions = array(
      'copy' => array(
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&output=copy'),
        'label' => ts('Save a Copy'),
      ),
      'pdf' => array(
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=pdf'),
        'label' => ts('View as pdf'),
      ),
      'print' => array(
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=print'),
        'label' => ts('Print report'),
      ),
    );
    // Hackery, Hackera, Hacker ahahahahahaha a super nasty hack.
    // Almost all report classes support csv & loading each class to call the method seems too
    // expensive. We also have on our later list 'do they support charts' which is instance specific
    // e.g use of group by might affect it. So, lets just skip for the few that don't for now.
    $csvBlackList = array(
      'CRM_Report_Form_Contact_Detail',
      'CRM_Report_Form_Event_Income',
    );
    if (!in_array($className, $csvBlackList)) {
      $actions['csv'] = array(
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&force=1&output=csv'),
        'label' => ts('Export to csv'),
      );
    }
    if (CRM_Core_Permission::check('administer Reports')) {
      $actions['delete'] = array(
        'url' => CRM_Utils_System::url($urlCommon, 'reset=1&action=delete'),
        'label' => ts('Delete report'),
        'confirm_message' => ts('Are you sure you want delete this report? This action cannot be undone.'),
      );
    }

    return $actions;
  }

}
