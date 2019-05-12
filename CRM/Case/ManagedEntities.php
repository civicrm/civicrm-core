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
            'version' => 3,
            'name' => $caseType['name'],
            'title' => (string) $xml->name,
            'description' => (string) $xml->description,
            'is_reserved' => 1,
            'is_active' => 1,
            'weight' => $xml->weight ? $xml->weight : 1,
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
   * @param \CRM_Core_ManagedEntities $me
   *
   * @return array
   * @see CRM_Utils_Hook::managed
   */
  public static function createManagedActivityTypes(CRM_Case_XMLRepository $xmlRepo, CRM_Core_ManagedEntities $me) {
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
          'version' => 3,
          'option_group_id' => 'activity_type',
          'label' => $actType,
          'name' => $actType,
          'description' => $actType,
          'component_id' => 'CiviCase',
        ],
      ];

      // We'll create managed-entity if this record doesn't exist yet
      // or if we previously decided to manage this record.
      if (!in_array($actType, $validActTypes)) {
        $result[] = $managed;
      }
      elseif ($me->get($managed['module'], $managed['name'])) {
        $result[] = $managed;
      }
    }

    return $result;
  }

  /**
   * Get a list of managed relationship-types by searching CiviCase XML files.
   *
   * @param \CRM_Case_XMLRepository $xmlRepo
   * @param \CRM_Core_ManagedEntities $me
   *
   * @return array
   * @see CRM_Utils_Hook::managed
   */
  public static function createManagedRelationshipTypes(CRM_Case_XMLRepository $xmlRepo, CRM_Core_ManagedEntities $me) {
    $result = [];

    if (!isset(Civi::$statics[__CLASS__]['reltypes'])) {
      $relationshipInfo = CRM_Core_PseudoConstant::relationshipType('label', TRUE, NULL);
      Civi::$statics[__CLASS__]['reltypes'] = CRM_Utils_Array::collect(CRM_Case_XMLProcessor::REL_TYPE_CNAME, $relationshipInfo);
    }
    $validRelTypes = Civi::$statics[__CLASS__]['reltypes'];

    $relTypes = $xmlRepo->getAllDeclaredRelationshipTypes();
    foreach ($relTypes as $relType) {
      $managed = [
        'module' => 'civicrm',
        'name' => "civicase:rel:$relType",
        'entity' => 'RelationshipType',
        'update' => 'never',
        'cleanup' => 'unused',
        'params' => [
          'version' => 3,
          'name_a_b' => "$relType is",
          'name_b_a' => $relType,
          'label_a_b' => "$relType is",
          'label_b_a' => $relType,
          'description' => $relType,
          'contact_type_a' => 'Individual',
          'contact_type_b' => 'Individual',
          'contact_sub_type_a' => NULL,
          'contact_sub_type_b' => NULL,
        ],
      ];

      // We'll create managed-entity if this record doesn't exist yet
      // or if we previously decided to manage this record.
      if (!in_array($relType, $validRelTypes)) {
        $result[] = $managed;
      }
      elseif ($me->get($managed['module'], $managed['name'])) {
        $result[] = $managed;
      }
    }

    return $result;
  }

}
