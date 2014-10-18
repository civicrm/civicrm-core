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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Case_Info extends CRM_Core_Component_Info {


  // docs inherited from interface
  protected $keyword = 'case';

  // docs inherited from interface
  /**
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviCase',
      'translatedName' => ts('CiviCase'),
      'title' => ts('CiviCase Engine'),
      'search' => 1,
      'showActivitiesInCore' => 0,
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getAngularModules() {
    $result = array();
    $result['crmCaseType'] = array(
      'ext' => 'civicrm',
      'js' => array('js/angular-crmCaseType.js'),
      'css' => array('css/angular-crmCaseType.css'),
    );

    CRM_Core_Resources::singleton()->addSetting(array(
      'crmCaseType' => array(
        'REL_TYPE_CNAME' => CRM_Case_XMLProcessor::REL_TYPE_CNAME,
      ),
    ));
    return $result;
  }

  // docs inherited from interface
  /**
   * @return array
   * @throws CRM_Core_Exception
   */
  public function getManagedEntities() {
    $entities = array_merge(
      CRM_Case_ManagedEntities::createManagedCaseTypes(),
      CRM_Case_ManagedEntities::createManagedActivityTypes(CRM_Case_XMLRepository::singleton(), CRM_Core_ManagedEntities::singleton()),
      CRM_Case_ManagedEntities::createManagedRelationshipTypes(CRM_Case_XMLRepository::singleton(), CRM_Core_ManagedEntities::singleton())
    );
    return $entities;
  }

  // docs inherited from interface
  /**
   * @param bool $getAllUnconditionally
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE) {
    return array(
      'delete in CiviCase',
      'administer CiviCase',
      'access my cases and activities',
      'access all cases and activities',
      'add cases',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceCounts($dao) {
    $result = array();
    if ($dao instanceof CRM_Core_DAO_OptionValue) {
      /** @var $dao CRM_Core_DAO_OptionValue */
      $activity_type_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'activity_type', 'id', 'name');
      if ($activity_type_gid == $dao->option_group_id) {
        $count = CRM_Case_XMLRepository::singleton()
          ->getActivityReferenceCount($dao->name);
        if ($count > 0) {
          $result[] = array(
            'name' => 'casetypexml:activities',
            'type' => 'casetypexml',
            'count' => $count,
          );
        }
      }
    }
    elseif ($dao instanceof CRM_Contact_DAO_RelationshipType) {
      /** @var $dao CRM_Contact_DAO_RelationshipType */
      $count = CRM_Case_XMLRepository::singleton()
        ->getRelationshipReferenceCount($dao->{CRM_Case_XMLProcessor::REL_TYPE_CNAME});
      if ($count > 0) {
        $result[] = array(
          'name' => 'casetypexml:relationships',
          'type' => 'casetypexml',
          'count' => $count,
        );
      }
    }
    return $result;
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function getUserDashboardElement() {
    return array();
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerTab() {
    return array('title' => ts('Cases'),
      'url' => 'case',
      'weight' => 50,
    );
  }

  // docs inherited from interface
  /**
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array('title' => ts('Cases'),
      'weight' => 50,
    );
  }

  // docs inherited from interface
  /**
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  // add shortcut to Create New
  /**
   * @param $shortCuts
   */
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
      $pathToCaseSampleTpl =  __DIR__ . '/xml/configuration.sample/';
      $config = CRM_Core_Config::singleton();
      CRM_Admin_Form_Setting_Component::loadCaseSampleData($config->dsn, $pathToCaseSampleTpl . 'case_sample.mysql.tpl');
      if (!CRM_Case_BAO_Case::createCaseViews()) {
        $msg = ts("Could not create the MySQL views for CiviCase. Your mysql user needs to have the 'CREATE VIEW' permission");
        CRM_Core_Error::fatal($msg);
      }
    }
  }
}

