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
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class RelationshipCacheSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $mirrorFields = [
      'description' => 'description',
      // Alias these two to avoid name conflict with fields in civicrm_contact table during bridge joins
      'created_date' => 'relationship_created_date',
      'modified_date' => 'relationship_modified_date',
    ];
    $relationshipFields = \CRM_Contact_DAO_Relationship::getSupportedFields();
    foreach (array_intersect_key($relationshipFields, $mirrorFields) as $origName => $origField) {
      $field = new FieldSpec($mirrorFields[$origName], $spec->getEntity(), \CRM_Utils_Type::typeToString($origField['type']));
      $field
        ->setTitle($origField['title'])
        ->setLabel($origField['html']['label'] ?? NULL)
        // Fetches the value from the relationship
        ->setColumnName('relationship_id')
        ->setDescription($origField['description'])
        ->setSqlRenderer([__CLASS__, 'mirrorRelationshipField']);
      $spec->addFieldSpec($field);
    }

    $directionalFields = [
      'permission_near_to_far' => [
        'title' => ts("Permission to access related contact"),
        'description' => ts('Whether contact has permission to view or update update the related contact'),
      ],
      'permission_far_to_near' => [
        'title' => ts("Permission to be accessed by related contact"),
        'description' => ts('Whether related contact has permission to view or update this contact'),
      ],
    ];
    foreach ($directionalFields as $name => $fieldInfo) {
      $field = new FieldSpec($name, $spec->getEntity(), 'Integer');
      $field
        ->setTitle($fieldInfo['title'])
        // Fetches the value from the relationship
        ->setColumnName('relationship_id')
        ->setDescription($fieldInfo['description'])
        ->setSuffixes(['name', 'label', 'icon'])
        ->setOptionsCallback(['CRM_Core_SelectValues', 'getPermissionedRelationshipOptions'])
        ->setSqlRenderer([__CLASS__, 'directionalRelationshipField']);
      $spec->addFieldSpec($field);
    }

  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'RelationshipCache' && $action === 'get';
  }

  /**
   * Generates sql for `description`, `relationship_created_date` and `relationship_modified_date` pseudo fields
   *
   * Note: the latter two have `relationship_` prefixing the field names to avoid naming conflicts during bridge joins.
   *
   * @param array $field
   * return string
   */
  public static function mirrorRelationshipField(array $field): string {
    $fieldName = str_replace('relationship_', '', $field['name']);
    return "(SELECT r.`$fieldName` FROM `civicrm_relationship` r WHERE r.`id` = {$field['sql_name']})";
  }

  /**
   * Generates sql for `permission_near_to_far` and `permission_far_to_near` pseudo fields
   *
   * @param array $field
   * return string
   */
  public static function directionalRelationshipField(array $field): string {
    $direction = $field['name'] === 'permission_near_to_far' ? 'a_b' : 'b_a';
    $orientation = str_replace('.`relationship_id`', '.`orientation`', $field['sql_name']);
    return "(SELECT IF($orientation = '$direction', r.is_permission_a_b, r.is_permission_b_a) FROM `civicrm_relationship` r WHERE r.`id` = {$field['sql_name']})";
  }

}
