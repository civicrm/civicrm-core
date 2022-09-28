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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Case_Info extends CRM_Core_Component_Info {


  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'case';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviCase',
      'translatedName' => ts('CiviCase'),
      'title' => ts('CiviCase Engine'),
      'search' => 1,
      'showActivitiesInCore' => Civi::settings()->get('civicaseShowCaseActivities') ?? 0,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getAngularModules() {
    global $civicrm_root;

    $result = [];
    $result['crmCaseType'] = include "$civicrm_root/ang/crmCaseType.ang.php";
    return $result;
  }

  /**
   * @inheritDoc
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

  /**
   * @inheritDoc
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = [
      'delete in CiviCase' => [
        ts('delete in CiviCase'),
        ts('Delete cases'),
      ],
      'administer CiviCase' => [
        ts('administer CiviCase'),
        ts('Define case types, access deleted cases'),
      ],
      'access my cases and activities' => [
        ts('access my cases and activities'),
        ts('View and edit only those cases managed by this user'),
      ],
      'access all cases and activities' => [
        ts('access all cases and activities'),
        ts('View and edit all cases (for visible contacts)'),
      ],
      'add cases' => [
        ts('add cases'),
        ts('Open a new case'),
      ],
    ];

    if (!$descriptions) {
      foreach ($permissions as $name => $attr) {
        $permissions[$name] = array_shift($attr);
      }
    }

    return $permissions;
  }

  /**
   * @inheritDoc
   */
  public function getReferenceCounts($dao) {
    $result = [];
    if ($dao instanceof CRM_Core_DAO_OptionValue) {
      /** @var $dao CRM_Core_DAO_OptionValue */
      $activity_type_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'activity_type', 'id', 'name');
      if ($activity_type_gid == $dao->option_group_id) {
        $count = CRM_Case_XMLRepository::singleton()
          ->getActivityReferenceCount($dao->name);
        if ($count > 0) {
          $result[] = [
            'name' => 'casetypexml:activities',
            'type' => 'casetypexml',
            'count' => $count,
          ];
        }
      }
    }
    elseif ($dao instanceof CRM_Contact_DAO_RelationshipType) {
      /** @var $dao CRM_Contact_DAO_RelationshipType */

      // Need to look both directions, but no need to translate case role
      // direction from XML perspective to client-based perspective
      $xmlRepo = CRM_Case_XMLRepository::singleton();
      $count = $xmlRepo->getRelationshipReferenceCount($dao->label_a_b);
      if ($dao->label_a_b != $dao->label_b_a) {
        $count += $xmlRepo->getRelationshipReferenceCount($dao->label_b_a);
      }
      if ($count > 0) {
        $result[] = [
          'name' => 'casetypexml:relationships',
          'type' => 'casetypexml',
          'count' => $count,
        ];
      }
    }
    return $result;
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function getUserDashboardElement() {
    return [];
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerTab() {
    return [
      'title' => ts('Cases'),
      'url' => 'case',
      'weight' => 50,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-folder-open-o';
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return [
      'title' => ts('Cases'),
      'weight' => 50,
    ];
  }

  /**
   * @inheritDoc
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access all cases and activities') ||
      CRM_Core_Permission::check('add cases')
    ) {
      $activityType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
      if ($activityType) {
        $shortCuts = array_merge($shortCuts, [
          [
            'path' => 'civicrm/case/add',
            'query' => "reset=1&action=add&atype={$activityType}&context=standalone",
            'ref' => 'new-case',
            'title' => ts('Case'),
          ],
        ]);
      }
    }
  }

  /**
   * (Setting Callback)
   * Respond to changes in the "enable_components" setting
   *
   * If CiviCase is being enabled, load the case related sample data
   *
   * @param array $oldValue
   *   List of component names.
   * @param array $newValue
   *   List of component names.
   * @param array $metadata
   *   Specification of the setting (per *.settings.php).
   *
   * @throws \CRM_Core_Exception.
   */
  public static function onToggleComponents($oldValue, $newValue, $metadata) {
    if (
      in_array('CiviCase', $newValue)
      &&
      (!$oldValue || !in_array('CiviCase', $oldValue))
    ) {
      $pathToCaseSampleTpl = __DIR__ . '/xml/configuration.sample/';
      self::loadCaseSampleData($pathToCaseSampleTpl . 'case_sample.mysql.tpl');
    }
  }

  /**
   * Load case sample data.
   *
   * @param string $fileName
   * @param bool $lineMode
   */
  public static function loadCaseSampleData($fileName, $lineMode = FALSE) {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    $locales = CRM_Core_I18n::getMultilingual();
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('multilingual', (bool) $locales);
    $smarty->assign('locales', $locales);

    if (!$lineMode) {

      $string = $smarty->fetch($fileName);
      // change \r\n to fix windows issues
      $string = str_replace("\r\n", "\n", $string);

      //get rid of comments starting with # and --

      $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

      $queries = preg_split('/;$/m', $string);
      foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
          try {
            $res = &$db->query($query);
          }
          catch (Exception $e) {
            die("Cannot execute $query: " . $e->getMessage());
          }
        }
      }
    }
    else {
      $fd = fopen($fileName, "r");
      while ($string = fgets($fd)) {
        $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
        $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

        $string = trim($string);
        if (!empty($string)) {
          try {
            $res = &$db->query($string);
          }
          catch (Exception $e) {
            die("Cannot execute $string: " . $e->getMessage());
          }
        }
      }
    }
  }

  /**
   * @return array
   *   Array(string $value => string $label).
   */
  public static function getRedactOptions() {
    return [
      'default' => ts('Default'),
      '0' => ts('Do not redact emails'),
      '1' => ts('Redact emails'),
    ];
  }

  /**
   * @return array
   *   Array(string $value => string $label).
   */
  public static function getMultiClientOptions() {
    return [
      'default' => ts('Default'),
      '0' => ts('Single client per case'),
      '1' => ts('Multiple client per case'),
    ];
  }

  /**
   * @return array
   *   Array(string $value => string $label).
   */
  public static function getSortOptions() {
    return [
      'default' => ts('Default'),
      '0' => ts('Definition order'),
      '1' => ts('Alphabetical order'),
    ];
  }

}
