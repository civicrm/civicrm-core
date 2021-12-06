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
class CRM_Contact_BAO_RelationshipType extends CRM_Contact_DAO_RelationshipType implements \Civi\Test\HookInterface {

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
    $relationshipType = self::writeRecord($params);

    CRM_Core_PseudoConstant::relationshipType('label', TRUE);
    CRM_Core_PseudoConstant::relationshipType('name', TRUE);
    CRM_Core_PseudoConstant::flush();
    CRM_Case_XMLProcessor::flushStaticCaches();
    return $relationshipType;
  }

  /**
   * Delete Relationship Types.
   *
   * @param int $relationshipTypeId
   *
   * @deprecated
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
    return static::deleteRecord(['id' => $relationshipTypeId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      // need to delete all option value field before deleting group
      \Civi\Api4\Relationship::delete(FALSE)
        ->addWhere('relationship_type_id', '=', $event->id)
        ->execute();
    }
  }

}
