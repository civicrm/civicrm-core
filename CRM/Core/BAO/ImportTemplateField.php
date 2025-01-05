<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Utils\CoreUtil;

/**
 * Logic for ImportTemplateField entity
 */
class CRM_Core_BAO_ImportTemplateField extends CRM_Core_DAO_ImportTemplateField {

  /**
   * Pseudoconstant callback for the 'name' field
   */
  public static function getImportableFieldOptions(string $fieldName, array $params):? array {
    $values = $params['values'];
    $entity = $values['entity'] ?? NULL;
    if (!$entity && !empty($values['id'])) {
      $entity = self::getDbVal('entity', $values['id']);
    }
    if (!$entity) {
      return [];
    }
    return (array) civicrm_api4($entity, 'getFields', [
      'action' => 'save',
      'select' => ['name', 'label', 'description'],
      'where' => [
        ['usage', 'CONTAINS', 'import'],
      ],
    ]);
  }

  /**
   * Pseudoconstant callback for the 'entity' field
   */
  public static function getImportableEntityOptions(string $fieldName, array $params):? array {
    $values = $params['values'];
    $userJobId = $values['user_job_id'] ?? NULL;
    if (!$userJobId && !empty($values['id'])) {
      $userJobId = CRM_Core_BAO_UserJob::getDbVal('user_job_id', $values['id']);
    }

    if (!$userJobId) {
      return [];
    }
    $entities = [];
    $jobTypes = array_column(CRM_Core_BAO_UserJob::getTypes(), NULL, 'id');
    $jobType = CRM_Core_BAO_UserJob::getDbVal('job_type', $values['user_job_id']);

    $mainEntityName = $jobTypes[$jobType]['entity'] ?? NULL;
    // TODO: For now each job type only supports one entity,
    // so this select list doesn't (yet) have more than one option.
    $entities[] = [
      'id' => $mainEntityName,
      'name' => $mainEntityName,
      'label' => CoreUtil::getInfoItem($mainEntityName, 'title'),
      'icon' => CoreUtil::getInfoItem($mainEntityName, 'icon'),
    ];

    return $entities;
  }

}
