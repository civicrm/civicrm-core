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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * Adds computed fields describing the logged-in user's relationship to a
 * Case: `my_case_role` (a role label, mirroring the case_role logic in
 * CRM_Case_BAO_Case::getCases()), `is_my_case` (a boolean covering any
 * role - not just the case manager), and `is_my_managed_case` (a boolean
 * scoped specifically to the case manager role, via CaseManagerSpecProvider's
 * existing `case_manager_id` logic).
 *
 * @service
 * @internal
 */
class CaseRoleSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $role = (new FieldSpec('my_case_role', $spec->getEntity(), 'String'))
      ->setTitle(ts('My Role'))
      ->setDescription(ts('The relationship role(s) the logged-in user holds on this case.'))
      ->setType('Extra')
      ->setColumnName('id')
      ->setSqlRenderer([__CLASS__, 'renderMyCaseRoleSql']);
    $spec->addFieldSpec($role);

    $isMyCase = (new FieldSpec('is_my_case', $spec->getEntity(), 'Boolean'))
      ->setTitle(ts('Is My Case'))
      ->setDescription(ts('Whether the logged-in user holds any active relationship role on this case.'))
      ->setType('Extra')
      ->setColumnName('id')
      ->setInputType('Toggle')
      ->setSqlRenderer([__CLASS__, 'renderIsMyCaseSql']);
    $spec->addFieldSpec($isMyCase);

    $isMyManagedCase = (new FieldSpec('is_my_managed_case', $spec->getEntity(), 'Boolean'))
      ->setTitle(ts('I Am Case Manager'))
      ->setDescription(ts('Whether the logged-in user is the configured case manager for this case.'))
      ->setType('Extra')
      ->setColumnName('id')
      ->setInputType('Toggle')
      ->setSqlRenderer([__CLASS__, 'renderIsMyManagedCaseSql']);
    $spec->addFieldSpec($isMyManagedCase);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Case' && $action === 'get';
  }

  public static function renderMyCaseRoleSql(array $field, Api4SelectQuery $query): string {
    $alias = self::getAlias($field);
    $userID = (int) (\CRM_Core_Session::getLoggedInContactID() ?? 0);
    if (!$userID) {
      return 'NULL';
    }
    return "(SELECT GROUP_CONCAT(DISTINCT IF(r.`contact_id_b` = $userID, rt.`label_a_b`, rt.`label_b_a`) SEPARATOR ', ')
      FROM `civicrm_relationship` r
      INNER JOIN `civicrm_relationship_type` rt ON rt.`id` = r.`relationship_type_id`
      WHERE r.`case_id` = $alias.`id`
        AND (r.`contact_id_a` = $userID OR r.`contact_id_b` = $userID))";
  }

  public static function renderIsMyCaseSql(array $field, Api4SelectQuery $query): string {
    $alias = self::getAlias($field);
    $userID = (int) (\CRM_Core_Session::getLoggedInContactID() ?? 0);
    if (!$userID) {
      return '0';
    }
    return "EXISTS (SELECT 1 FROM `civicrm_relationship` r
      WHERE r.`case_id` = $alias.`id`
        AND r.`is_active`
        AND (r.`contact_id_a` = $userID OR r.`contact_id_b` = $userID))";
  }

  /**
   * Reuses CaseManagerSpecProvider::renderCaseManagerSql() (the per-case-type
   * manager-role lookup) rather than duplicating it, wrapping its subquery
   * in an equality check against the logged-in user. $field/$query are
   * passed straight through: CaseManagerSpecProvider's alias derivation
   * only depends on $field['sql_name'], which resolves identically here
   * (both fields share `columnName('id')` on the same base Case row).
   */
  public static function renderIsMyManagedCaseSql(array $field, Api4SelectQuery $query): string {
    $userID = (int) (\CRM_Core_Session::getLoggedInContactID() ?? 0);
    if (!$userID) {
      return '0';
    }
    $managerSql = CaseManagerSpecProvider::renderCaseManagerSql($field, $query);
    return "($managerSql = $userID)";
  }

  /**
   * $field['sql_name'] is the alias-qualified `id` column (e.g. `a`.`id`)
   * in whatever context this field is being selected from (main entity or a join).
   */
  private static function getAlias(array $field): string {
    return substr($field['sql_name'], 0, strrpos($field['sql_name'], '.'));
  }

}
