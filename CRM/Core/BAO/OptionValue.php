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
class CRM_Core_BAO_OptionValue extends CRM_Core_DAO_OptionValue {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Create option value - note that the create function calls 'add' but
   * has more business logic
   *
   * @param array $params input parameters
   *
   * @return object
   */
  static function create($params) {
    if (empty($params['id'])){
      self::setDefaults($params);
    }
    $ids = array();
    if (!empty($params['id'])) {
      $ids = array('optionValue' => $params['id']);
    }
    return  CRM_Core_BAO_OptionValue::add($params, $ids);
  }
  /**
   * Set default Parameters
   * This functions sets default parameters if not set:
   * - name & label are set to each other as required (it might make more sense for one
   * to be required but this would mean a change to the api level)
   * - weight & value will be set to their respective option groups next values
   * if nothing is passed in.
   *
   * Note this function does not check for presence of $params['id'] so should only be called
   * if 'id' is not present
   *
   * @param array $params
   */
  static function setDefaults(&$params){
    if(CRM_Utils_Array::value('label', $params, NULL) === NULL){
      $params['label'] = $params['name'];
    }
    if(CRM_Utils_Array::value('name', $params, NULL) === NULL){
      $params['name'] = $params['label'];
    }
    if(CRM_Utils_Array::value('weight', $params, NULL) === NULL){
      $params['weight'] = self::getDefaultWeight($params);
    }
    if (CRM_Utils_Array::value('value', $params, NULL) === NULL){
      $params['value'] = self::getDefaultValue($params);
    }
  }

