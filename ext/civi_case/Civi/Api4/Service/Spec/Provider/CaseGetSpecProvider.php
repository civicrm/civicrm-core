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
 * @service
 * @internal
 */
class CaseGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $creator = new FieldSpec('creator_id', $spec->getEntity(), 'Integer');
    $creator->setTitle(ts('Case Creator'));
    $creator->setLabel(ts('Case Creator'));
    $creator->setDescription('Contact who created the case.');
    $creator->setFkEntity('Contact');
    $creator->setSqlRenderer([__CLASS__, 'renderSqlForCaseCreator']);
    $spec->addFieldSpec($creator);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Case' && $action === 'get';
  }

  public static function renderSqlForCaseCreator(array $field, Api4SelectQuery $query): string {
    $relationshipTypeDao = new \CRM_Contact_DAO_RelationshipType();
    $relationshipTypeDao->find();
    $relationshipTypes = $relationshipTypeDao->fetchAll();

    $caseTypeDao = new \CRM_Case_DAO_CaseType();
    $caseTypeDao->find();

    while ($caseTypeDao->fetch()) {
      $row = $caseTypeDao->toArray();
      if (empty($row['definition'])) {
        $xml = \CRM_Case_XMLRepository::singleton()->retrieveFile($row['name']);
        $row['definition'] = $xml ? \CRM_Case_BAO_CaseType::convertXmlToDefinition($xml) : [];
      }
      else {
        \CRM_Case_BAO_CaseType::formatOutputDefinition($row['definition'], $row);
      }
      foreach ($row['definition']['caseRoles'] ?? [] as $role) {
        if (!empty($role['creator'])) {
          $caseTypeId = $row['id'];
          foreach ($relationshipTypes as $relationshipType) {
            if ($relationshipType['name_a_b'] == $role['name']) {
              $caseTypeMap[$caseTypeId]['relTypeId'] = $relationshipType['id'];
              $caseTypeMap[$caseTypeId]['orientation'] = 'b_a';
              break 2;
            }
            if ($relationshipType['name_b_a'] == $role['name']) {
              $caseTypeMap[$caseTypeId]['relTypeId'] = $relationshipType['id'];
              $caseTypeMap[$caseTypeId]['orientation'] = 'a_b';
              break 2;
            }
          }
        }
      }
    }

    if (empty($caseTypeMap)) {
      return 'NULL';
    }

    foreach ($caseTypeMap as $caseTypeId => $mapping) {
      $idMap[] = "WHEN $caseTypeId THEN {$mapping['relTypeId']}";
      $orientationMap[] = "WHEN $caseTypeId THEN '{$mapping['orientation']}'";
    }

    $caseIdSqlName = $query->getField('id')['sql_name'];

    $separator = "\n                  ";
    return "(SELECT `far_contact_id`
              FROM `civicrm_relationship_cache`
              WHERE `case_id` = $caseIdSqlName
              AND `relationship_type_id` = (
                  CASE `case_type_id`$separator" . implode($separator, $idMap) . "
                  ELSE NULL END)
              AND `orientation` = (
                  CASE `case_type_id`$separator" . implode($separator, $orientationMap) . "
                  ELSE NULL END)
      LIMIT 1)";
  }

}
