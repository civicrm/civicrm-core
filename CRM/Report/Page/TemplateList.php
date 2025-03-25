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
 * Page for displaying list of Report templates available.
 */
class CRM_Report_Page_TemplateList extends CRM_Core_Page {

  /**
   * @param int $compID
   * @param string|null $grouping
   *
   * @return array
   */
  public static function &info($compID = NULL, $grouping = NULL) {
    $all = CRM_Utils_Request::retrieveValue('all', 'Boolean', NULL, FALSE, 'GET');

    // Needed later for translating component names
    $components = CRM_Core_Component::getComponents();

    $compClause = '';
    if ($compID) {
      if ($compID == 99) {
        $compClause = " AND v.component_id IS NULL ";
      }
      else {
        $compClause = " AND v.component_id = {$compID} ";
      }
    }
    elseif ($grouping) {
      $compClause = " AND v.grouping = '{$grouping}' ";
    }
    $sql = "
SELECT  v.id, v.value, v.label, v.description, v.component_id,
  CASE
    WHEN comp.name IS NOT NULL THEN SUBSTRING(comp.name, 5)
    WHEN v.grouping IS NOT NULL THEN v.grouping
    ELSE 'Contact'
    END as component_name,
        v.grouping,
        inst.id as instance_id
FROM    civicrm_option_value v
INNER JOIN civicrm_option_group g
        ON (v.option_group_id = g.id AND g.name = 'report_template')
LEFT  JOIN civicrm_report_instance inst
        ON v.value = inst.report_id
LEFT  JOIN civicrm_component comp
        ON v.component_id = comp.id
";

    if (!$all) {
      $sql .= " WHERE v.is_active = 1 {$compClause}";
    }
    $sql .= " ORDER BY  v.weight ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = [];
    while ($dao->fetch()) {
      $enabled = CRM_Core_Component::isEnabled("Civi{$dao->component_name}");
      $component_name = $dao->component_name;
      if ($component_name != 'Contact' && $component_name != $dao->grouping && !$enabled) {
        continue;
      }
      // Display a translated label, if possible
      if (empty($rows[$component_name]['label'])) {
        $label = $component_name;
        if (!empty($components['Civi' . $component_name])) {
          $label = $components['Civi' . $component_name]->info['translatedName'] ?? $component_name;
        }
        if ($component_name == 'Contact') {
          $label = ts('Contacts');
        }
        $rows[$component_name]['label'] = $label;
      }
      $rows[$component_name]['list'][$dao->value]['title'] = _ts($dao->label);
      $rows[$component_name]['list'][$dao->value]['description'] = _ts($dao->description);
      $rows[$component_name]['list'][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
      $rows[$component_name]['list'][$dao->value]['instanceUrl'] = $dao->instance_id ? CRM_Utils_System::url(
        'civicrm/report/list',
        "reset=1&ovid=$dao->id"
      ) : '';
    }

    return $rows;
  }

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $compID = CRM_Utils_Request::retrieve('compid', 'Positive', $this);
    $grouping = CRM_Utils_Request::retrieve('grp', 'String', $this);
    $rows = self::info($compID, $grouping);
    $this->assign('list', $rows);

    return parent::run();
  }

}