  /**
   * Get next available value
   * We will take the highest numeric value (or 0 if no numeric values exist)
   * and add one. The calling function is responsible for any
   * more complex decision making
   *
   * @param array $params
   *
   * @return int
   */
  static function getDefaultWeight($params){
    return (int) CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
          array('option_group_id' => $params['option_group_id']));
  }

  /**
   * Get next available value
   * We will take the highest numeric value (or 0 if no numeric values exist)
   * and add one. The calling function is responsible for any
   * more complex decision making
   * @param array $params
   */
  static function getDefaultValue($params){
     $bao = new CRM_Core_BAO_OptionValue();
     $bao->option_group_id = $params['option_group_id'];
     if(isset($params['domain_id'])){
       $bao->domain_id = $params['domain_id'];
     }
     $bao->selectAdd();
     $bao->whereAdd("value REGEXP '^[0-9]+$'");
     $bao->selectAdd('(ROUND(COALESCE(MAX(CONVERT(value, UNSIGNED)),0)) +1) as nextvalue');
     $bao->find(TRUE);
     return $bao->nextvalue;
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
   * @return object CRM_Core_BAO_OptionValue object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);
    if ($optionValue->find(TRUE)) {
      CRM_Core_DAO::storeValues($optionValue, $defaults);
      return $optionValue;
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
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionValue', $id, 'is_active', $is_active);
  }

  /**
   * Function to add an Option Value
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return CRM_Core_DAO_OptionValue
   */
  static function add(&$params, &$ids) {
    // CRM-10921: do not reset attributes to default if this is an update
    //@todo consider if defaults are being set in the right place. 'dumb' defaults like
    // these would be usefully set @ the api layer so they are visible to api users
    // complex defaults like the domain id below would make sense in the setDefauls function
    // but unclear what other ways this function is being used
    if (empty($ids['optionValue'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
      $params['is_optgroup'] = CRM_Utils_Array::value('is_optgroup', $params, FALSE);
      $params['filter'] = CRM_Utils_Array::value('filter', $params, FALSE);
    }

    // action is taken depending upon the mode
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);

    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_option_value SET is_default = 0 WHERE  option_group_id = %1';

      // tweak default reset, and allow multiple default within group.
      if ($resetDefaultFor = CRM_Utils_Array::value('reset_default_for', $params)) {
        if (is_array($resetDefaultFor)) {
          $colName = key($resetDefaultFor);
          $colVal = $resetDefaultFor[$colName];
          $query .= " AND ( $colName IN (  $colVal ) )";
        }
      }

      $p = array(1 => array($params['option_group_id'], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $p);
    }

    // CRM-13814 : evalute option group id
    if (!array_key_exists('option_group_id', $params) && !empty($ids['optionValue'])) {
      $groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
        $ids['optionValue'], 'option_group_id', 'id'
      );
    }
    else {
      $groupId = $params['option_group_id'];
    }

    $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      $groupId, 'name', 'id'
    );
    if (in_array($groupName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $optionValue->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());
    }

    $optionValue->id = CRM_Utils_Array::value('optionValue', $ids);
    $optionValue->save();
    CRM_Core_PseudoConstant::flush();
    return $optionValue;
  }

  /**
   * Function to delete Option Value
   *
   * @param $optionValueId
   *
   * @internal param int $optionGroupId Id of the Option Group to be deleted.
   *
   * @return boolean
   *
   * @access public
   * @static
   */
  static function del($optionValueId) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $optionValueId;
    if (self::updateRecords($optionValueId, CRM_Core_Action::DELETE)) {
      CRM_Core_PseudoConstant::flush();
      return $optionValue->delete();
    }
    return FALSE;
  }

  /**
   * Function to retrieve activity type label and description
   *
   * @param int     $activityTypeId  activity type id
   *
   * @return array  label and description
   * @static
   * @access public
   */
  static function getActivityTypeDetails($activityTypeId) {
    $query = "SELECT civicrm_option_value.label, civicrm_option_value.description
   FROM civicrm_option_value
        LEFT JOIN civicrm_option_group ON ( civicrm_option_value.option_group_id = civicrm_option_group.id )
   WHERE civicrm_option_group.name = 'activity_type'
         AND civicrm_option_value.value =  {$activityTypeId} ";

    $dao = CRM_Core_DAO::executeQuery($query);

    $dao->fetch();

    return array($dao->label, $dao->description);
  }

  /**
   * Get the Option Value title.
   *
   * @param int $id id of Option Value
   *
   * @return string title
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $id, 'label');
  }

  /**
   * updates contacts affected by the option value passed.
   *
   * @param Integer $optionValueId     the option value id.
   * @param int     $action            the action describing whether prefix/suffix was UPDATED or DELETED
   *
   * @return bool
   */
  static function updateRecords(&$optionValueId, $action) {
    //finding group name
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $optionValueId;
    $optionValue->find(TRUE);

    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionValue->option_group_id;
    $optionGroup->find(TRUE);

    // group name
    $gName = $optionGroup->name;
    // value
    $value = $optionValue->value;

    // get the proper group name & affected field name
    // todo: this may no longer be needed for individuals - check inputs
    $individuals = array(
      'gender' => 'gender_id',
      'individual_prefix' => 'prefix_id',
      'individual_suffix' => 'suffix_id',
      'communication_style' => 'communication_style_id', // Not only Individuals -- but the code seems to be generic for all contact types, despite the naming...
    );
    $contributions = array('payment_instrument' => 'payment_instrument_id');
    $activities    = array('activity_type' => 'activity_type_id');
    $participant   = array('participant_role' => 'role_id');
    $eventType     = array('event_type' => 'event_type_id');
    $aclRole       = array('acl_role' => 'acl_role_id');

    $all = array_merge($individuals, $contributions, $activities, $participant, $eventType, $aclRole);
    $fieldName = '';

    foreach ($all as $name => $id) {
      if ($gName == $name) {
        $fieldName = $id;
      }
    }
    if ($fieldName == '') {
      return TRUE;
    }

    if (array_key_exists($gName, $individuals)) {
      $contactDAO = new CRM_Contact_DAO_Contact();

      $contactDAO->$fieldName = $value;
      $contactDAO->find();

      while ($contactDAO->fetch()) {
        if ($action == CRM_Core_Action::DELETE) {
          $contact = new CRM_Contact_DAO_Contact();
          $contact->id = $contactDAO->id;
          $contact->find(TRUE);

          // make sure dates doesn't get reset
          $contact->birth_date = CRM_Utils_Date::isoToMysql($contact->birth_date);
          $contact->deceased_date = CRM_Utils_Date::isoToMysql($contact->deceased_date);
          $contact->$fieldName = 'NULL';
          $contact->save();
        }
      }

      return TRUE;
    }

    if (array_key_exists($gName, $contributions)) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->$fieldName = $value;
      $contribution->find();
      while ($contribution->fetch()) {
        if ($action == CRM_Core_Action::DELETE) {
          $contribution->$fieldName = 'NULL';
          $contribution->save();
        }
      }
      return TRUE;
    }

    if (array_key_exists($gName, $activities)) {
      $activity = new CRM_Activity_DAO_Activity();
      $activity->$fieldName = $value;
      $activity->find();
      while ($activity->fetch()) {
        $activity->delete();
      }
      return TRUE;
    }

    //delete participant role, type and event type option value
    if (array_key_exists($gName, $participant)) {
      $participantValue = new CRM_Event_DAO_Participant();
      $participantValue->$fieldName = $value;
      if ($participantValue->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }

    //delete event type option value
    if (array_key_exists($gName, $eventType)) {
      $event = new CRM_Event_DAO_Event();
      $event->$fieldName = $value;
      if ($event->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }

    //delete acl_role option value
    if (array_key_exists($gName, $aclRole)) {
      $entityRole = new CRM_ACL_DAO_EntityRole();
      $entityRole->$fieldName = $value;

      $aclDAO = new CRM_ACL_DAO_ACL();
      $aclDAO->entity_id = $value;
      if ($entityRole->find(TRUE) || $aclDAO->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * updates options values weights.
   *
   * @param $opGroupId
   * @param array $opWeights options value , weight pair
   *
   * @internal param int $opGroupIde option group id.
   * @return void
   * @access public
   * @static
   */
  static function updateOptionWeights($opGroupId, $opWeights) {
    if (!is_array($opWeights) || empty($opWeights)) {
      return;
    }

    foreach ($opWeights as $opValue => $opWeight) {
      $optionValue = new CRM_Core_DAO_OptionValue();
      $optionValue->option_group_id = $opGroupId;
      $optionValue->value = $opValue;
      if ($optionValue->find(TRUE)) {
        $optionValue->weight = $opWeight;
        $optionValue->save();
      }
      $optionValue->free();
    }
  }

  /**
   * Get the values of all option values given an option group ID. Store in system cache
   * Does not take any filtering arguments. The object is to avoid hitting the DB and retrieve
   * from memory
   *
   * @param int $optionGroupID the option group for which we want the values from
   *
   * @return array an array of array of values for this option group
   * @static
   * @public
   */
  static function getOptionValuesArray($optionGroupID) {
    // check if we can get the field values from the system cache
    $cacheKey     = "CRM_Core_BAO_OptionValue_OptionGroupID_{$optionGroupID}";
    $cache        = CRM_Utils_Cache::singleton();
    $optionValues = $cache->get($cacheKey);
    if (empty($optionValues)) {
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->option_group_id = $optionGroupID;
      $dao->orderBy('weight ASC, label ASC');
      $dao->find();

      $optionValues = array();
      while ($dao->fetch()) {
        $optionValues[$dao->id] = array();
        CRM_Core_DAO::storeValues($dao, $optionValues[$dao->id]);
      }

      $cache->set($cacheKey, $optionValues);
    }

    return $optionValues;
  }

  /**
   * Get the values of all option values given an option group ID as a key => value pair
   * Use above cached function to make it super efficient
   *
   * @param int $optionGroupID the option group for which we want the values from
   *
   * @return array an associative array of label, value pairs
   * @static
   * @public
   */
  static function getOptionValuesAssocArray($optionGroupID) {
    $optionValues = self::getOptionValuesArray($optionGroupID);

    $options = array();
    foreach ($optionValues as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    return $options;
  }
  /**
   * Get the values of all option values given an option group Name as a key => value pair
   * Use above cached function to make it super efficient
   *
   * @param string $optionGroupName the option group name for which we want the values from
   *
   * @return array an associative array of label, value pairs
   * @static
   * @public
   */
  static function getOptionValuesAssocArrayFromName($optionGroupName) {
    $dao = new CRM_Core_DAO_OptionGroup();
    $dao->name = $optionGroupName;
    $dao->selectAdd();
    $dao->selectAdd('id');
    $dao->find(TRUE);
    $optionValues = self::getOptionValuesArray($dao->id);

    $options = array();
    foreach ($optionValues as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    return $options;
  }

}

