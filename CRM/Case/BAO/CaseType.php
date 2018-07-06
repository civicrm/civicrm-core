<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class contains the functions for Case Type management.
 */
class CRM_Case_BAO_CaseType extends CRM_Case_DAO_CaseType {

  /**
   * Static field for all the case information that we can potentially export.
   *
   * @var array
   */
  static $_exportableFields = NULL;

  /**
   * Takes an associative array and creates a Case Type object.
   *
   * the function extract all the params it needs to initialize the create a
   * case type object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @throws CRM_Core_Exception
   *
   * @return CRM_Case_BAO_CaseType
   */
  public static function add(&$params) {
    $caseTypeDAO = new CRM_Case_DAO_CaseType();

    // form the name only if missing: CRM-627
    $nameParam = CRM_Utils_Array::value('name', $params, NULL);
    if (!$nameParam && empty($params['id'])) {
      $params['name'] = CRM_Utils_String::titleToVar($params['title']);
    }

    // Old case-types (pre-4.5) may keep their ucky names, but new case-types must satisfy isValidName()
    if (empty($params['id']) && !empty($params['name']) && !CRM_Case_BAO_CaseType::isValidName($params['name'])) {
      throw new CRM_Core_Exception("Cannot create new case-type with malformed name [{$params['name']}]");
    }

    $caseTypeName = (isset($params['name'])) ? $params['name'] : CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $params['id'], 'name', 'id', TRUE);

    // function to format definition column
    if (isset($params['definition']) && is_array($params['definition'])) {
      $params['definition'] = self::convertDefinitionToXML($caseTypeName, $params['definition']);
      CRM_Core_ManagedEntities::scheduleReconciliation();
    }

