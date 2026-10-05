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
 * Adds a computed `case_manager_id` field to the Case entity.
 *
 * There's no fixed relationship type for "case manager" - each CaseType's
 * XML definition (<CaseRoles>) declares which relationship type/direction
 * plays that role, so it can't be expressed as a plain DB join. This
 * provider resolves the per-case-type role via
 * CRM_Case_XMLProcessor_Process::getCaseManagerRoleId() once per query and
 * bakes the result into a CASE-driven correlated subquery.
 *
 * @service
 * @internal
 */
class CaseManagerSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $field = (new FieldSpec('case_manager_id', $spec->getEntity(), 'Integer'))
      ->setTitle(ts('Case Manager'))
      ->setDescription(ts('Contact currently holding the case-type\'s manager role for this case.'))
      ->setType('Extra')
      ->setColumnName('id')
      ->setFkEntity('Contact')
      ->setInputType('EntityRef')
      ->setOperators(['=', '!=', 'IN', 'NOT IN', 'IS EMPTY', 'IS NOT EMPTY', 'IS NULL', 'IS NOT NULL'])
      ->setSqlRenderer([__CLASS__, 'renderCaseManagerSql']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Case' && $action === 'get';
  }

  /**
   * Builds a `CASE case_type_id WHEN ... THEN (correlated subquery) END`
   * expression that picks the active (or, failing that, most-recently
   * expired) contact in the case-type's configured manager role.
   *
   * Mirrors the selection logic in CRM_Case_BAO_Case::getCaseManagerContact().
   */
  public static function renderCaseManagerSql(array $field, Api4SelectQuery $query): string {
    // $field['sql_name'] is the alias-qualified `id` column (e.g. `a`.`id`)
    // in whatever context this field is being selected from (main entity or a join).
    $alias = substr($field['sql_name'], 0, strrpos($field['sql_name'], '.'));

    $xmlProcessor = new \CRM_Case_XMLProcessor_Process();
    $caseTypeNames = \CRM_Case_PseudoConstant::caseType('name');

    $whenClauses = [];
    foreach ($caseTypeNames as $caseTypeId => $caseTypeName) {
      $managerRoleId = $xmlProcessor->getCaseManagerRoleId($caseTypeName);
      if (empty($managerRoleId)) {
        continue;
      }
      $relationshipTypeId = (int) substr($managerRoleId, 0, -4);
      $contactColumn = substr($managerRoleId, -4) === '_a_b' ? 'contact_id_b' : 'contact_id_a';
      $caseTypeId = (int) $caseTypeId;

      $whenClauses[] = "WHEN $alias.`case_type_id` = $caseTypeId THEN (
        SELECT r.`$contactColumn`
        FROM `civicrm_relationship` r
        WHERE r.`case_id` = $alias.`id`
          AND r.`relationship_type_id` = $relationshipTypeId
        ORDER BY r.`is_active` DESC, r.`end_date` DESC
        LIMIT 1
      )";
    }

    if (!$whenClauses) {
      return 'NULL';
    }

    return '(CASE ' . implode(' ', $whenClauses) . ' END)';
  }

}
