<?php
// $Id: Relationship.php 45502 2013-02-08 13:32:55Z kurund $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv2 relationship functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Relationship
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Relationship.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Contact/BAO/Relationship.php';
require_once 'CRM/Contact/BAO/RelationshipType.php';

/**
 * Add or update a relationship
 *
 * @param  array   $params   (reference ) input parameters
 *
 * @return array (reference) id of created or updated record
 * @static void
 * @access public
 */
function civicrm_relationship_create(&$params) {
  _civicrm_initialize();

  // check params for required fields (add/update)
  $error = _civicrm_relationship_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }
  $values = array();
  require_once 'CRM/Contact/BAO/Relationship.php';
  $error = _civicrm_relationship_format_params($params, $values);

  if (civicrm_error($error)) {
    return $error;
  }

  $ids = array();
  $action = CRM_Core_Action::ADD;
  require_once 'CRM/Utils/Array.php';

  if (CRM_Utils_Array::value('id', $params)) {
    $ids['relationship'] = $params['id'];
    $ids['contactTarget'] = $params['contact_id_b'];
    $action = CRM_Core_Action::UPDATE;
  }

  $values['relationship_type_id'] = $params['relationship_type_id'] . '_a_b';
  $values['contact_check'] = array($params['contact_id_b'] => $params['contact_id_b']);
  $ids['contact'] = $params['contact_id_a'];

  $relationshipBAO = CRM_Contact_BAO_Relationship::create($values, $ids);

  if (is_a($relationshipBAO, 'CRM_Core_Error')) {
    return civicrm_create_error('Relationship can not be created');
  }
  elseif ($relationshipBAO[1]) {
    return civicrm_create_error('Relationship is not valid');
  }
  elseif ($relationshipBAO[2]) {
    return civicrm_create_error('Relationship already exists');
  }
  CRM_Contact_BAO_Relationship::relatedMemberships($params['contact_id_a'], $values, $ids, $action);

  return civicrm_create_success(array('id' => implode(',', $relationshipBAO[4])));
}

/**
 * Delete a relationship
 *
 * @param  id of relationship  $id
 *
 * @return boolean  true if success, else false
 * @static void
 * @access public
 */
function civicrm_relationship_delete(&$params) {

  if (empty($params)) {
    return civicrm_create_error('No input parameter present');
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameter is not an array'));
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    return civicrm_create_error('Missing required parameter');
  }
  require_once 'CRM/Utils/Rule.php';
  if ($params['id'] != NULL && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_create_error('Invalid value for relationship ID');
  }

  $relationBAO = new CRM_Contact_BAO_Relationship();
  $relationBAO->id = $params['id'];
  if (!$relationBAO->find(TRUE)) {
    return civicrm_create_error(ts('Relationship id is not valid'));
  }
  else {
    $relationBAO->del($params['id']);
    return civicrm_create_success(ts('Deleted relationship successfully'));
  }
}

/**
 * Function to update relationship
 *
 * @param  array $params   Associative array of property name/value pairs to update the relationship
 *
 * @return array Array with relationship information
 *
 * @access public
 *
 */
