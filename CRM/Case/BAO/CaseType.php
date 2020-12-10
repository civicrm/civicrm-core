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
  public static $_exportableFields = NULL;

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
    $nameParam = $params['name'] ?? NULL;
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

    $xw = new XMLWriter();
    $xw->openMemory();
    $xw->setIndent(TRUE);
    $xw->setIndentString(' ');
    $xw->startDocument("1.0", 'UTF-8');

    $xw->startElement('CaseType');

    $xw->startElement('name');
    $xw->text($name);
    $xw->fullEndElement();

    if (array_key_exists('forkable', $definition)) {
      $xw->startElement('forkable');
      $xw->text((int) $definition['forkable']);
      $xw->fullEndElement();
    }

    if (isset($definition['activityTypes'])) {
      $xw->startElement('ActivityTypes');

      foreach ($definition['activityTypes'] as $values) {
        $xw->startElement('ActivityType');
        foreach ($values as $key => $value) {
          $xw->startElement($key);
          $xw->text($value);
          $xw->fullEndElement();
        }
        // ActivityType
        $xw->fullEndElement();
      }
      // ActivityTypes
      $xw->fullEndElement();
    }

    if (!empty($definition['statuses'])) {
      $xw->startElement('Statuses');

      foreach ($definition['statuses'] as $value) {
        $xw->startElement('Status');
        $xw->text($value);
        $xw->fullEndElement();
      }
      // Statuses
      $xw->fullEndElement();
    }

    if (isset($definition['activitySets'])) {

      $xw->startElement('ActivitySets');
      foreach ($definition['activitySets'] as $k => $val) {

        $xw->startElement('ActivitySet');
        foreach ($val as $index => $setVal) {
          switch ($index) {
            case 'activityTypes':
              if (!empty($setVal)) {
                $xw->startElement('ActivityTypes');
                foreach ($setVal as $values) {
                  $xw->startElement('ActivityType');
                  foreach ($values as $key => $value) {
                    // Some parameters here may be arrays of values.
                    // Also, the tests expect an empty array to be represented as an empty value.
                    $value = (array) $value;
                    if (count($value) === 0) {
                      // Create an empty value.
                      $value[] = '';
                    }

                    foreach ($value as $val) {
                      $xw->startElement($key);
                      $xw->text($val);
                      $xw->fullEndElement();
                    }
                  }
                  // ActivityType
                  $xw->fullEndElement();
                }
                // ActivityTypes
                $xw->fullEndElement();
              }
              break;

            // passthrough
            case 'sequence':
            case 'timeline':
              if ($setVal) {
                $xw->startElement($index);
                $xw->text('true');
                $xw->fullEndElement();
              }
              break;

            default:
              $xw->startElement($index);
              $xw->text($setVal);
              $xw->fullEndElement();
          }
        }
        // ActivitySet
        $xw->fullEndElement();
      }
      // ActivitySets
      $xw->fullEndElement();
    }

    if (isset($definition['caseRoles'])) {
      $xw->startElement('CaseRoles');
      foreach ($definition['caseRoles'] as $values) {
        $xw->startElement('RelationshipType');
        foreach ($values as $key => $value) {
          $xw->startElement($key);
          if ($key == 'groups') {
            $xw->text(implode(',', (array) $value));
          }
          else {
            $xw->text($value);
          }
          // $key
          $xw->fullEndElement();
        }
        // RelationshipType
        $xw->fullEndElement();
      }
      // CaseRoles
      $xw->fullEndElement();
    }

    if (array_key_exists('restrictActivityAsgmtToCmsUser', $definition)) {
      $xw->startElement('RestrictActivityAsgmtToCmsUser');
      $xw->text($definition['restrictActivityAsgmtToCmsUser']);
      $xw->fullEndElement();
    }
    if (!empty($definition['activityAsgmtGrps'])) {
      $xw->startElement('ActivityAsgmtGrps');
      foreach ((array) $definition['activityAsgmtGrps'] as $value) {
        $xw->startElement('Group');
        $xw->text($value);
        $xw->fullEndElement();
      }
      // ActivityAsgmtGrps
      $xw->fullEndElement();
    }

    // CaseType
    $xw->fullEndElement();
    $xw->endDocument();

    return $xw->outputMemory();
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
    $definition = [];

    if (isset($xml->forkable)) {
      $definition['forkable'] = (int) $xml->forkable;
    }

    if (isset($xml->RestrictActivityAsgmtToCmsUser)) {
      $definition['restrictActivityAsgmtToCmsUser'] = (int) $xml->RestrictActivityAsgmtToCmsUser;
    }

    if (isset($xml->ActivityAsgmtGrps)) {
      $definition['activityAsgmtGrps'] = (array) $xml->ActivityAsgmtGrps->Group;
      // Backwards compat - convert group ids to group names if ids are supplied
      if (array_filter($definition['activityAsgmtGrps'], ['\CRM_Utils_Rule', 'integer']) === $definition['activityAsgmtGrps']) {
        foreach ($definition['activityAsgmtGrps'] as $idx => $group) {
          $definition['activityAsgmtGrps'][$idx] = CRM_Core_DAO::getFieldValue('CRM_Contact_BAO_Group', $group);
        }
      }
    }

    // set activity types
    if (isset($xml->ActivityTypes)) {
      $definition['activityTypes'] = [];
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
      $definition['activitySets'] = [];
      $definition['timelineActivityTypes'] = [];

      foreach ($xml->ActivitySets->ActivitySet as $activitySetXML) {
        // parse basic properties
        $activitySet = [];
        $activitySet['name'] = (string) $activitySetXML->name;
        $activitySet['label'] = (string) $activitySetXML->label;
        if ('true' == (string) $activitySetXML->timeline) {
          $activitySet['timeline'] = 1;
        }
        if ('true' == (string) $activitySetXML->sequence) {
          $activitySet['sequence'] = 1;
        }

        if (isset($activitySetXML->ActivityTypes)) {
          $activitySet['activityTypes'] = [];
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
      $definition['caseRoles'] = [];
      foreach ($xml->CaseRoles->RelationshipType as $caseRoleXml) {
        $caseRole = json_decode(json_encode($caseRoleXml), TRUE);
        if (!empty($caseRole['groups'])) {
          $caseRole['groups'] = explode(',', $caseRole['groups']);
        }
        $definition['caseRoles'][] = $caseRole;
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
      throw new CRM_Core_Exception(ts("You can not delete this case type -- it is assigned to %1 existing case record(s). If you do not want this case type to be used going forward, consider disabling it instead.", [1 => $total]));
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
