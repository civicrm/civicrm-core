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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;
use CRM_Core_DAO;

/**
 * @service
 * @internal
 */
class MembershipTypeGetSpecProvider extends AutoService implements SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $field = (new FieldSpec('relationship_type_label', $spec->getEntity(), 'Array'))
      ->setLabel(ts('Relationship Type Label'))
      ->setTitle(ts('Relationship Type Label'))
      ->setColumnName('id')
      ->setDescription(ts('Label of relationship types with direction'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->addOutputFormatter([__CLASS__, 'formatRelationshipTypeLabel']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $entity === 'MembershipType' && $action === 'get';
  }

  /**
   * @param mixed $value
   * @param array $row
   */
  public static function formatRelationshipTypeLabel(&$value, $row): void {
    $relationshipTypeId = $row['relationship_type_id'] ?? NULL;
    $relationshipDirection = $row['relationship_direction'] ?? NULL;

    // If they aren't selected in the query, fetch them dynamically.
    if (($relationshipTypeId === NULL || $relationshipDirection === NULL) && !empty($row['id'])) {
      $relationshipTypeId ??= \CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $row['id'], 'relationship_type_id');
      $relationshipDirection ??= \CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $row['id'], 'relationship_direction');
    }

    // Ensure we have arrays. Note that outputFormatters can be applied in any order, so we can't assume the format of $row['relationship_type_id'] or $row['relationship_direction']
    $relationshipTypeId = \CRM_Core_DAO::unSerializeField($relationshipTypeId, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
    $relationshipDirection = \CRM_Core_DAO::unSerializeField($relationshipDirection, CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);

    $relationshipTypeId = array_filter((array) $relationshipTypeId);
    $relationshipDirection = array_filter((array) $relationshipDirection);

    $relationshipLabels = [];
    if (!empty($relationshipTypeId) && !empty($relationshipDirection)) {
      foreach ($relationshipTypeId as $index => $typeId) {
        $direction = $relationshipDirection[$index] ?? 'a_b';
        $fieldName = 'label_' . $direction;
        if ($fieldName === 'label_a_b' || $fieldName === 'label_b_a') {
          $label = \CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $typeId, $fieldName);
          if ($label) {
            $relationshipLabels[] = $label;
          }
        }
      }
    }
    $value = $relationshipLabels;
  }

}
