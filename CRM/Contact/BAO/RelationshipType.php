<?php
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_BAO_RelationshipType extends CRM_Contact_DAO_RelationshipType {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contact_BAO_RelationshipType object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $relationshipType = new CRM_Contact_DAO_RelationshipType();
    $relationshipType->copyValues($params);
    if ($relationshipType->find(TRUE)) {
      CRM_Core_DAO::storeValues($relationshipType, $defaults);
      $relationshipType->free();
      return $relationshipType;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_RelationshipType', $id, 'is_active', $is_active);
  }

  /**
   * Function to add the relationship type in the db
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Contact_DAO_RelationshipType
   * @access public
   * @static
   *
   */
  static function add(&$params, &$ids) {
    //to change name, CRM-3336
    if (!CRM_Utils_Array::value('label_a_b', $params) && CRM_Utils_Array::value('name_a_b', $params)) {
      $params['label_a_b'] = $params['name_a_b'];
    }

    if (!CRM_Utils_Array::value('label_b_a', $params) && CRM_Utils_Array::value('name_b_a', $params)) {
      $params['label_b_a'] = $params['name_b_a'];
    }

    // set label to name if it's not set - but *only* for
    // ADD action. CRM-3336 as part from (CRM-3522)
    if (!CRM_Utils_Array::value('relationshipType', $ids)) {
      if (!CRM_Utils_Array::value('name_a_b', $params) && CRM_Utils_Array::value('label_a_b', $params)) {
        $params['name_a_b'] = $params['label_a_b'];
      }
      if (!CRM_Utils_Array::value('name_b_a', $params) && CRM_Utils_Array::value('label_b_a', $params)) {
        $params['name_b_a'] = $params['label_b_a'];
      }
    }

    // action is taken depending upon the mode
    $relationshipType = new CRM_Contact_DAO_RelationshipType();

    $relationshipType->copyValues($params);

    // if label B to A is blank, insert the value label A to B for it
    if (!strlen(trim($strName = CRM_Utils_Array::value('name_b_a', $params)))) {
      $relationshipType->name_b_a = CRM_Utils_Array::value('name_a_b', $params);
    }
    if (!strlen(trim($strName = CRM_Utils_Array::value('label_b_a', $params)))) {
      $relationshipType->label_b_a = CRM_Utils_Array::value('label_a_b', $params);
    }

    $relationshipType->id = CRM_Utils_Array::value('relationshipType', $ids);

    return $relationshipType->save();
  }

  /**
   * Function to delete Relationship Types
   *
   * @param int $relationshipTypeId
   * @static
   */
  static function del($relationshipTypeId) {
    // make sure relationshipTypeId is an integer
    if (!CRM_Utils_Rule::positiveInteger($relationshipTypeId)) {
      CRM_Core_Error::fatal(ts('Invalid relationship type'));
    }


    //check dependencies

    // delete all relationships
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->relationship_type_id = $relationshipTypeId;
    $relationship->delete();

    // set all membership_type to null
    $query = "
UPDATE civicrm_membership_type
  SET  relationship_type_id = NULL
 WHERE relationship_type_id = %1
";
    $params = array(1 => array($relationshipTypeId, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);

    //fixed for CRM-3323
    $mappingField = new CRM_Core_DAO_MappingField();
    $mappingField->relationship_type_id = $relationshipTypeId;
    $mappingField->find();
    while ($mappingField->fetch()) {
      $mappingField->delete();
    }

    $relationshipType = new CRM_Contact_DAO_RelationshipType();
    $relationshipType->id = $relationshipTypeId;
    return $relationshipType->delete();
  }
}

