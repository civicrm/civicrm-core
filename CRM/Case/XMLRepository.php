<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 *
 * The XMLRepository is responsible for loading XML for case-types.
 * It includes any bulk operations that apply across the list of all XML
 * documents of all case-types.
 */
class CRM_Case_XMLRepository {
  private static $singleton;

  /**
   * @var array<String,SimpleXMLElement>
   */
  protected $xml = [];

  /**
   * @var array|NULL
   */
  protected $hookCache = NULL;

  /**
   * @var array|NULL symbolic names of case-types
   */
  protected $allCaseTypes = NULL;

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
    $this->allCaseTypes = NULL;
    CRM_Core_DAO::$_dbColumnValueCache = [];
  }

  /**
   * Class constructor.
   *
   * @param array $allCaseTypes
   * @param array $xml
   */
  public function __construct($allCaseTypes = NULL, $xml = []) {
    $this->allCaseTypes = $allCaseTypes;
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
    // check if xml definition is defined in db
    $definition = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseType, 'definition', 'name');

    if (!empty($definition)) {
      list ($xml, $error) = CRM_Utils_XML::parseString($definition);
      if (!$xml) {
        throw new CRM_Core_Exception("Failed to parse CaseType XML: $error");
      }
      return $xml;
    }

    // TODO In 4.6 or 5.0, remove support for weird machine-names
    //if (!CRM_Case_BAO_CaseType::isValidName($caseType)) {
    //  // perhaps caller provider a the label instead of the name?
    //  throw new CRM_Core_Exception("Cannot load caseType with malformed name [$caseType]");
    //}

    if (!CRM_Utils_Array::value($caseType, $this->xml)) {
      $fileXml = $this->retrieveFile($caseType);
      if ($fileXml) {
        $this->xml[$caseType] = $fileXml;
      }
      else {
        return FALSE;
      }
    }
    return $this->xml[$caseType];
  }

  /**
   * Retrieve file.
   *
   * @param string $caseType
   * @return SimpleXMLElement|FALSE
   */
  public function retrieveFile($caseType) {
    $fileName = NULL;
    $fileXml = NULL;

    if (CRM_Case_BAO_CaseType::isValidName($caseType)) {
      // Search for a file based directly on the $caseType name
      $fileName = $this->findXmlFile($caseType);
    }

    // For backward compatibility, also search for double-munged file names
    // TODO In 4.6 or 5.0, remove support for loading double-munged file names
    if (!$fileName || !file_exists($fileName)) {
      $fileName = $this->findXmlFile(CRM_Case_XMLProcessor::mungeCaseType($caseType));
    }

    if ($fileName && file_exists($fileName)) {
      // read xml file
      $dom = new DomDocument();
      $xmlString = file_get_contents($fileName);
      $dom->loadXML($xmlString);
      $dom->documentURI = $fileName;
      $dom->xinclude();
      $fileXml = simplexml_import_dom($dom);
    }

    return $fileXml;
  }

  /**
   * Find xml file.
   *
   * @param string $caseType
   * @return null|string
   *   file path
   */
  public function findXmlFile($caseType) {
    // first check custom templates directory
    $fileName = NULL;

    if (!$fileName || !file_exists($fileName)) {
      $caseTypesViaHook = $this->getCaseTypesViaHook();
      if (isset($caseTypesViaHook[$caseType], $caseTypesViaHook[$caseType]['file'])) {
        $fileName = $caseTypesViaHook[$caseType]['file'];
      }
    }

    if (!$fileName || !file_exists($fileName)) {
      $config = CRM_Core_Config::singleton();
      if (isset($config->customTemplateDir) && $config->customTemplateDir) {
        // check if the file exists in the custom templates directory
        $fileName = implode(DIRECTORY_SEPARATOR,
          [
            $config->customTemplateDir,
            'CRM',
            'Case',
            'xml',
            'configuration',
            "$caseType.xml",
          ]
        );
      }
    }

    if (!$fileName || !file_exists($fileName)) {
      if (!file_exists($fileName)) {
        // check if file exists locally
        $fileName = implode(DIRECTORY_SEPARATOR,
          [
            dirname(__FILE__),
            'xml',
            'configuration',
            "$caseType.xml",
          ]
        );
      }

      if (!file_exists($fileName)) {
        // check if file exists locally
        $fileName = implode(DIRECTORY_SEPARATOR,
          [
            dirname(__FILE__),
            'xml',
            'configuration.sample',
            "$caseType.xml",
          ]
        );
      }
    }
    return file_exists($fileName) ? $fileName : NULL;
  }

  /**
   * @return array
   * @see CRM_Utils_Hook::caseTypes
   */
  public function getCaseTypesViaHook() {
    if ($this->hookCache === NULL) {
      $this->hookCache = [];
      CRM_Utils_Hook::caseTypes($this->hookCache);
    }
    return $this->hookCache;
  }

  /**
   * @return array<string> symbolic names of case-types
   */
  public function getAllCaseTypes() {
    if ($this->allCaseTypes === NULL) {
      $this->allCaseTypes = CRM_Case_PseudoConstant::caseType("name");
    }
    return $this->allCaseTypes;
  }

  /**
   * @return array<string> symbolic-names of activity-types
   */
  public function getAllDeclaredActivityTypes() {
    $result = [];

    $p = new CRM_Case_XMLProcessor_Process();
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      $result = array_merge($result, $p->getDeclaredActivityTypes($caseTypeXML));
    }

    $result = array_unique($result);
    sort($result);
    return $result;
  }

  /**
   * @return array<string> symbolic-names of relationship-types
   */
  public function getAllDeclaredRelationshipTypes() {
    $result = [];

    $p = new CRM_Case_XMLProcessor_Process();
    foreach ($this->getAllCaseTypes() as $caseTypeName) {
      $caseTypeXML = $this->retrieve($caseTypeName);
      $result = array_merge($result, $p->getDeclaredRelationshipTypes($caseTypeXML));
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
      if (in_array($activityType, $p->getDeclaredActivityTypes($caseTypeXML))) {
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
      if (in_array($relationshipTypeName, $p->getDeclaredRelationshipTypes($caseTypeXML))) {
        $count++;
      }
    }
    return $count;
  }

}
