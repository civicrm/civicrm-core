<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contains the functions for Case Type management
 *
 */
class CRM_Case_BAO_CaseType extends CRM_Case_DAO_CaseType {

  /**
   * static field for all the case information that we can potentially export
   *
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * takes an associative array and creates a Case Type object
   *
   * the function extract all the params it needs to initialize the create a
   * case type object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @internal param array $ids the array that holds all the db ids
   *
   * @return object CRM_Case_BAO_CaseType object
   * @access public
   * @static
   */
  static function add(&$params) {
    $caseTypeDAO = new CRM_Case_DAO_CaseType();

    // function to format definition column
    self::convertDefinitionToXML($params);

    $caseTypeDAO->copyValues($params);
    return $caseTypeDAO->save();
  }

  /**
   * Function to format / convert submitted array to xml for case type definition
   *
   * @param $params associated array of submitted values
   *
   * @return void
   * @static
   * @access public
   */
  static function convertDefinitionToXML(&$params) {
    $xmlFile = '<?xml version="1.0" encoding="iso-8859-1" ?>' . "\n\n<CaseType>\n";
    $xmlFile .= "<name>{$params['name']}</name>\n";

    if (!empty($params['definition']['activityTypes'])) {
      $xmlFile .= "<ActivityTypes>\n";
      foreach ($params['definition']['activityTypes'] as $values) {
        $xmlFile .= "<ActivityType>\n";
        foreach ($values as $key => $value) {
          $xmlFile .= "<{$key}>{$value}</{$key}>\n";
        }
        $xmlFile .= "</ActivityType>\n";
      }
      $xmlFile .= "</ActivityTypes>\n";
    }

    if (!empty($params['definition']['activitySets'])) {
      $xmlFile .= "<ActivitySets>\n";
      foreach ($params['definition']['activitySets'] as $k => $val) {
        $xmlFile .= "<ActivitySet>\n";
        foreach ($val as $index => $setVal) {
          if ($index == 'activityTypes') {
            if (!empty($setVal)) {
              $xmlFile .= "<ActivityTypes>\n";
              foreach ($setVal as $values) {
                $xmlFile .= "<ActivityType>\n";
                foreach ($values as $key => $value) {
                  $xmlFile .= "<{$key}>{$value}</{$key}>\n";
                }
                $xmlFile .= "</ActivityType>\n";
              }
              $xmlFile .= "</ActivityTypes>\n";
            }
          }
          else {
            $xmlFile .= "<{$index}>{$setVal}</{$index}>\n";
          }
        }

        $xmlFile .= "</ActivitySet>\n";
      }

      $xmlFile .= "</ActivitySets>\n";
    }

    if (!empty($params['definition']['caseRoles'])) {
      $xmlFile .= "<CaseRoles>\n";
      foreach ($params['definition']['caseRoles'] as $values) {
        $xmlFile .= "<RelationshipType>\n";
        foreach ($values as $key => $value) {
          $xmlFile .= "<{$key}>{$value}</{$key}>\n";
        }
        $xmlFile .= "</RelationshipType>\n";
      }
      $xmlFile .= "</CaseRoles>\n";
    }

    $xmlFile .= '</CaseType>';
    $params['definition'] = $xmlFile;
  }

  /**
   * Function to get the case definition either from db or read from xml file
   *
   * @param $caseType a single case-type record
   *
   * @return array the definition of the case-type, expressed as PHP array-tree
   * @static
   */
  static function getCaseTypeDefinition($caseType) {
    // check if case type definition is saved in DB
    if (!empty($caseType['definition'])) {
      $xml = simplexml_load_string($caseType['definition']);
    }
    else {
      $xml = CRM_Case_XMLRepository::singleton()->retrieve($caseType['name']);
    }

    // build PHP array based on definition
    $definition = array();

    // set activity types
    $activityTypes = json_decode(json_encode($xml->ActivityTypes), TRUE);
    $definition['activityTypes'] = $activityTypes['ActivityType'];

    // set activity sets
    $activitySets = json_decode(json_encode($xml->ActivitySets), TRUE);

    // hack to fix the case when we have only one activityset
    if (!empty($activitySets['ActivitySet']['name'])) {
      $temp = $activitySets['ActivitySet'];
      $activitySets['ActivitySet'] = array($temp);
    }

    foreach ($activitySets['ActivitySet'] as $key => $value) {
      foreach ($value as $k => $val) {
        if ( $k == 'ActivityTypes') {
          $definition['activitySets'][$key]['activityTypes'] = array_pop(array_values($val));
        }
        else {
          $definition['activitySets'][$key][$k] = $val;
        }
      }
    }

    // set case roles
    $caseRoles = json_decode(json_encode($xml->CaseRoles), TRUE);
    $definition['caseRoles'] = $caseRoles['RelationshipType'];

    return $definition;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   *
   * @internal param array $ids the array that holds all the db ids
   *
   * @return CRM_Case_BAO_CaseType|null the found object or null
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values) {
    $caseType = new CRM_Case_BAO_CaseType();

    $caseType->copyValues($params);

    if ($caseType->find(TRUE)) {
      CRM_Core_DAO::storeValues($caseType, $values);
      return $caseType;
    }
    return NULL;
  }

  /**
   * takes an associative array and creates a case type object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @internal param array $ids the array that holds all the db ids
   *
   * @return object CRM_Case_BAO_CaseType object
   * @access public
   * @static
   */
  static function &create(&$params) {
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

    return $caseType;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. We'll tweak this function to be more
   * full featured over a period of time. This is the inverse function of
   * create.  It also stores all the retrieved values in the default array
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the name / value pairs
   *                        in a hierarchical manner
   *
   * @internal param array $ids (reference) the array that holds all the db ids
   *
   * @return object CRM_Case_BAO_CaseType object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $caseType = CRM_Case_BAO_CaseType::getValues($params, $defaults);
    return $caseType;
  }

  /**
   * @param $caseTypeId
   *
   * @return mixed
   */
  static function del($caseTypeId) {
    $caseType = new CRM_Case_DAO_CaseType();
    $caseType->id = $caseTypeId;
    return $caseType->delete();
  }
}
