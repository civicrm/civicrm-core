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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Case_Info extends CRM_Core_Component_Info {


  // docs inherited from interface
  protected $keyword = 'case';

  // docs inherited from interface
  public function getInfo() {
    return array(
      'name' => 'CiviCase',
      'translatedName' => ts('CiviCase'),
      'title' => ts('CiviCase Engine'),
      'search' => 1,
      'showActivitiesInCore' => 0,
    );
  }

  // docs inherited from interface
  public function getManagedEntities() {
    // Use hook_civicrm_caseTypes to build a list of OptionValues
    // In the long run, we may want more specialized logic for this, but
    // this design is fairly convenient and will allow us to replace it
    // without changing the hook_civicrm_caseTypes interface.
    $entities = array();

    $caseTypes = array();
    CRM_Utils_Hook::caseTypes($caseTypes);

    $proc = new CRM_Case_XMLProcessor();
    $caseTypesGroupId = civicrm_api3('OptionGroup', 'getvalue', array('name' => 'case_type', 'return' => 'id'));
    if (!is_numeric($caseTypesGroupId)) {
      throw new CRM_Core_Exception("Found invalid ID for OptionGroup (case_type)");
    }
    foreach ($caseTypes as $name => $caseType) {
      $xml = $proc->retrieve($name);
      if (!$xml) {
        throw new CRM_Core_Exception("Failed to load XML for case type (" . $name . ")");
      }

      if (isset($caseType['module'], $caseType['name'], $caseType['file'])) {
        $entities[] = array(
          'module' => $caseType['module'],
          'name' => $caseType['name'],
          'entity' => 'OptionValue',
          'params' => array(
            'version' => 3,
            'name' => $caseType['name'],
            'label' => (string) $xml->name,
            'description' => (string) $xml->description, // CRM_Utils_Array::value('description', $caseType, ''),
            'option_group_id' => $caseTypesGroupId,
            'is_reserved' => 1,
          ),
        );
      }
      else {
        throw new CRM_Core_Exception("Invalid case type");
      }
    }

    return $entities;
  }

  // docs inherited from interface
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array(
      'delete in CiviCase',
      'administer CiviCase',
      'access my cases and activities',
      'access all cases and activities',
      'add cases',
    );
  }

  // docs inherited from interface
  public function getUserDashboardElement() {
    return array();
  }

  // docs inherited from interface
  public function registerTab() {
    return array('title' => ts('Cases'),
      'url' => 'case',
      'weight' => 50,
    );
  }

  // docs inherited from interface
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Cases'),
      'weight' => 50,
    );
  }

  // docs inherited from interface
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access all cases and activities') ||
      CRM_Core_Permission::check('add cases')
    ) {
      $atype = CRM_Core_OptionGroup::getValue('activity_type',
        'Open Case',
        'name'
      );
      if ($atype) {
        $shortCuts = array_merge($shortCuts, array(
          array('path' => 'civicrm/case/add',
              'query' => "reset=1&action=add&atype=$atype&context=standalone",
              'ref' => 'new-case',
              'title' => ts('Case'),
            )));
      }
    }
  }

  /**
   * (Setting Callback)
   * Respond to changes in the "enable_components" setting
   *
   * If CiviCase is being enabled, load the case related sample data
   *
   * @param array $oldValue List of component names
   * @param array $newValue List of component names
   * @param array $metadata Specification of the setting (per *.settings.php)
   */
  public static function onToggleComponents($oldValue, $newValue, $metadata) {
    if (
      in_array('CiviCase', $newValue)
      &&
      (!$oldValue || !in_array('CiviCase', $oldValue))
    ) {
      $config = CRM_Core_Config::singleton();
      CRM_Admin_Form_Setting_Component::loadCaseSampleData($config->dsn, $config->sqlDir . 'case_sample.mysql');
      CRM_Admin_Form_Setting_Component::loadCaseSampleData($config->dsn, $config->sqlDir . 'case_sample1.mysql');
      if (!CRM_Case_BAO_Case::createCaseViews()) {
        $msg = ts("Could not create the MySQL views for CiviCase. Your mysql user needs to have the 'CREATE VIEW' permission");
        CRM_Core_Error::fatal($msg);
      }
    }
  }
}

