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
 */
class CRM_Contact_BAO_RelationshipType extends CRM_Contact_DAO_RelationshipType {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contact_BAO_RelationshipType
   */
  public static function retrieve(&$params, &$defaults) {
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
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_RelationshipType', $id, 'is_active', $is_active);
  }

  /**
   * Add the relationship type in the db.
   *
   * @param array $params
   *
   * @return CRM_Contact_DAO_RelationshipType
   */
  public static function add($params) {
    if (empty($params['id'])) {
      // Set name to label if not set
      if (empty($params['label_a_b']) && !empty($params['name_a_b'])) {
        $params['label_a_b'] = $params['name_a_b'];
      }
      if (empty($params['label_b_a']) && !empty($params['name_b_a'])) {
        $params['label_b_a'] = $params['name_b_a'];
      }

      // set label to name if it's not set
      if (empty($params['name_a_b']) && !empty($params['label_a_b'])) {
        $params['name_a_b'] = $params['label_a_b'];
      }
      if (empty($params['name_b_a']) && !empty($params['label_b_a'])) {
        $params['name_b_a'] = $params['label_b_a'];
      }
    }

    // action is taken depending upon the mode
    $relationshipType = new CRM_Contact_DAO_RelationshipType();

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'RelationshipType', CRM_Utils_Array::value('id', $params), $params);

    $relationshipType->copyValues($params);
    $relationshipType->save();

    CRM_Utils_Hook::post($hook, 'RelationshipType', $relationshipType->id, $relationshipType);

    CRM_Core_PseudoConstant::relationshipType('label', TRUE);
    CRM_Core_PseudoConstant::relationshipType('name', TRUE);
    CRM_Case_XMLProcessor::flushStaticCaches();
    return $relationshipType;
  }

  /**
   * Delete Relationship Types.
   *
   * @param int $relationshipTypeId
   *
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public static function del($relationshipTypeId) {
    // make sure relationshipTypeId is an integer
    // @todo review this as most delete functions rely on the api & form layer for this
    // or do a find first & throw error if no find
    if (!CRM_Utils_Rule::positiveInteger($relationshipTypeId)) {
      throw new CRM_Core_Exception(ts('Invalid relationship type'));
    }

    //check dependencies

    // delete all relationships
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->relationship_type_id = $relationshipTypeId;
    $relationship->delete();

    // remove this relationship type from membership types
    $mems = civicrm_api3('MembershipType', 'get', [
      'relationship_type_id' => ['LIKE' => "%{$relationshipTypeId}%"],
      'return' => ['id', 'relationship_type_id', 'relationship_direction'],
    ]);
    foreach ($mems['values'] as $membershipTypeId => $membershipType) {
      $pos = array_search($relationshipTypeId, $membershipType['relationship_type_id']);
      // Api call may have returned false positives but currently the relationship_type_id uses
      // nonstandard serialization which makes anything more accurate impossible.
      if ($pos !== FALSE) {
        unset($membershipType['relationship_type_id'][$pos], $membershipType['relationship_direction'][$pos]);
        civicrm_api3('MembershipType', 'create', $membershipType);
      }
    }

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