    $caseTypeDAO->copyValues($params);
    $result = $caseTypeDAO->save();
    CRM_Case_XMLRepository::singleton()->flush();
    return $result;
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName == 'definition') {
      $this->{$fieldName} = "<CaseType><name>TestCaseType{$counter}</name></CaseType>";
    }
    else {
      parent::assignTestValue($fieldName, $fieldDef, $counter);
    }
  }


  /**
   * Format / convert submitted array to xml for case type definition
   *
   * @param string $name
   * @param array $definition
   *   The case-type definition expressed as an array-tree.
   * @return string
   *   XML
   */
  public static function convertDefinitionToXML($name, $definition) {
    $xmlFile = '<?xml version="1.0" encoding="utf-8" ?>' . "\n\n<CaseType>\n";
    $xmlFile .= "<name>" . self::encodeXmlString($name) . "</name>\n";

    if (array_key_exists('forkable', $definition)) {
      $xmlFile .= "<forkable>" . ((int) $definition['forkable']) . "</forkable>\n";
    }

    if (isset($definition['activityTypes'])) {
      $xmlFile .= "<ActivityTypes>\n";
      foreach ($definition['activityTypes'] as $values) {
        $xmlFile .= "<ActivityType>\n";
        foreach ($values as $key => $value) {
          $xmlFile .= "<{$key}>" . self::encodeXmlString($value) . "</{$key}>\n";
        }
        $xmlFile .= "</ActivityType>\n";
      }
      $xmlFile .= "</ActivityTypes>\n";
    }

    if (!empty($definition['statuses'])) {
      $xmlFile .= "<Statuses>\n";
      foreach ($definition['statuses'] as $value) {
        $xmlFile .= "<Status>$value</Status>\n";
      }
      $xmlFile .= "</Statuses>\n";
    }

    if (isset($definition['activitySets'])) {
      $xmlFile .= "<ActivitySets>\n";
      foreach ($definition['activitySets'] as $k => $val) {
        $xmlFile .= "<ActivitySet>\n";
        foreach ($val as $index => $setVal) {
          switch ($index) {
            case 'activityTypes':
              if (!empty($setVal)) {
                $xmlFile .= "<ActivityTypes>\n";
                foreach ($setVal as $values) {
                  $xmlFile .= "<ActivityType>\n";
                  foreach ($values as $key => $value) {
                    $xmlFile .= "<{$key}>" . self::encodeXmlString($value) . "</{$key}>\n";
                  }
                  $xmlFile .= "</ActivityType>\n";
                }
                $xmlFile .= "</ActivityTypes>\n";
              }
              break;

            case 'sequence': // passthrough
            case 'timeline':
              if ($setVal) {
                $xmlFile .= "<{$index}>true</{$index}>\n";
              }
              break;

            default:
              $xmlFile .= "<{$index}>" . self::encodeXmlString($setVal) . "</{$index}>\n";
          }
        }

        $xmlFile .= "</ActivitySet>\n";
      }

      $xmlFile .= "</ActivitySets>\n";
    }

    if (isset($definition['caseRoles'])) {
      $xmlFile .= "<CaseRoles>\n";
      foreach ($definition['caseRoles'] as $values) {
        $xmlFile .= "<RelationshipType>\n";
        foreach ($values as $key => $value) {
          $xmlFile .= "<{$key}>" . self::encodeXmlString($value) . "</{$key}>\n";
        }
        $xmlFile .= "</RelationshipType>\n";
      }
      $xmlFile .= "</CaseRoles>\n";
    }

    $xmlFile .= '</CaseType>';
    return $xmlFile;
  }

  /**
   * Ugh. This shouldn't exist. Use a real XML-encoder.
   *
   * Escape a string for use in XML.
   *
   * @param string $str
   *   A string which should outputted to XML.
   * @return string
   * @deprecated
   */
  protected static function encodeXmlString($str) {
    // PHP 5.4: return htmlspecialchars($str, ENT_XML1, 'UTF-8')
    return htmlspecialchars($str);
  }

  /**
   * Get the case definition either from db or read from xml file.
   *
   * @param SimpleXmlElement $xml
   *   A single case-type record.
   *
   * @return array
   *   the definition of the case-type, expressed as PHP array-tree
   */
  public static function convertXmlToDefinition($xml) {
    // build PHP array based on definition
    $definition = array();

    if (isset($xml->forkable)) {
      $definition['forkable'] = (int) $xml->forkable;
    }

    // set activity types
    if (isset($xml->ActivityTypes)) {
      $definition['activityTypes'] = array();
      foreach ($xml->ActivityTypes->ActivityType as $activityTypeXML) {
        $definition['activityTypes'][] = json_decode(json_encode($activityTypeXML), TRUE);
      }
    }

    // set statuses
    if (isset($xml->Statuses)) {
      $definition['statuses'] = (array) $xml->Statuses->Status;
    }

    // set activity sets
    if (isset($xml->ActivitySets)) {
      $definition['activitySets'] = array();
      $definition['timelineActivityTypes'] = array();

      foreach ($xml->ActivitySets->ActivitySet as $activitySetXML) {
        // parse basic properties
        $activitySet = array();
        $activitySet['name'] = (string) $activitySetXML->name;
        $activitySet['label'] = (string) $activitySetXML->label;
        if ('true' == (string) $activitySetXML->timeline) {
          $activitySet['timeline'] = 1;
        }
        if ('true' == (string) $activitySetXML->sequence) {
          $activitySet['sequence'] = 1;
        }

        if (isset($activitySetXML->ActivityTypes)) {
          $activitySet['activityTypes'] = array();
          foreach ($activitySetXML->ActivityTypes->ActivityType as $activityTypeXML) {
            $activityType = json_decode(json_encode($activityTypeXML), TRUE);
            $activitySet['activityTypes'][] = $activityType;
            if ($activitySetXML->timeline) {
              $definition['timelineActivityTypes'][] = $activityType;
            }
          }
        }
        $definition['activitySets'][] = $activitySet;
      }
    }

    // set case roles
    if (isset($xml->CaseRoles)) {
      $definition['caseRoles'] = array();
      foreach ($xml->CaseRoles->RelationshipType as $caseRoleXml) {
        $definition['caseRoles'][] = json_decode(json_encode($caseRoleXml), TRUE);
      }
    }

    return $definition;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return CRM_Case_BAO_CaseType|null the found object or null
   */
  public static function &getValues(&$params, &$values) {
    $caseType = new CRM_Case_BAO_CaseType();

    $caseType->copyValues($params);

    if ($caseType->find(TRUE)) {
      CRM_Core_DAO::storeValues($caseType, $values);
      return $caseType;
    }
    return NULL;
  }

  /**
   * Takes an associative array and creates a case type object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Case_BAO_CaseType
   */
  public static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'CaseType', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'CaseType', NULL, $params);
    }

    $caseType = self::add($params);

    if (is_a($caseType, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $caseType;
    }

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'CaseType', $caseType->id, $case);
    }
    else {
      CRM_Utils_Hook::post('create', 'CaseType', $caseType->id, $case);
    }
    $transaction->commit();
    CRM_Case_XMLRepository::singleton(TRUE);
    CRM_Core_OptionGroup::flushAll();

    return $caseType;
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   *
   * @return CRM_Case_BAO_CaseType
   */
  public static function retrieve(&$params, &$defaults) {
    $caseType = CRM_Case_BAO_CaseType::getValues($params, $defaults);
    return $caseType;
  }

  /**
   * @param int $caseTypeId
   *
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public static function del($caseTypeId) {
    $caseType = new CRM_Case_DAO_CaseType();
    $caseType->id = $caseTypeId;
    $refCounts = $caseType->getReferenceCounts();
    $total = array_sum(CRM_Utils_Array::collect('count', $refCounts));
    if ($total) {
      throw new CRM_Core_Exception(ts("You can not delete this case type -- it is assigned to %1 existing case record(s). If you do not want this case type to be used going forward, consider disabling it instead.", array(1 => $total)));
    }
    $result = $caseType->delete();
    CRM_Case_XMLRepository::singleton(TRUE);
    return $result;
  }

  /**
   * Determine if a case-type name is well-formed
   *
   * @param string $caseType
   * @return bool
   */
  public static function isValidName($caseType) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $caseType);
  }

  /**
   * Determine if the case-type has *both* DB and file-based definitions.
   *
   * @param int $caseTypeId
   * @return bool|null
   *   TRUE if there are *both* DB and file-based definitions
   */
  public static function isForked($caseTypeId) {
    $caseTypeName = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseTypeId, 'name', 'id', TRUE);
    if ($caseTypeName) {
      $dbDefinition = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseTypeId, 'definition', 'id', TRUE);
      $fileDefinition = CRM_Case_XMLRepository::singleton()->retrieveFile($caseTypeName);
      return $fileDefinition && $dbDefinition;
    }
    return NULL;
  }

  /**
   * Determine if modifications are allowed on the case-type
   *
   * @param int $caseTypeId
   * @return bool
   *   TRUE if the definition can be modified
   */
  public static function isForkable($caseTypeId) {
    $caseTypeName = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseTypeId, 'name', 'id', TRUE);
    if ($caseTypeName) {
      // if file-based definition explicitly disables "forkable" option, then don't allow changes to definition
      $fileDefinition = CRM_Case_XMLRepository::singleton()->retrieveFile($caseTypeName);
      if ($fileDefinition && isset($fileDefinition->forkable)) {
        return CRM_Utils_String::strtobool((string) $fileDefinition->forkable);
      }
    }
    return TRUE;
  }

}
