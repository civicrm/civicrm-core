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
 * The XMLRepository is responsible for loading XML for case-types.
 * It includes any bulk operations that apply across the list of all XML
 * documents of all case-types.
 */
class CRM_Case_XMLRepository {
  private static $singleton;

  /**
   * @var array
   * <String,SimpleXMLElement>
   */
  protected $xml = [];

  /**
   * @var array|null
   */
  protected $hookCache = NULL;

  /**
   * Override case types, only used by unit tests
   *
   * @var array|null
   */
  protected $unitTestCaseTypes = NULL;

  /**
   * @param bool $fresh
   * @return CRM_Case_XMLRepository
   */
  public static function singleton($fresh = FALSE) {
    if (!self::$singleton || $fresh) {
      self::$singleton = new static();
    }
    return self::$singleton;
  }

  public function flush() {
    $this->xml = [];
    $this->hookCache = NULL;
    $this->unitTestCaseTypes = NULL;
    CRM_Core_DAO::$_dbColumnValueCache = [];
  }

  /**
   * Class constructor.
   *
   * @param array $unitTestCaseTypes
   * @param array $xml
   */
  public function __construct($unitTestCaseTypes = NULL, $xml = []) {
    $this->unitTestCaseTypes = $unitTestCaseTypes;
    $this->xml = $xml;
  }

  /**
   * Retrieve case.
   *
   * @param string $caseType
   *
   * @return FALSE|\SimpleXMLElement
   * @throws \CRM_Core_Exception
   */
  public function retrieve($caseType) {
    // Used by unit tests
    if (!empty($this->xml[$caseType])) {
      return $this->xml[$caseType];
    }

    // check if xml definition is defined in db
    $definition = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseType, 'definition', 'name');

    if (!empty($definition)) {
      list ($xml, $error) = CRM_Utils_XML::parseString($definition);
      if (!$xml) {
        throw new CRM_Core_Exception("Failed to parse CaseType XML: $error");
      }
      return $xml;
    }

    return FALSE;
  }

  /**
   * @return string[]
   *   symbolic names of case-types
   */
  public function getAllCaseTypes() {
    return $this->unitTestCaseTypes ?? CRM_Case_PseudoConstant::caseType("name");
  }

  /**
   * @return array<string> symbolic-names of activity-types
   */
  public function getAllDeclaredActivityTypes() {
    $result = [];

    $p = new CRM_Case_XMLProcessor_Process();
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      if ($caseTypeXML) {
        $result = array_merge($result, $p->getDeclaredActivityTypes($caseTypeXML));
      }
    }

    $result = array_unique($result);
    sort($result);
    return $result;
  }

  /**
   * Relationships are straight from XML, described from perspective of non-client
   *
   * @return array<string> symbolic-names of relationship-types
   */
  public function getAllDeclaredRelationshipTypes() {
    $result = [];

    $p = new CRM_Case_XMLProcessor_Process();
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      if ($caseTypeXML) {
        $result = array_merge($result, $p->getDeclaredRelationshipTypes($caseTypeXML));
      }
    }

    $result = array_unique($result);
    sort($result);
    return $result;
  }

  /**
   * Determine the number of times a particular activity-type is
   * referenced in CiviCase XML.
   *
   * @param string $activityType
   *   Symbolic-name of an activity type.
   * @return int
   */
  public function getActivityReferenceCount($activityType) {
    $p = new CRM_Case_XMLProcessor_Process();
    $count = 0;
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      if ($caseTypeXML && in_array($activityType, $p->getDeclaredActivityTypes($caseTypeXML))) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Determine the number of times a particular activity-type is
   * referenced in CiviCase XML.
   *
   * @param string $relationshipTypeName
   *   Symbolic-name of a relationship-type.
   * @return int
   */
  public function getRelationshipReferenceCount($relationshipTypeName) {
    $p = new CRM_Case_XMLProcessor_Process();
    $count = 0;
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      if ($caseTypeXML && in_array($relationshipTypeName, $p->getDeclaredRelationshipTypes($caseTypeXML))) {
        $count++;
      }
    }
    return $count;
  }

}
