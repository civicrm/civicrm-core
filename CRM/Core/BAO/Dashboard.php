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

use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class contains Contact dashboard related functions.
 */
class CRM_Core_BAO_Dashboard extends CRM_Core_DAO_Dashboard implements EventSubscriberInterface {

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_angularModules' => ['addDashboardModuleDependencies', -2000],
    ];
  }

  /**
   * Create or update Dashboard.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_Dashboard
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Dashboard', $params['id'] ?? NULL, $params);
    $dao = self::addDashlet($params);
    CRM_Utils_Hook::post($hook, 'Dashboard', $dao->id, $dao, $params);
    return $dao;
  }

  /**
   * Get all available contact dashlets
   *
   * @return array
   *   array of dashlets
   * @throws \CRM_Core_Exception
   */
  public static function getContactDashlets(): array {
    $cid = CRM_Core_Session::getLoggedInContactID();
    $results = [];

    // Get all dashlets we have access to
    $availableDashlets = (array) \Civi\Api4\Dashboard::get(TRUE)
      ->addWhere('domain_id', '=', 'current_domain')
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->indexBy('id');

    \Civi\Api4\DashboardContact::initialize()
      ->execute();

    $dashletsUsed = \Civi\Api4\DashboardContact::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('dashboard_id', 'IN', array_keys($availableDashlets))
      ->addSelect('column_no', 'is_active', 'dashboard_id', 'weight', 'contact_id')
      ->addOrderBy('weight')
      ->execute();

    // first add linked dashlet records, in order to respect the linked weights
    foreach ($dashletsUsed as $dashletUsed) {
      if (!isset($availableDashlets[$dashletUsed['dashboard_id']])) {
        continue;
      }
      $dashletRecord = $availableDashlets[$dashletUsed['dashboard_id']];
      $results[] = array_merge($dashletRecord, [
        'dashboard_contact.id' => $dashletUsed['id'],
        'dashboard_contact.contact_id' => $dashletUsed['contact_id'],
        'dashboard_contact.weight' => $dashletUsed['weight'],
        'dashboard_contact.column_no' => $dashletUsed['column_no'],
        'dashboard_contact.is_active' => $dashletUsed['is_active'],
      ]);
      // remove from availableDashlets so we dont add again below
      unset($availableDashlets[$dashletUsed['dashboard_id']]);
    }
    // now add the remaining unlinked dashlets
    foreach ($availableDashlets as $dashlet) {
      $results[] = array_merge($dashlet, [
        'dashboard_contact.id' => NULL,
        'dashboard_contact.contact_id' => NULL,
        'dashboard_contact.weight' => NULL,
        'dashboard_contact.column_no' => NULL,
        'dashboard_contact.is_active' => NULL,
      ]);
    }

    return $results;
  }

  /**
   * settingsFactory from crmDashboard.ang.php
   *
   * @return array
   */
  public static function angularSettings() {
    return [
      'dashlets' => self::getContactDashlets(),
    ];
  }

  /**
   * partialsCallback from crmDashboard.ang.php
   *
   * Generates an html template for each angular-based dashlet.
   *
   * @param $moduleName
   * @param $module
   * @return array
   */
  public static function angularPartials($moduleName, $module): array {
    $angularDashletDirectives = \Civi\Api4\Dashboard::get(FALSE)
      ->addWhere('directive', 'IS NOT EMPTY')
      ->addSelect('directive', '')
      ->execute()
      ->column('directive');

    $partials = [];
    foreach ($angularDashletDirectives as $directive) {
      $partials["~/{$moduleName}/directives/{$directive}.html"] = "<{$directive}></{$directive}>";
    }
    return $partials;
  }

  /**
   * Add modules that provide dashlet directives as dependencies to crmDashboard
   */
  public static function addDashboardModuleDependencies(GenericHookEvent $e): void {
    $dashletDirectives = \Civi\Api4\Dashboard::get(FALSE)
      ->addWhere('directive', 'IS NOT EMPTY')
      ->addSelect('directive', '')
      ->execute()
      ->column('directive');

    $dashletModules = [];

    // Find (the first) module that provides each directive
    foreach ($dashletDirectives as $directive) {
      foreach ($e->angularModules as $moduleName => $module) {
        if (!empty($module['exports'][$directive])) {
          $dashletModules[] = $moduleName;
          continue 2;
        }
      }
      \Civi::log()->warning("No Angular module found to provide crmDashboard dashlet directive: {$directive}");
    }

    $e->angularModules['crmDashboard']['requires'] = array_unique(array_merge(
      $e->angularModules['crmDashboard']['requires'],
      $dashletModules
    ));
  }

  /**
   * Enforce permission restrictions on Dashboards.
   *
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @return array
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $dao = CRM_Core_DAO::executeQuery("SELECT id, permission, permission_operator FROM civicrm_dashboard");
    $permittedIds = [];
    while ($dao->fetch()) {
      $permission = CRM_Core_DAO::unSerializeField($dao->permission, CRM_Core_DAO::SERIALIZE_COMMA);
      if (self::checkPermission($permission, $dao->permission_operator, $userId)) {
        $permittedIds[] = $dao->id;
      }
    }

    $clauses = [
      'id' => ['IN (' . implode(', ', $permittedIds ?: [0]) . ')'],
    ];

    CRM_Utils_Hook::selectWhereClause($entityName ?? $this, $clauses);

    return $clauses;
  }

  /**
   * Check dashlet permission for current user.
   *
   * @param array|null $permissions
   * @param string|null $operator
   * @param int|null $contactId
   *
   * @return bool
   *   true if user has permission to view dashlet
   */
  private static function checkPermission(?array $permissions, ?string $operator, ?int $contactId = NULL): bool {
    if ($permissions) {
      static $allComponents;
      if (!$allComponents) {
        $allComponents = CRM_Core_Component::getNames();
      }

      $hasPermission = FALSE;
      foreach ($permissions as $key) {
        $showDashlet = TRUE;

        $componentName = CRM_Core_Permission::getComponentName($key);

        // If the permission depends on a component, ensure it is enabled
        if ($componentName) {
          if (!CRM_Core_Component::isEnabled($componentName) || !CRM_Core_Permission::check($key, $contactId)) {
            $showDashlet = FALSE;
            if ($operator == 'AND') {
              return $showDashlet;
            }
          }
          else {
            $hasPermission = TRUE;
          }
        }
        elseif (!CRM_Core_Permission::check($key, $contactId)) {
          $showDashlet = FALSE;
          if ($operator == 'AND') {
            return $showDashlet;
          }
        }
        else {
          $hasPermission = TRUE;
        }
      }

      return $showDashlet || $hasPermission;
    }
    // If permission is not set consider everyone has access.
    return TRUE;
  }

  /**
   * Add dashlets.
   *
   * @param array $params
   *
   * @return object
   *   $dashlet returns dashlet object
   */
  public static function addDashlet(&$params) {

    // special case to handle duplicate entries for report instances
    $dashboardID = $params['id'] ?? NULL;

    if (!empty($params['instanceURL'])) {
      $query = "SELECT id
                        FROM `civicrm_dashboard`
                        WHERE url LIKE '" . ($params['instanceURL'] ?? '') . "&%'";
      $dashboardID = CRM_Core_DAO::singleValueQuery($query);
    }

    $dashlet = new CRM_Core_DAO_Dashboard();

    if (!$dashboardID) {
      // Assign domain before search to allow identical dashlets in different domains.
      $dashlet->domain_id = $params['domain_id'] ?? CRM_Core_Config::domainID();

      // Try and find an existing dashlet - it will be updated if found.
      if (!empty($params['name'])) {
        $dashlet->name = $params['name'] ?? NULL;
        $dashlet->find(TRUE);
      }
    }
    else {
      $dashlet->id = $dashboardID;
    }

    $dashlet->copyValues($params);
    $dashlet->save();

    // now we need to make dashlet entries for each contact
    self::addContactDashlet($dashlet);

    return $dashlet;
  }

  /**
   * Update contact dashboard with new dashlet.
   *
   * @param object $dashlet
   */
  public static function addContactDashlet($dashlet) {
    $admin = CRM_Core_Permission::check('administer CiviCRM');

    // if dashlet is created by admin then you need to add it all contacts.
    // else just add to contact who is creating this dashlet
    $contactIDs = [];
    if ($admin) {
      $query = "SELECT distinct( contact_id )
                        FROM civicrm_dashboard_contact";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $contactIDs[$dao->contact_id] = NULL;
      }
    }
    else {
      //Get the id of Logged in User
      $contactID = CRM_Core_Session::getLoggedInContactID();
      if (!empty($contactID)) {
        $contactIDs[$contactID] = NULL;
      }
    }

    // Remove contact ids that already have this dashlet to avoid DB
    // constraint violation.
    $query = "SELECT distinct( contact_id )
              FROM civicrm_dashboard_contact WHERE dashboard_id = {$dashlet->id}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if (array_key_exists($dao->contact_id, $contactIDs)) {
        unset($contactIDs[$dao->contact_id]);
      }
    }
    if (!empty($contactIDs)) {
      foreach ($contactIDs as $contactID => $value) {
        $valuesArray[] = " ( {$dashlet->id}, {$contactID} )";
      }
      $valuesString = implode(',', $valuesArray);
      $query = "
                  INSERT INTO civicrm_dashboard_contact ( dashboard_id, contact_id )
                  VALUES {$valuesString}";

      CRM_Core_DAO::executeQuery($query);
    }
  }

}
