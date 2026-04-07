<?php

/**
 * Class CRM_Case_ManagedEntities
 */
class CRM_Case_ManagedEntities {

  /**
   * Get a list of managed-entities representing auto-generated case-types
   * using hook_civicrm_caseTypes.
   *
   * @return array
   * @see CRM_Utils_Hook::managed
   * @throws CRM_Core_Exception
   */
  public static function createManagedCaseTypes() {
    $entities = [];

    // Use hook_civicrm_caseTypes to build a list of OptionValues
    // In the long run, we may want more specialized logic for this, but
    // this design is fairly convenient and will allow us to replace it
    // without changing the hook_civicrm_caseTypes interface.

    $caseTypes = [];
    CRM_Utils_Hook::caseTypes($caseTypes);

    $proc = new CRM_Case_XMLProcessor();
    foreach ($caseTypes as $name => $caseType) {
      $xml = $proc->retrieve($name);
      if (!$xml) {
        throw new CRM_Core_Exception("Failed to load XML for case type (" . $name . ")");
      }

      if (isset($caseType['module'], $caseType['name'], $caseType['file'])) {
        $entities[] = [
          'module' => $caseType['module'],
          'name' => $caseType['name'],
          'entity' => 'CaseType',
          'params' => [
            'version' => 4,
            'values' => [
              'name' => $caseType['name'],
              'title' => (string) $xml->name,
              'description' => (string) $xml->description,
              'is_reserved' => TRUE,
              'is_active' => TRUE,
              'weight' => $xml->weight ?: 1,
            ],
            'match' => ['name'],
          ],
        ];
      }
      else {
        throw new CRM_Core_Exception("Invalid case type");
      }
    }
    return $entities;
  }

  /**
   * Get a list of managed activity-types by searching CiviCase XML files.
   *
   * @param \CRM_Case_XMLRepository $xmlRepo
   *
   * @return array
   * @see CRM_Utils_Hook::managed
   */
  public static function createManagedActivityTypes(CRM_Case_XMLRepository $xmlRepo): array {
    $result = [];
    $validActTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');

    $actTypes = $xmlRepo->getAllDeclaredActivityTypes();
    foreach ($actTypes as $actType) {
      $managed = [
        'module' => 'civicrm',
        'name' => "civicase:act:$actType",
        'entity' => 'OptionValue',
        'update' => 'never',
        'cleanup' => 'unused',
        'params' => [
          'version' => 4,
          'values' => [
            'option_group_id.name' => 'activity_type',
            'label' => $actType,
            'name' => $actType,
            'description' => $actType,
            'component_id.name' => 'CiviCase',
          ],
          'match' => ['option_group_id', 'name'],
        ],
      ];

      // We'll create managed-entity if this record doesn't exist yet
      // or if we previously decided to manage this record.
      if (!in_array($actType, $validActTypes)) {
        $result[] = $managed;
      }
      elseif (self::getManagedEntity($managed['module'], $managed['name'])) {
        $result[] = $managed;
      }
    }

    return $result;
  }

  /**
   * Get a list of managed relationship-types by searching CiviCase XML files.
   *
   * @param \CRM_Case_XMLRepository $xmlRepo
   *
   * @return array
   * @see CRM_Utils_Hook::managed
   */
  public static function createManagedRelationshipTypes(CRM_Case_XMLRepository $xmlRepo): array {
    $result = [];

    if (!isset(Civi::$statics[__CLASS__]['reltypes'])) {
      $relationshipInfo = CRM_Core_PseudoConstant::relationshipType('name', TRUE, NULL);
      foreach ($relationshipInfo as $id => $relTypeDetails) {
        Civi::$statics[__CLASS__]['reltypes']["{$id}_a_b"] = $relTypeDetails['name_a_b'];
        if ($relTypeDetails['name_a_b'] != $relTypeDetails['name_b_a']) {
          Civi::$statics[__CLASS__]['reltypes']["{$id}_b_a"] = $relTypeDetails['name_b_a'];
        }
      }
    }
    $validRelTypes = Civi::$statics[__CLASS__]['reltypes'];

    $relTypes = $xmlRepo->getAllDeclaredRelationshipTypes();
    foreach ($relTypes as $relType) {
      // Making assumption that client is the A side of the relationship.
      // Relationship label coming from XML, meaning from perspective of
      // non-client.

      // These assumptions only apply if a case type is introduced without the
      // relationship types already existing.
      $managed = [
        'module' => 'civicrm',
        'name' => "civicase:rel:$relType",
        'entity' => 'RelationshipType',
        'update' => 'never',
        'cleanup' => 'unused',
        'params' => [
          'version' => 4,
          'values' => [
            'name_a_b' => "$relType is",
            'name_b_a' => $relType,
            'label_a_b' => "$relType is",
            'label_b_a' => $relType,
            'description' => $relType,
            'contact_type_a' => NULL,
            'contact_type_b' => NULL,
            'contact_sub_type_a' => NULL,
            'contact_sub_type_b' => NULL,
          ],
          'match' => ['name_a_b', 'name_b_a'],
        ],
      ];

      // We'll create managed-entity if this record doesn't exist yet
      // or if we previously decided to manage this record.
      if (!in_array($relType, $validRelTypes)) {
        $result[] = $managed;
      }
      elseif (self::getManagedEntity($managed['module'], $managed['name'])) {
        $result[] = $managed;
      }
    }

    return $result;
  }

  /**
   * Read a managed entity using APIv3.
   *
   * @param string $moduleName
   *   The name of the module which declared entity.
   * @param string $managedName
   *   The symbolic name of the entity.
   * @return array|NULL
   *   API representation, or NULL if the entity does not exist
   */
  private static function getManagedEntity($moduleName, $managedName) {
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $moduleName;
    $dao->name = $managedName;
    $result = NULL;
    if ($dao->find(TRUE)) {
      $params = [
        'id' => $dao->entity_id,
      ];
      try {
        $result = civicrm_api3($dao->entity_type, 'getsingle', $params);
      }
      catch (Exception $e) {
      }
    }
    return $result;
  }

}