function civicrm_relationship_update($params) {
  try {
    _civicrm_initialize();
    $errorScope = CRM_Core_TemporaryErrorScope::useException();

    /*
        * Erik Hommel, 5 Oct 2010 : fix for CRM-6895
        * check if required field relationship_id is in the parms. As the
        * CRM_Contact_BAO_Relationship::getRelatonship throws up some issues
        * (CRM-6905) the relationship is retrieved with a direct query
        */


    civicrm_verify_mandatory($params, 'CRM_Contact_DAO_Relationship', array('relationship_id'));

    $names = array(
      'id', 'contact_id_a', 'contact_id_b',
      'relationship_type_id', 'start_date', 'end_date', 'is_active',
      'description', 'is_permission_a_b', 'is_permission_b_a', 'case_id',
    );

    $relationship_id = (int) $params['relationship_id'];
    $query           = "SELECT * FROM civicrm_relationship WHERE id = $relationship_id";
    $daoRelations    = CRM_Core_DAO::executeQuery($query);
    while ($daoRelations->fetch()) {
      foreach ($names as $name) {
        $current_values[$name] = $daoRelations->$name;
      }
    }
    $params = array_merge($current_values, $params);
    $params['start_date'] = date("Ymd", strtotime($params['start_date']));
    $params['end_date'] = date("Ymd", strtotime($params['end_date']));

    return civicrm_relationship_create($params);
  }
  catch(PEAR_Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
  catch(Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
}

/**
 * Function to get the relationship
 *
 * @param array   $params          (reference ) input parameters
 param['contact_id'] is mandatory
 *
 * @return        Array of all relationship.
 *
 * @access  public
 */
function civicrm_relationship_get($params) {
  if (!isset($params['contact_id'])) {
    return civicrm_create_error(ts('Could not find contact_id in input parameters.'));
  }

  return civicrm_contact_relationship_get($params);
}

/**
 * backward compatibility function to match broken naming convention in v2.2.1 and prior
 */
function civicrm_get_relationships($contact_a, $contact_b = NULL, $relationshipTypes = NULL, $sort = NULL) {
  return civicrm_contact_relationship_get($contact_a, $contact_b, $relationshipTypes, $sort);
}

/**
 * Function to get the relationship
 *
 * @param array   $contact_a          (reference ) input parameters.
 * @param array   $contact_b          (reference ) input parameters.
 * @param array   $relationshipTypes  an array of Relationship Type Name.
 * @param string  $sort               sort all relationship by relationshipId (eg asc/desc)
 *
 * @return        Array of all relationship.
 *
 * @access  public
 */
function civicrm_contact_relationship_get($contact_a, $contact_b = NULL, $relationshipTypes = NULL, $sort = NULL) {
  if (!is_array($contact_a)) {
    return civicrm_create_error(ts('Input parameter is not an array'));
  }

  if (!isset($contact_a['contact_id'])) {
    return civicrm_create_error(ts('Could not find contact_id in input parameters.'));
  }
  require_once 'CRM/Contact/BAO/Relationship.php';
  $contactID = $contact_a['contact_id'];
  $relationships = CRM_Contact_BAO_Relationship::getRelationship($contactID);

  if (!empty($relationshipTypes)) {
    $result = array();
    foreach ($relationshipTypes as $relationshipName) {
      foreach ($relationships as $key => $relationship) {
        if ($relationship['relation'] == $relationshipName) {
          $result[$key] = $relationship;
        }
      }
    }
    $relationships = $result;
  }

  if (isset($contact_b['contact_id'])) {
    $cid = $contact_b['contact_id'];
    $result = array();

    foreach ($relationships as $key => $relationship) {
      if ($relationship['cid'] == $cid) {
        $result[$key] = $relationship;
      }
    }
    $relationships = $result;
  }

  //sort by relationship id
  if ($sort) {
    if (strtolower($sort) == 'asc') {
      ksort($relationships);
    }
    elseif (strtolower($sort) == 'desc') {
      krsort($relationships);
    }
  }

  //handle custom data.
  require_once 'CRM/Core/BAO/CustomGroup.php';

  foreach ($relationships as $relationshipId => $values) {
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Relationship', CRM_Core_DAO::$_nullObject, $relationshipId, FALSE,
      $values['civicrm_relationship_type_id']
    );
    $formatTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, CRM_Core_DAO::$_nullObject);

    $defaults = array();
    CRM_Core_BAO_CustomGroup::setDefaults($formatTree, $defaults);

    if (!empty($defaults)) {
      foreach ($defaults as $key => $val) {
        $relationships[$relationshipId][$key] = $val;
      }
    }
  }

  if ($relationships) {
    return civicrm_create_success($relationships);
  }
  else {
    return civicrm_create_error(ts('Invalid Data'));
  }
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *                            '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_relationship_format_params(&$params, &$values) {
  // copy all the relationship fields as is

  $fields = CRM_Contact_DAO_Relationship::fields();
  _civicrm_store_values($fields, $params, $values);

  $relationTypes = CRM_Core_PseudoConstant::relationshipType('name', TRUE);

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    require_once 'CRM/Utils/System.php';
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    switch ($key) {
      case 'contact_id_a':
      case 'contact_id_b':
        require_once 'CRM/Utils/Rule.php';
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        break;

      case 'start_date':
      case 'end_date':
        if (!CRM_Utils_Rule::qfDate($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'relationship_type':
        foreach ($relationTypes as $relTypId => $relValue) {
          if (CRM_Utils_Array::key(ucfirst($value), $relValue)) {
            $relationshipTypeId = $relTypId;
            break;
          }
        }

        if ($relationshipTypeId) {
          if (CRM_Utils_Array::value('relationship_type_id', $values) &&
            $relationshipTypeId != $values['relationship_type_id']
          ) {
            return civicrm_create_error('Mismatched Relationship Type and Relationship Type Id');
          }
          $values['relationship_type_id'] = $params['relationship_type_id'] = $relationshipTypeId;
        }
        else {
          return civicrm_create_error('Invalid Relationship Type');
        }
      case 'relationship_type_id':
        if ($key == 'relationship_type_id' && !array_key_exists($value, $relationTypes)) {
          return civicrm_create_error("$key not a valid: $value");
        }

        // execute for both relationship_type and relationship_type_id
        $relation = $relationTypes[$params['relationship_type_id']];
        require_once 'CRM/Contact/BAO/Contact.php';
        if ($relation['contact_type_a'] &&
          $relation['contact_type_a'] != CRM_Contact_BAO_Contact::getContactType($params['contact_id_a'])
        ) {
          return civicrm_create_error("Contact ID :{$params['contact_id_a']} is not of contact type {$relation['contact_type_a']}");
        }
        if ($relation['contact_type_b'] &&
          $relation['contact_type_b'] != CRM_Contact_BAO_Contact::getContactType($params['contact_id_b'])
        ) {
          return civicrm_create_error("Contact ID :{$params['contact_id_b']} is not of contact type {$relation['contact_type_b']}");
        }
        break;

      default:
        break;
    }
  }

  if (array_key_exists('note', $params)) {
    $values['note'] = $params['note'];
  }
  _civicrm_custom_format_params($params, $values, 'Relationship');

  return array();
}

/**
 * This function ensures that we have the right input parameters
 *
 * We also need to make sure we run all the form rules on the params list
 * to ensure that the params are valid
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new relationship.
 *
 * @return bool|CRM_Utils_Error
 * @access private
 */
function _civicrm_relationship_check_params(&$params) {
  static $required = array(
    'contact_id_a' => NULL,
    'contact_id_b' => NULL,
    'relationship_type_id' => 'relationship_type',
  );

  // params should be an array
  if (!is_array($params)) {
    return civicrm_create_error('Input parameter is not an array');
  }
  // cannot create with empty params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }
  // check params for validity of Relationship id
  if (CRM_Utils_Array::value('id', $params)) {
    require_once 'CRM/Contact/BAO/Relationship.php';
    $relation = new CRM_Contact_BAO_Relationship();
    $relation->id = $params['id'];
    if (!$relation->find(TRUE)) {
      return civicrm_create_error('Relationship id is not valid');
    }
    else {
      if (($params['contact_id_a'] != $relation->contact_id_a) ||
        ($params['contact_id_b'] != $relation->contact_id_b)
      ) {
        return civicrm_create_error('Cannot change the contacts once relationship has been created');
      }
    }
  }

  $valid = TRUE;
  $error = '';
  foreach ($required as $field => $eitherField) {
    if (!CRM_Utils_Array::value($field, $params)) {
      if ($eitherField && CRM_Utils_Array::value($eitherField, $params)) {
        continue;
      }
      $valid = FALSE;
      $error .= " $field";
    }
  }

  if (!$valid) {
    return civicrm_create_error('Required fields not found' . $error);
  }

  return array();
}

