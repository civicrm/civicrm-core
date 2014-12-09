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
class CRM_Contact_BAO_Relationship extends CRM_Contact_DAO_Relationship {

  /**
   * various constants to indicate different type of relationships
   *
   * @var int
   */
  CONST PAST = 1, DISABLED = 2, CURRENT = 4, INACTIVE = 8;

  /**
   * takes an associative array and creates a relationship object
   *
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   * per http://wiki.civicrm.org/confluence/display/CRM/Database+layer
   *  "we are moving away from the $ids param "
   *
   * @return object CRM_Contact_BAO_Relationship object
   * @access public
   * @static
   */
  static function create(&$params, $ids = array()) {
    $valid = $invalid = $duplicate = $saved = 0;
    $relationships = $relationshipIds = array();
    $relationshipId = CRM_Utils_Array::value('relationship', $ids, CRM_Utils_Array::value('id', $params));
    //CRM-9015 - the hooks are called here & in add (since add doesn't call create)
    // but in future should be tidied per ticket
    if(empty($relationshipId)){
      $hook = 'create';
      $action = CRM_Core_Action::ADD;
    }
    else{
      $hook = 'edit';
      $action = CRM_Core_Action::UPDATE;
    }

    CRM_Utils_Hook::pre($hook, 'Relationship', $relationshipId, $params);

    if (!$relationshipId) {
      // creating a new relationship
      $dataExists = self::dataExists($params);
      if (!$dataExists) {
        return array(FALSE, TRUE, FALSE, FALSE, NULL);
      }
      $relationshipIds = array();
      foreach ($params['contact_check'] as $key => $value) {
        $errors = '';
        // check if the relationship is valid between contacts.
        // step 1: check if the relationship is valid if not valid skip and keep the count
        // step 2: check the if two contacts already have a relationship if yes skip and keep the count
        // step 3: if valid relationship then add the relation and keep the count

        // step 1
        $errors = self::checkValidRelationship($params, $ids, $key);
        if ($errors) {
          $invalid++;
          continue;
        }

        if (
          self::checkDuplicateRelationship(
            $params,
            CRM_Utils_Array::value('contact', $ids),
            // step 2
            $key
          )
        ) {
          $duplicate++;
          continue;
        }

        $relationship = self::add($params, $ids, $key);
        $relationshipIds[] = $relationship->id;
        $relationships[$relationship->id] = $relationship;
        $valid++;
      }
      // editing the relationship
    }
    else {
      // check for duplicate relationship
      // @todo this code doesn't cope well with updates - causes e-Notices.
      // API has a lot of code to work around
      // this but should review this code & remove the extra handling from the api
      // it seems doubtful any of this is relevant if the contact fields & relationship
      // type fields are not set
      if (
        self::checkDuplicateRelationship(
          $params,
          CRM_Utils_Array::value('contact', $ids),
          $ids['contactTarget'],
          $relationshipId
        )
      ) {
        $duplicate++;
        return array($valid, $invalid, $duplicate, $saved, NULL);
      }

      $validContacts = TRUE;
      //validate contacts in update mode also.
      if (!empty($ids['contact']) && !empty($ids['contactTarget'])) {
        if (self::checkValidRelationship($params, $ids, $ids['contactTarget'])) {
          $validContacts = FALSE;
          $invalid++;
        }
      }
      if ($validContacts) {
        // editing an existing relationship
        $relationship = self::add($params, $ids, $ids['contactTarget']);
        $relationshipIds[] = $relationship->id;
        $relationships[$relationship->id] = $relationship;
        $saved++;
      }
    }

    // do not add to recent items for import, CRM-4399
    if (!(!empty($params['skipRecentView']) || $invalid || $duplicate)) {
      $url = CRM_Utils_System::url('civicrm/contact/view/rel',
        "action=view&reset=1&id={$relationship->id}&cid={$relationship->contact_id_a}&context=home"
      );


      $session = CRM_Core_Session::singleton();
      $recentOther = array();
      if (($session->get('userID') == $relationship->contact_id_a) ||
        CRM_Contact_BAO_Contact_Permission::allow($relationship->contact_id_a, CRM_Core_Permission::EDIT)
      ) {
        $rType = substr(CRM_Utils_Array::value('relationship_type_id', $params), -3);
        $recentOther = array(
          'editUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
            "action=update&reset=1&id={$relationship->id}&cid={$relationship->contact_id_a}&rtype={$rType}&context=home"
          ),
          'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
            "action=delete&reset=1&id={$relationship->id}&cid={$relationship->contact_id_a}&rtype={$rType}&context=home"
          ),
        );
      }

      $title = CRM_Contact_BAO_Contact::displayName($relationship->contact_id_a) . ' (' . CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
        $relationship->relationship_type_id, 'label_a_b'
      ) . ' ' . CRM_Contact_BAO_Contact::displayName($relationship->contact_id_b) . ')';
      // add the recently created Relationship
      CRM_Utils_Recent::add($title,
        $url,
        $relationship->id,
        'Relationship',
        $relationship->contact_id_a,
        NULL,
        $recentOther
      );
    }

    return array($valid, $invalid, $duplicate, $saved, $relationshipIds, $relationships);
  }

  /**
   * This is the function that check/add if the relationship created is valid
   *
   * @param array  $params      (reference ) an assoc array of name/value pairs
   * @param integer $contactId  this is contact id for adding relationship
   * @param array $ids          the array that holds all the db ids
   *
   * @return object CRM_Contact_BAO_Relationship
   * @access public
   * @static
   */
  static function add(&$params, $ids = array(), $contactId = NULL) {
    $relationshipId =
      CRM_Utils_Array::value('relationship', $ids, CRM_Utils_Array::value('id', $params));

    $hook = 'create';
    if($relationshipId) {
      $hook = 'edit';
    }
    //@todo hook are called from create and add - remove one
    CRM_Utils_Hook::pre($hook , 'Relationship', $relationshipId, $params);

    $relationshipTypes = CRM_Utils_Array::value('relationship_type_id', $params);

    // explode the string with _ to get the relationship type id
    // and to know which contact has to be inserted in
    // contact_id_a and which one in contact_id_b
    list($type, $first, $second) = explode('_', $relationshipTypes);

    ${'contact_' . $first} = CRM_Utils_Array::value('contact', $ids);
    ${'contact_' . $second} = $contactId;

    // check if the relationship type is Head of Household then update the
    // household's primary contact with this contact.
    if ($type == 6) {
      CRM_Contact_BAO_Household::updatePrimaryContact($contact_b, $contact_a);
    }

    $relationship = new CRM_Contact_BAO_Relationship();
    //@todo this code needs to be updated for the possibility that not all fields are set
    // (update)
    $relationship->contact_id_b = $contact_b;
    $relationship->contact_id_a = $contact_a;
    $relationship->relationship_type_id = $type;
    $relationship->id = $relationshipId;

    $dateFields = array('end_date', 'start_date');

    foreach (self::getdefaults() as $defaultField => $defaultValue){
      if(isset($params[$defaultField])){
        if(in_array($defaultField, $dateFields)){
          $relationship->$defaultField = CRM_Utils_Date::format(CRM_Utils_Array::value($defaultField, $params));
          if(!$relationship->$defaultField){
            $relationship->$defaultField = 'NULL';
          }
        }
        else{
          $relationship->$defaultField = $params[$defaultField];
        }
      }
      elseif(!$relationshipId){
        $relationship->$defaultField = $defaultValue;
      }
    }

    $relationship->save();

    // add custom field values
    if (!empty($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_relationship', $relationship->id);
    }

    $relationship->free();

    CRM_Utils_Hook::post($hook, 'Relationship', $relationship->id, $relationship);

    return $relationship;
  }
  /**
   * Specifiy defaults for creating a relationship
   *
   * @return array $defaults array of defaults for creating relationship
   * @access public
   * @static
   */
  static function getdefaults() {
    return array(
      'is_active' => 0,
      'is_permission_a_b' => 0,
      'is_permission_b_a' => 0,
      'description' => '',
      'start_date' => 'NULL',
      'case_id' => NULL,
      'end_date' => 'NULL',
    );
  }


  /**
   * Check if there is data to create the object
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return boolean
   * @access public
   * @static
   */
  static function dataExists(&$params) {
    // return if no data present
    if (!is_array(CRM_Utils_Array::value('contact_check', $params))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Function to get get list of relationship type based on the contact type.
   *
   * @param int $contactId this is the contact id of the current contact.
   * @param null $contactSuffix
   * @param string $relationshipId the id of the existing relationship if any
   * @param string $contactType contact type
   * @param boolean $all if true returns relationship types in both the direction
   * @param string $column name/label that going to retrieve from db.
   *
   *
   * @param bool $biDirectional
   * @param string $contactSubType includes relationshiptypes between this subtype
   *
   * @param boolean $onlySubTypeRelationTypes if set only subtype which is passed by $contactSubType
   *                                          related relationshiptypes get return
   *
   * @internal param string $strContact this value is currently ignored, keeping it there for legacy reasons
   * @access public
   * @static
   *
   * @return array - array reference of all relationship types with context to current contact.
   */
  static function getContactRelationshipType($contactId = NULL,
    $contactSuffix = NULL,
    $relationshipId = NULL,
    $contactType = NULL,
    $all = FALSE,
    $column = 'label',
    $biDirectional = TRUE,
    $contactSubType = NULL,
    $onlySubTypeRelationTypes = FALSE
  ) {
    $allRelationshipType = array();
    $relationshipType    = array();
    $allRelationshipType = CRM_Core_PseudoConstant::relationshipType($column);

    $otherContactType = NULL;
    if ($relationshipId) {
      $relationship = new CRM_Contact_DAO_Relationship();
      $relationship->id = $relationshipId;
      if ($relationship->find(TRUE)) {
        $contact = new CRM_Contact_DAO_Contact();
        $contact->id = ($relationship->contact_id_a === $contactId) ? $relationship->contact_id_b : $relationship->contact_id_a;

        if ($contact->find(TRUE)) {
          $otherContactType = $contact->contact_type;
          //CRM-5125 for contact subtype specific relationshiptypes
          if ($contact->contact_sub_type) {
            $otherContactSubType = $contact->contact_sub_type;
          }
        }
      }
    }

    $contactSubType = array();
    if ($contactId) {
      $contactType = CRM_Contact_BAO_Contact::getContactType($contactId);
      $contactSubType = CRM_Contact_BAO_Contact::getContactSubType($contactId);
    }

    foreach ($allRelationshipType as $key => $value) {
      // the contact type is required or matches
      if (((!$value['contact_type_a']) ||
          $value['contact_type_a'] == $contactType
        ) &&
        // the other contact type is required or present or matches
        ((!$value['contact_type_b']) ||
          (!$otherContactType) ||
          $value['contact_type_b'] == $otherContactType
        ) &&
        (!$contactSubType ||
          (in_array($value['contact_sub_type_a'], $contactSubType) ||
            in_array($value['contact_sub_type_b'], $contactSubType) ||
            ((!$value['contact_sub_type_b'] &&
                !$value['contact_sub_type_a']
              ) &&
              !$onlySubTypeRelationTypes
            )
          )
        )
      ) {
        $relationshipType[$key . '_a_b'] = $value["{$column}_a_b"];
      }

      if (((!$value['contact_type_b']) ||
          $value['contact_type_b'] == $contactType
        ) &&
        ((!$value['contact_type_a']) ||
          (!$otherContactType) ||
          $value['contact_type_a'] == $otherContactType
        ) &&
        (!$contactSubType ||
          (in_array($value['contact_sub_type_b'], $contactSubType) ||
            in_array($value['contact_sub_type_a'], $contactSubType) ||
            ((!$value['contact_sub_type_a'] &&
                !$value['contact_sub_type_b']
              ) &&
              !$onlySubTypeRelationTypes
            )
          )
        )
      ) {
        $relationshipType[$key . '_b_a'] = $value["{$column}_b_a"];
      }

      if ($all) {
        $relationshipType[$key . '_a_b'] = $value["{$column}_a_b"];
        $relationshipType[$key . '_b_a'] = $value["{$column}_b_a"];
      }
    }

    if ($biDirectional) {
      // lets clean up the data and eliminate all duplicate values
      // (i.e. the relationship is bi-directional)
      $relationshipType = array_unique($relationshipType);
    }

    // sort the relationshipType in ascending order CRM-7736
    asort($relationshipType);
    return $relationshipType;
  }

  /**
   * @param $id
   * @param $action
   *
   * @return CRM_Contact_DAO_Relationship
   */
  static function clearCurrentEmployer($id, $action) {
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->id = $id;
    $relationship->find(TRUE);

    //to delete relationship between household and individual                                                                                          \
    //or between individual and orgnization
    if (($action & CRM_Core_Action::DISABLE) || ($action & CRM_Core_Action::DELETE)) {
      $relTypes = CRM_Utils_Array::index(array('name_a_b'), CRM_Core_PseudoConstant::relationshipType('name'));
      if ($relationship->relationship_type_id == $relTypes['Employee of']['id'] ||
          $relationship->relationship_type_id == $relTypes['Household Member of']['id']) {
        $sharedContact = new CRM_Contact_DAO_Contact();
        $sharedContact->id = $relationship->contact_id_a;
        $sharedContact->find(TRUE);

          if ($relationship->relationship_type_id == 4 && $relationship->contact_id_b == $sharedContact->employer_id) {
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($relationship->contact_id_a);
        }
      }
    }
    return  $relationship;
  }

  /**
   * Function to delete the relationship
   *
   * @param int $id relationship id
   *
   * @return null
   * @access public
   *
   * @static
   */
  static function del($id) {
    // delete from relationship table
    CRM_Utils_Hook::pre('delete', 'Relationship', $id, CRM_Core_DAO::$_nullArray);

    $relationship = self::clearCurrentEmployer($id, CRM_Core_Action::DELETE);
    if (CRM_Core_Permission::access('CiviMember')) {
      // create $params array which isrequired to delete memberships
      // of the related contacts.
      $params = array(
        'relationship_type_id' => "{$relationship->relationship_type_id}_a_b",
        'contact_check' => array($relationship->contact_id_b => 1),
      );

      $ids = array();
      // calling relatedMemberships to delete the memberships of
      // related contacts.
      self::relatedMemberships($relationship->contact_id_a,
        $params,
        $ids,
        CRM_Core_Action::DELETE,
        FALSE
      );
    }

    $relationship->delete();
    CRM_Core_Session::setStatus(ts('Selected relationship has been deleted successfully.'), ts('Record Deleted'), 'success');

    CRM_Utils_Hook::post('delete', 'Relationship', $id, $relationship);

    // delete the recently created Relationship
    $relationshipRecent = array(
      'id' => $id,
      'type' => 'Relationship',
    );
    CRM_Utils_Recent::del($relationshipRecent);

    return $relationship;
  }

  /**
   * Function to disable/enable the relationship
   *
   * @param int $id relationship id
   *
   * @param $action
   *
   * @return null
   * @access public
   * @static
   */
  static function disableEnableRelationship($id, $action) {
    $relationship = self::clearCurrentEmployer($id, $action);
    if (CRM_Core_Permission::access('CiviMember')) {
      // create $params array which isrequired to delete memberships
      // of the related contacts.
      $params = array(
        'relationship_type_id' => "{$relationship->relationship_type_id}_a_b",
        'contact_check' => array($relationship->contact_id_b => 1),
      );

      $ids = array();
      // calling relatedMemberships to delete/add the memberships of
      // related contacts.
      if ($action & CRM_Core_Action::DISABLE) {
        CRM_Contact_BAO_Relationship::relatedMemberships($relationship->contact_id_a,
          $params,
          $ids,
          CRM_Core_Action::DELETE,
          FALSE
        );
      }
      elseif ($action & CRM_Core_Action::ENABLE) {
        $ids['contact'] = $relationship->contact_id_a;
        CRM_Contact_BAO_Relationship::relatedMemberships($relationship->contact_id_a,
          $params,
          $ids,
          CRM_Core_Action::ADD,
          FALSE
        );
      }
    }
  }

  /**
   * Delete the object records that are associated with this contact
   *
   * @param  int  $contactId id of the contact to delete
   *
   * @return void
   * @access public
   * @static
   */
  static function deleteContact($contactId) {
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->contact_id_a = $contactId;
    $relationship->delete();

    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->contact_id_b = $contactId;
    $relationship->delete();

    CRM_Contact_BAO_Household::updatePrimaryContact(NULL, $contactId);
  }

  /**
   * Function to get the other contact in a relationship
   *
   * @param int $id relationship id
   *
   * $returns  returns the contact ids in the realtionship
   *
   * @return \CRM_Contact_DAO_Relationship
   * @access public
   * @static
   */
  static function getContactIds($id) {
    $relationship = new CRM_Contact_DAO_Relationship();

    $relationship->id = $id;
    $relationship->selectAdd();
    $relationship->selectAdd('contact_id_a, contact_id_b');
    $relationship->find(TRUE);

    return $relationship;
  }

  /**
   * Function to check if the relationship type selected between two contacts is correct
   *
   * @param int $contact_a 1st contact id
   * @param int $contact_b 2nd contact id
   * @param int $relationshipTypeId relationship type id
   *
   * @return boolean  true if it is valid relationship else false
   * @access public
   * @static
   */
  static function checkRelationshipType($contact_a, $contact_b, $relationshipTypeId) {
    $relationshipType = new CRM_Contact_DAO_RelationshipType();
    $relationshipType->id = $relationshipTypeId;
    $relationshipType->selectAdd();
    $relationshipType->selectAdd('contact_type_a, contact_type_b, contact_sub_type_a, contact_sub_type_b');
    if ($relationshipType->find(TRUE)) {
      $contact_type_a = CRM_Contact_BAO_Contact::getContactType($contact_a);
      $contact_type_b = CRM_Contact_BAO_Contact::getContactType($contact_b);

      $contact_sub_type_a = CRM_Contact_BAO_Contact::getContactSubType($contact_a);
      $contact_sub_type_b = CRM_Contact_BAO_Contact::getContactSubType($contact_b);

      if (((!$relationshipType->contact_type_a) || ($relationshipType->contact_type_a == $contact_type_a)) &&
        ((!$relationshipType->contact_type_b) || ($relationshipType->contact_type_b == $contact_type_b)) &&
        ((!$relationshipType->contact_sub_type_a) || (in_array($relationshipType->contact_sub_type_a,
              $contact_sub_type_a
            ))) &&
        ((!$relationshipType->contact_sub_type_b) || (in_array($relationshipType->contact_sub_type_b,
              $contact_sub_type_b
            )))
      ) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * this function does the validtion for valid relationship
   *
   * @param array $params this array contains the values there are subitted by the form
   * @param array $ids the array that holds all the db ids
   * @param integer $contactId this is contact id for adding relationship
   *
   * @return string
  @access public
   * @static
   */
  static function checkValidRelationship(&$params, &$ids, $contactId) {
    $errors = '';

    // get the string of relationship type
    $relationshipTypes = CRM_Utils_Array::value('relationship_type_id', $params);
    list($type, $first, $second) = explode('_', $relationshipTypes);
    ${'contact_' . $first} = CRM_Utils_Array::value('contact', $ids);
    ${'contact_' . $second} = $contactId;
    // function to check if the relationship selected is correct
    // i.e. employer relationship can exit between Individual and Organization (not between Individual and Individual)
    if (!CRM_Contact_BAO_Relationship::checkRelationshipType($contact_a, $contact_b, $type)) {
      $errors = 'Please select valid relationship between these two contacts.';
    }
    return $errors;
  }

  /**
   * this function checks for duplicate relationship
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param integer $id this the id of the contact whom we are adding relationship
   * @param integer $contactId  this is contact id for adding relationship
   * @param integer $relationshipId this is relationship id for the contact
   *
   * @return boolean true if record exists else false
   * @access public
   * @static
   */
  static function checkDuplicateRelationship(&$params, $id, $contactId = 0, $relationshipId = 0) {
    $relationshipTypeId = CRM_Utils_Array::value('relationship_type_id', $params);
    list($type, $first, $second) = explode('_', $relationshipTypeId);

    $queryString = "
SELECT id
FROM   civicrm_relationship
WHERE  relationship_type_id = " . CRM_Utils_Type::escape($type, 'Integer');

    /*
    * CRM-11792 - date fields from API are in ISO format, but this function
    * supports date arrays BAO has increasingly standardised to ISO format
    * so I believe this function should support ISO rather than make API
    * format it - however, need to support array format for now to avoid breakage
    * @ time of writing this function is called from Relationship::create (twice)
    * CRM_BAO_Contact_Utils::clearCurrentEmployer (seemingly without dates)
    * CRM_Contact_Form_Task_AddToOrganization::postProcess &
    * CRM_Contact_Form_Task_AddToHousehold::postProcess
    * (I don't think the last 2 support dates but not sure
    */

    $dateFields = array('end_date', 'start_date');
    foreach ($dateFields as $dateField){
      if(array_key_exists($dateField, $params)) {
        if (empty($params[$dateField]) || $params[$dateField] == 'null'){
          //this is most likely coming from an api call & probably loaded
          // from the DB to deal with some of the
          // other myriad of excessive checks still in place both in
          // the api & the create functions
          $queryString .= " AND $dateField IS NULL";
          continue;
        }
        elseif (is_array($params[$dateField])){
          $queryString .= " AND $dateField = " .
            CRM_Utils_Type::escape(CRM_Utils_Date::format($params[$dateField]), 'Date');
        }
        else {
          $queryString .= " AND $dateField = " .
            CRM_Utils_Type::escape($params[$dateField], 'Date');
        }
      }
    }

    $queryString .=
      " AND ( ( contact_id_a = " . CRM_Utils_Type::escape($id, 'Integer') .
      " AND contact_id_b = " . CRM_Utils_Type::escape($contactId, 'Integer') .
      " ) OR ( contact_id_a = " . CRM_Utils_Type::escape($contactId, 'Integer') .
      " AND contact_id_b = " . CRM_Utils_Type::escape($id, 'Integer') . " ) ) ";

    //if caseId is provided, include it duplicate checking.
    if ($caseId = CRM_Utils_Array::value('case_id', $params)) {
      $queryString .= " AND case_id = " . CRM_Utils_Type::escape($caseId, 'Integer');
    }

    if ($relationshipId) {
      $queryString .= " AND id !=" . CRM_Utils_Type::escape($relationshipId, 'Integer');
    }

    $relationship = new CRM_Contact_BAO_Relationship();
    $relationship->query($queryString);
    $relationship->fetch();
    $relationship->free();
    return ($relationship->id) ? TRUE : FALSE;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int $id id of the database record
   * @param boolean $is_active value we want to set the is_active field
   *
   * @throws CiviCRM_API3_Exception
   * @return Object             DAO object on success, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    // as both the create & add functions have a bunch of logic in them that
    // doesn't seem to cope with a normal update we will call the api which
    // has tested handling for this
    // however, a longer term solution would be to simplify the add, create & api functions
    // to be more standard. It is debatable @ that point whether it's better to call the BAO
    // direct as the api is more tested.
    $result = civicrm_api('relationship', 'create', array(
      'id' => $id,
      'is_active' => $is_active,
      'version' => 3,
    ));

    if (is_array($result) && !empty($result['is_error']) && $result['error_message'] != 'Relationship already exists') {
      throw new CiviCRM_API3_Exception($result['error_message'], CRM_Utils_Array::value('error_code', $result, 'undefined'), $result);
    }

    // call (undocumented possibly deprecated) hook
    CRM_Utils_Hook::enableDisable('CRM_Contact_BAO_Relationship', $id, $is_active);

    return TRUE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   * @internal param array $ids the array that holds all the db ids
   *
   * @return array (reference)   the values that could be potentially assigned to smarty
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values) {
    if (empty($params)) {
      return NULL;
    }
    $v = array();

    // get the specific number of relationship or all relationships.
    if (!empty($params['numRelationship'])) {
      $v['data'] = &CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'], NULL, $params['numRelationship']);
    }
    else {
      $v['data'] = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id']);
    }

    // get the total count of relationships
    $v['totalCount'] = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'], NULL, NULL, TRUE);

    $values['relationship']['data'] = &$v['data'];
    $values['relationship']['totalCount'] = &$v['totalCount'];

    return $v;
  }

  /**
   * helper function to form the sql for relationship retrieval
   *
   * @param int $contactId contact id
   * @param int $status (check const at top of file)
   * @param int $numRelationship no of relationships to display (limit)
   * @param int $count get the no of relationships
   * $param int $relationshipId relationship id
   * @param $relationshipId
   * @param string $direction the direction we are interested in a_b or b_a
   * @param array $params array of extra values including relationship_type_id per api spec
   *
   * return string the query for this diretion
   *
   * @return array
   * @static
   * @access public
   */
  static function makeURLClause($contactId, $status, $numRelationship, $count, $relationshipId, $direction, $params = array()) {
    $select = $from = $where = '';

    $select = '( ';
    if ($count) {
      if ($direction == 'a_b') {
        $select .= ' SELECT count(DISTINCT civicrm_relationship.id) as cnt1, 0 as cnt2 ';
      }
      else {
        $select .= ' SELECT 0 as cnt1, count(DISTINCT civicrm_relationship.id) as cnt2 ';
      }
    }
    else {
      $select .= ' SELECT civicrm_relationship.id as civicrm_relationship_id,
                              civicrm_contact.sort_name as sort_name,
                              civicrm_contact.display_name as display_name,
                              civicrm_contact.job_title as job_title,
                              civicrm_contact.employer_id as employer_id,
                              civicrm_contact.organization_name as organization_name,
                              civicrm_address.street_address as street_address,
                              civicrm_address.city as city,
                              civicrm_address.postal_code as postal_code,
                              civicrm_state_province.abbreviation as state,
                              civicrm_country.name as country,
                              civicrm_email.email as email,
                              civicrm_contact.contact_type as contact_type,
                              civicrm_phone.phone as phone,
                              civicrm_contact.id as civicrm_contact_id,
                              civicrm_relationship.contact_id_b as contact_id_b,
                              civicrm_relationship.contact_id_a as contact_id_a,
                              civicrm_relationship_type.id as civicrm_relationship_type_id,
                              civicrm_relationship.start_date as start_date,
                              civicrm_relationship.end_date as end_date,
                              civicrm_relationship.description as description,
                              civicrm_relationship.is_active as is_active,
                              civicrm_relationship.is_permission_a_b as is_permission_a_b,
                              civicrm_relationship.is_permission_b_a as is_permission_b_a,
                              civicrm_relationship.case_id as case_id';

      if ($direction == 'a_b') {
        $select .= ', civicrm_relationship_type.label_a_b as label_a_b,
                              civicrm_relationship_type.label_b_a as relation ';
      }
      else {
        $select .= ', civicrm_relationship_type.label_a_b as label_a_b,
                              civicrm_relationship_type.label_a_b as relation ';
      }
    }

    $from = "
      FROM  civicrm_relationship
INNER JOIN  civicrm_relationship_type ON ( civicrm_relationship.relationship_type_id = civicrm_relationship_type.id )
INNER JOIN  civicrm_contact ";
    if ($direction == 'a_b') {
      $from .= 'ON ( civicrm_contact.id = civicrm_relationship.contact_id_a ) ';
    }
    else {
      $from .= 'ON ( civicrm_contact.id = civicrm_relationship.contact_id_b ) ';
    }
    $from .= "
LEFT JOIN  civicrm_address ON (civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1)
LEFT JOIN  civicrm_phone   ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary = 1)
LEFT JOIN  civicrm_email   ON (civicrm_email.contact_id = civicrm_contact.id AND civicrm_email.is_primary = 1)
LEFT JOIN  civicrm_state_province ON (civicrm_address.state_province_id = civicrm_state_province.id)
LEFT JOIN  civicrm_country ON (civicrm_address.country_id = civicrm_country.id)
";
    $where = 'WHERE ( 1 )';
    if ($contactId) {
      if ($direction == 'a_b') {
        $where .= ' AND civicrm_relationship.contact_id_b = ' . CRM_Utils_Type::escape($contactId, 'Positive');
      }
      else {
        $where .= ' AND civicrm_relationship.contact_id_a = ' . CRM_Utils_Type::escape($contactId, 'Positive') . '
                    AND civicrm_relationship.contact_id_a != civicrm_relationship.contact_id_b ';
      }
    }
    if ($relationshipId) {
      $where .= ' AND civicrm_relationship.id = ' . CRM_Utils_Type::escape($relationshipId, 'Positive');
    }

    $date = date('Y-m-d');
    if ($status == self::PAST) {
      //this case for showing past relationship
      $where .= ' AND civicrm_relationship.is_active = 1 ';
      $where .= " AND civicrm_relationship.end_date < '" . $date . "'";
    }
    elseif ($status == self::DISABLED) {
      // this case for showing disabled relationship
      $where .= ' AND civicrm_relationship.is_active = 0 ';
    }
    elseif ($status == self::CURRENT) {
      //this case for showing current relationship
      $where .= ' AND civicrm_relationship.is_active = 1 ';
      $where .= " AND (civicrm_relationship.end_date >= '" . $date . "' OR civicrm_relationship.end_date IS NULL) ";
    }
    elseif ($status == self::INACTIVE) {
      //this case for showing inactive relationships
      $where .= " AND (civicrm_relationship.end_date < '" . $date . "'";
      $where .= ' OR civicrm_relationship.is_active = 0 )';
    }

    // CRM-6181
    $where .= ' AND civicrm_contact.is_deleted = 0';
    if(!empty($params['membership_type_id']) && empty($params['relationship_type_id'])) {
      $where .= self::membershipTypeToRelationshipTypes($params, $direction);
    }
    if(!empty($params['relationship_type_id'])) {
      if(is_array($params['relationship_type_id'])) {
        $where .=  " AND " . CRM_Core_DAO::createSQLFilter('relationship_type_id', $params['relationship_type_id'], 'Integer');
      }
      else {
        $where .= ' AND relationship_type_id = ' . CRM_Utils_Type::escape($params['relationship_type_id'], 'Positive');
      }
    }
    if ($direction == 'a_b') {
      $where .= ' ) UNION ';
    }
    else {
      $where .= ' ) ';
    }

    return array($select, $from, $where);
  }

  /**
   * This is the function to get the list of relationships
   *
   * @param int $contactId contact id
   * @param int $status 1: Past 2: Disabled 3: Current
   * @param int $numRelationship no of relationships to display (limit)
   * @param int $count get the no of relationships
   * $param int $relationshipId relationship id
   * $param array $links the list of links to display
   * $param int   $permissionMask  the permission mask to be applied for the actions
   * $param boolean $permissionedContact to return only permissioned Contact
   * $param array $params array of variables consistent with filters supported by the api
   * return array $values relationship records
   * @param int $relationshipId
   * @param null $links
   * @param null $permissionMask
   * @param bool $permissionedContact
   * @param array $params
   *
   * @return array|int
   * @static
   * @access public
   */
  static function getRelationship($contactId = NULL,
    $status              = 0, $numRelationship = 0,
    $count               = 0, $relationshipId = 0,
    $links               = NULL, $permissionMask = NULL,
    $permissionedContact = FALSE,
    $params = array()
  ) {
    $values = array();
    if (!$contactId && !$relationshipId) {
      return $values;
    }

    list($select1, $from1, $where1) = self::makeURLClause($contactId, $status, $numRelationship,
      $count, $relationshipId, 'a_b', $params
    );
    list($select2, $from2, $where2) = self::makeURLClause($contactId, $status, $numRelationship,
      $count, $relationshipId, 'b_a', $params
    );

    $order = $limit = '';
    if (!$count) {
      if (empty($params['sort'])) {
        $order = ' ORDER BY civicrm_relationship_type_id, sort_name ';
      }
      else {
        $order = " ORDER BY {$params['sort']} ";
      }

      $offset = 0;
      if (!empty($params['offset']) && $params['offset'] > 0) {
        $offset = $params['offset'];
      }

      if ($numRelationship) {
        $limit = " LIMIT {$offset}, $numRelationship";
      }
    }

    // building the query string
    $queryString = $select1 . $from1 . $where1 . $select2 . $from2 . $where2 . $order . $limit;

    $relationship = new CRM_Contact_DAO_Relationship();

    $relationship->query($queryString);
    $row = array();
    if ($count) {
      $relationshipCount = 0;
      while ($relationship->fetch()) {
        $relationshipCount += $relationship->cnt1 + $relationship->cnt2;
      }
      return $relationshipCount;
    }
    else {

      $mask = NULL;
      if ($status != self::INACTIVE) {
        if ($links) {
          $mask = array_sum(array_keys($links));
          if ($mask & CRM_Core_Action::DISABLE) {
            $mask -= CRM_Core_Action::DISABLE;
          }
          if ($mask & CRM_Core_Action::ENABLE) {
            $mask -= CRM_Core_Action::ENABLE;
          }

          if ($status == self::CURRENT) {
            $mask |= CRM_Core_Action::DISABLE;
          }
          elseif ($status == self::DISABLED) {
            $mask |= CRM_Core_Action::ENABLE;
          }
          $mask = $mask & $permissionMask;
        }
      }
      while ($relationship->fetch()) {
        $rid = $relationship->civicrm_relationship_id;
        $cid = $relationship->civicrm_contact_id;
        if (($permissionedContact) &&
          (!CRM_Contact_BAO_Contact_Permission::relationship($cid, $contactId))
        ) {
          continue;
        }
        $values[$rid]['id'] = $rid;
        $values[$rid]['cid'] = $cid;
        $values[$rid]['contact_id_a'] = $relationship->contact_id_a;
        $values[$rid]['contact_id_b'] = $relationship->contact_id_b;
        $values[$rid]['contact_type'] = $relationship->contact_type;
        $values[$rid]['relationship_type_id'] = $relationship->civicrm_relationship_type_id;
        $values[$rid]['relation'] = $relationship->relation;
        $values[$rid]['name'] = $relationship->sort_name;
        $values[$rid]['display_name'] = $relationship->display_name;
        $values[$rid]['job_title'] = $relationship->job_title;
        $values[$rid]['email'] = $relationship->email;
        $values[$rid]['phone'] = $relationship->phone;
        $values[$rid]['employer_id'] = $relationship->employer_id;
        $values[$rid]['organization_name'] = $relationship->organization_name;
        $values[$rid]['country'] = $relationship->country;
        $values[$rid]['city'] = $relationship->city;
        $values[$rid]['state'] = $relationship->state;
        $values[$rid]['start_date'] = $relationship->start_date;
        $values[$rid]['end_date'] = $relationship->end_date;
        $values[$rid]['description'] = $relationship->description;
        $values[$rid]['is_active'] = $relationship->is_active;
        $values[$rid]['is_permission_a_b'] = $relationship->is_permission_a_b;
        $values[$rid]['is_permission_b_a'] = $relationship->is_permission_b_a;
        $values[$rid]['case_id'] = $relationship->case_id;

        if ($status) {
          $values[$rid]['status'] = $status;
        }

        $values[$rid]['civicrm_relationship_type_id'] = $relationship->civicrm_relationship_type_id;

        if ($relationship->contact_id_a == $contactId) {
          $values[$rid]['rtype'] = 'a_b';
        }
        else {
          $values[$rid]['rtype'] = 'b_a';
        }

        if ($links) {
          $replace = array(
            'id' => $rid,
            'rtype' => $values[$rid]['rtype'],
            'cid' => $contactId,
            'cbid' => $values[$rid]['cid'],
            'caseid' => $values[$rid]['case_id'],
            'clientid' => $contactId,
          );

          if ($status == self::INACTIVE) {
            // setting links for inactive relationships
            $mask = array_sum(array_keys($links));
            if (!$values[$rid]['is_active']) {
              $mask -= CRM_Core_Action::DISABLE;
            }
            else {
              $mask -= CRM_Core_Action::ENABLE;
              $mask -= CRM_Core_Action::DISABLE;
            }
          }

          // Give access to manage case link by copying to MAX_ACTION index temporarily, depending on case permission of user.
          if ($values[$rid]['case_id']) {
            // Borrowed logic from CRM_Case_Page_Tab
            $hasCaseAccess = FALSE;
            if (CRM_Core_Permission::check('access all cases and activities')) {
              $hasCaseAccess = TRUE;
            }
            else {
              $userCases = CRM_Case_BAO_Case::getCases(FALSE);
              if (array_key_exists($values[$rid]['case_id'], $userCases)) {
                $hasCaseAccess = TRUE;
              }
            }

            if ($hasCaseAccess) {
              // give access by copying to MAX_ACTION temporarily, otherwise leave at NONE which won't display
              $links[CRM_Core_Action::MAX_ACTION] = $links[CRM_Core_Action::NONE];
              $links[CRM_Core_Action::MAX_ACTION]['name'] = ts('Manage Case #%1', array(1 => $values[$rid]['case_id']));
              $links[CRM_Core_Action::MAX_ACTION]['class'] = 'no-popup';

              // Also make sure we have the right client cid since can get here from multiple relationship tabs.
              if ($values[$rid]['rtype'] == 'b_a') {
                $replace['clientid'] = $values[$rid]['cid'];
              }
            }
          }

          $values[$rid]['action'] = CRM_Core_Action::formLink(
            $links,
            $mask,
            $replace,
            ts('more'),
            FALSE,
            'relationship.selector.row',
            'Relationship',
            $rid);
          unset($links[CRM_Core_Action::MAX_ACTION]);
        }
      }

      $relationship->free();
      return $values;
    }
  }

  /**
   * Function to get get list of relationship type based on the target contact type.
   *
   * @param string $targetContactType it's valid contact tpye(may be Individual , Organization , Household)
   *
   * @return array - array reference of all relationship types with context to current contact type .
   *
   */
  function getRelationType($targetContactType) {
    $relationshipType = array();
    $allRelationshipType = CRM_Core_PseudoConstant::relationshipType();

    foreach ($allRelationshipType as $key => $type) {
      if ($type['contact_type_b'] == $targetContactType) {
        $relationshipType[$key . '_a_b'] = $type['label_a_b'];
      }
    }

    return $relationshipType;
  }

  /**
   * Function to create / update / delete membership for related contacts.
   *
   * This function will create/update/delete membership for related
   * contact based on 1) contact have active membership 2) that
   * membership is is extedned by the same relationship type to that
   * of the existing relationship.
   *
   * @param $contactId  Int     contact id
   * @param $params     array   array of values submitted by POST
   * @param $ids        array   array of ids
   * @param \const|\which $action which action called this function
   *
   * @param bool $active
   *
   * @static
   */
  static function relatedMemberships($contactId, &$params, $ids, $action = CRM_Core_Action::ADD, $active = TRUE) {
    // Check the end date and set the status of the relationship
    // accrodingly.
    $status = self::CURRENT;

    if (!empty($params['end_date'])) {
      $endDate = CRM_Utils_Date::setDateDefaults(CRM_Utils_Date::format($params['end_date']), NULL, 'Ymd');
      $today = date('Ymd');

      if ($today > $endDate) {
        $status = self::PAST;
      }
    }

    if (($action & CRM_Core_Action::ADD) &&
      ($status & self::PAST)
    ) {
      // if relationship is PAST and action is ADD, no qustion
      // of creating RELATED membership and return back to
      // calling method
      return;
    }

    $rel = explode('_', $params['relationship_type_id']);

    $relTypeId     = $rel[0];
    $relDirection  = "_{$rel[1]}_{$rel[2]}";
    $targetContact = array();
    if (($action & CRM_Core_Action::ADD) ||
      ($action & CRM_Core_Action::DELETE)
    ) {
      $contact = $contactId;
      $targetContact = CRM_Utils_Array::value('contact_check', $params);
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      $contact = $ids['contact'];
      $targetContact = array($ids['contactTarget'] => 1);
    }

    // Build the 'values' array for
    // 1. ContactA
    // 2. ContactB
    // This will allow us to check if either of the contacts in
    // relationship have active memberships.

    $values = array();

    // 1. ContactA
    $values[$contact] = array(
      'relatedContacts' => $targetContact,
      'relationshipTypeId' => $relTypeId,
      'relationshipTypeDirection' => $relDirection,
    );
    // 2. ContactB
    if (!empty($targetContact)) {
      foreach ($targetContact as $cid => $donCare) {
        $values[$cid] = array(
          'relatedContacts' => array($contact => 1),
          'relationshipTypeId' => $relTypeId,
        );

        $relTypeParams = array('id' => $relTypeId);
        $relTypeValues = array();
        CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $relTypeValues);

        if (CRM_Utils_Array::value('name_a_b', $relTypeValues) == CRM_Utils_Array::value('name_b_a', $relTypeValues)) {
          $values[$cid]['relationshipTypeDirection'] = '_a_b';
        }
        else {
          $values[$cid]['relationshipTypeDirection'] = ($relDirection == '_a_b') ? '_b_a' : '_a_b';
        }
      }
    }

    // Now get the active memberships for all the contacts.
    // If contact have any valid membership(s), then add it to
    // 'values' array.
    foreach ($values as $cid => $subValues) {
      $memParams = array('contact_id' => $cid);
      $memberships = array();

      CRM_Member_BAO_Membership::getValues($memParams, $memberships, $active);

      if (empty($memberships)) {
        continue;
      }

      $values[$cid]['memberships'] = $memberships;
    }
    $deceasedStatusId = array_search('Deceased', CRM_Member_PseudoConstant::membershipStatus());

    // done with 'values' array.
    // Finally add / edit / delete memberships for the related contacts
    foreach ($values as $cid => $details) {
      if (!array_key_exists('memberships', $details)) {
        continue;
      }

      $mainRelatedContactId = key(CRM_Utils_Array::value('relatedContacts', $details, array()));

      foreach ($details['memberships'] as $membershipId => $membershipValues) {
        $relTypeIds = array();
        if ($action & CRM_Core_Action::DELETE) {
          // Delete memberships of the related contacts only if relationship type exists for membership type
          $query = "
SELECT relationship_type_id, relationship_direction
  FROM civicrm_membership_type
 WHERE id = {$membershipValues['membership_type_id']}";
          $dao = CRM_Core_DAO::executeQuery($query);
          $relTypeDirs = array();
          while ($dao->fetch()) {
            $relTypeId = $dao->relationship_type_id;
            $relDirection = $dao->relationship_direction;
          }
          $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $relTypeId);
          if (in_array($values[$cid]['relationshipTypeId'], $relTypeIds)) {
            CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId, $mainRelatedContactId);
          }
          continue;
        }
        if (($action & CRM_Core_Action::UPDATE) &&
          ($status & self::PAST) &&
          ($membershipValues['owner_membership_id'])
        ) {
          // If relationship is PAST and action is UPDATE
          // then delete the RELATED membership
          CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipValues['owner_membership_id'],
            $membershipValues['membership_contact_id']
          );
          continue;
        }

        // add / edit the memberships for related
        // contacts.

        // Get the Membership Type Details.
        $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipValues['membership_type_id']);
        // Check if contact's relationship type exists in membership type
        $relTypeDirs = array();
        if (!empty($membershipType['relationship_type_id'])) {
          $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_type_id']);
        }
        if (!empty($membershipType['relationship_direction'])) {
          $relDirections = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_direction']);
        }
        foreach ($relTypeIds as $key => $value) {
          $relTypeDirs[] = $value . '_' . $relDirections[$key];
        }
        $relTypeDir = $details['relationshipTypeId'] . $details['relationshipTypeDirection'];
        if (in_array($relTypeDir, $relTypeDirs)) {
          // Check if relationship being created/updated is
          // similar to that of membership type's
          // relationship.

          $membershipValues['owner_membership_id'] = $membershipId;
          unset($membershipValues['id']);
          unset($membershipValues['membership_contact_id']);
          unset($membershipValues['contact_id']);
          unset($membershipValues['membership_id']);
          foreach ($details['relatedContacts'] as $relatedContactId => $donCare) {
            $membershipValues['contact_id'] = $relatedContactId;
            if ($deceasedStatusId &&
              CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $relatedContactId, 'is_deceased')
            ) {
              $membershipValues['status_id'] = $deceasedStatusId;
              $membershipValues['skipStatusCal'] = TRUE;
            }
            foreach (array(
              'join_date', 'start_date', 'end_date') as $dateField) {
              if (!empty($membershipValues[$dateField])) {
                $membershipValues[$dateField] = CRM_Utils_Date::processDate($membershipValues[$dateField]);
              }
            }

            if ($action & CRM_Core_Action::UPDATE) {
              //delete the membership record for related
              //contact before creating new membership record.
              CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId, $relatedContactId);
            }

            // check whether we have some related memberships still available
            $query = "
SELECT count(*)
  FROM civicrm_membership
    LEFT JOIN civicrm_membership_status ON (civicrm_membership_status.id = civicrm_membership.status_id)
 WHERE membership_type_id = {$membershipValues['membership_type_id']} AND owner_membership_id = {$membershipValues['owner_membership_id']}
    AND is_current_member = 1";
            $result = CRM_Core_DAO::singleValueQuery($query);
            if ($result < CRM_Utils_Array::value('max_related', $membershipValues, PHP_INT_MAX)) {
              CRM_Member_BAO_Membership::create($membershipValues, CRM_Core_DAO::$_nullArray);
            }
          }
        }
        elseif ($action & CRM_Core_Action::UPDATE) {
          // if action is update and updated relationship do
          // not match with the existing
          // membership=>relationship then we need to
          // delete the membership record created for
          // previous relationship.

          if (self::isDeleteRelatedMembership($relTypeIds, $contactId, $mainRelatedContactId, $relTypeId, CRM_Utils_Array::value('relationship_ids', $params))) {
            CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId, $mainRelatedContactId);
          }
        }
      }
    }
  }

  /**
   * Helper function to check whether to delete the membership or
   * not.
   *
   * @static
   *
   */
  static function isDeleteRelatedMembership($relTypeIds, $contactId, $mainRelatedContactId, $relTypeId, $relIds) {
    if (in_array($relTypeId, $relTypeIds)) {
      return TRUE;
    }

    if (empty($relIds)) {
      return FALSE;
    }

    $relParamas = array(1 => array($contactId, 'Integer'),
      2 => array($mainRelatedContactId, 'Integer'),
    );

    if ($contactId == $mainRelatedContactId) {
      $recordsFound = (int)CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $relTypeIds) . " )  AND contact_id_a IN ( %1 ) OR contact_id_b IN ( %1 ) AND id IN (" . implode(',', $relIds) . ")", $relParamas);
      if ($recordsFound) {
        return FALSE;
      }
      return TRUE;
    }

    $recordsFound = (int)CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $relTypeIds) . " ) AND contact_id_a IN ( %1, %2 ) AND contact_id_b IN ( %1, %2 ) AND id NOT IN (" . implode(',', $relIds) . ")", $relParamas);

    if ($recordsFound) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Function to get Current Employer for Contact
   *
   * @param $contactIds       Contact Ids
   *
   * @return array $currentEmployer array of the current employer@static
   */
  static function getCurrentEmployer($contactIds) {
    $contacts = implode(',', $contactIds);

    $query = "
SELECT organization_name, id, employer_id
FROM civicrm_contact
WHERE id IN ( {$contacts} )
";

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $currentEmployer = array();
    while ($dao->fetch()) {
      $currentEmployer[$dao->id]['org_id'] = $dao->employer_id;
      $currentEmployer[$dao->id]['org_name'] = $dao->organization_name;
    }

    return $currentEmployer;
  }

  /**
   * Function to return list of permissioned employer for a given contact.
   *
   * @param $contactID   int     contact id whose employers
   * are to be found.
   * @param $name        string  employers sort name
   *
   * @static
   *
   * @return array array of employers.
   *
   */
  static function getPermissionedEmployer($contactID, $name = NULL) {
    //get the relationship id
    $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType',
      'Employee of', 'id', 'name_a_b'
    );

    return self::getPermissionedContacts($contactID, $relTypeId, $name);
  }


 /**
  * Function to return list of permissioned contacts for a given contact and relationship type
  *
  * @param $contactID int contact id whose permissioned contacts are to be found.
  * @param $relTypeId string one or more relationship type id's
  * @param $name string
  *
  * @static
  *
  * @return array of contacts
  */
  static function getPermissionedContacts($contactID, $relTypeId, $name = NULL) {
    $contacts = array();

    if ($relTypeId) {
      $query = "
SELECT cc.id as id, cc.sort_name as name
FROM civicrm_relationship cr, civicrm_contact cc
WHERE
cr.contact_id_a         = %1 AND
cr.relationship_type_id IN (%2) AND
cr.is_permission_a_b    = 1 AND
IF(cr.end_date IS NULL, 1, (DATEDIFF( CURDATE( ), cr.end_date ) <= 0)) AND
cr.is_active = 1 AND
cc.id = cr.contact_id_b";

      if (!empty($name)) {
        $name   = CRM_Utils_Type::escape($name, 'String');
        $query .= "
AND cc.sort_name LIKE '%$name%'";
      }

      $args = array(1 => array($contactID, 'Integer'), 2 => array($relTypeId, 'String'));
      $dao  = CRM_Core_DAO::executeQuery($query, $args);

      while ($dao->fetch()) {
        $contacts[$dao->id] = array(
          'name' => $dao->name,
          'value' => $dao->id,
        );
      }
    }
    return $contacts;
  }

  /**
   * Merge relationships from otherContact to mainContact
   * Called during contact merge operation
   *
   * @param int $mainId contact id of main contact record.
   * @param int $otherId contact id of record which is going to merge.
   * @param array $sqls (reference) array of sql statements to append to.
   *
   * @see CRM_Dedupe_Merger::cpTables()
   *
   * @static
   */
  static function mergeRelationships($mainId, $otherId, &$sqls) {
    // Delete circular relationships
    $sqls[] = "DELETE FROM civicrm_relationship
      WHERE (contact_id_a = $mainId AND contact_id_b = $otherId)
         OR (contact_id_b = $mainId AND contact_id_a = $otherId)";

    // Delete relationship from other contact if main contact already has that relationship
    $sqls[] = "DELETE r2
      FROM civicrm_relationship r1, civicrm_relationship r2
      WHERE r1.relationship_type_id = r2.relationship_type_id
      AND r1.id <> r2.id
      AND (
        r1.contact_id_a = $mainId AND r2.contact_id_a = $otherId AND r1.contact_id_b = r2.contact_id_b
        OR r1.contact_id_b = $mainId AND r2.contact_id_b = $otherId AND r1.contact_id_a = r2.contact_id_a
        OR (
          (r1.contact_id_a = $mainId AND r2.contact_id_b = $otherId AND r1.contact_id_b = r2.contact_id_a
          OR r1.contact_id_b = $mainId AND r2.contact_id_a = $otherId AND r1.contact_id_a = r2.contact_id_b)
          AND r1.relationship_type_id IN (SELECT id FROM civicrm_relationship_type WHERE name_b_a = name_a_b)
        )
      )";

    // Move relationships
    $sqls[] = "UPDATE IGNORE civicrm_relationship SET contact_id_a = $mainId WHERE contact_id_a = $otherId";
    $sqls[] = "UPDATE IGNORE civicrm_relationship SET contact_id_b = $mainId WHERE contact_id_b = $otherId";

    // Move current employer id (name will get updated later)
    $sqls[] = "UPDATE civicrm_contact SET employer_id = $mainId WHERE employer_id = $otherId";
  }

  /**
   * Set 'is_valid' field to false for all relationships whose end date is in the past, ie. are expired.
   *
   * @return True on success, false if error is encountered.
   */
  static function disableExpiredRelationships() {
    $query = "SELECT id FROM civicrm_relationship WHERE is_active = 1 AND end_date < CURDATE()";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $result = CRM_Contact_BAO_Relationship::setIsActive($dao->id, FALSE);
      // Result will be NULL if error occurred. We abort early if error detected.
      if ($result == NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Function filters the query by possible relationships for the membership type
   * It is intended to be called when constructing queries for the api (reciprocal & non-reciprocal)
   * and to add clauses to limit the return to those relationships which COULD inherit a membership type
   * (as opposed to those who inherit a particular membership
   *
   * @param array $params api input array
   * @param null $direction
   *
   * @return array
   */
  static function membershipTypeToRelationshipTypes(&$params, $direction = NULL) {
    $membershipType = civicrm_api3('membership_type', 'getsingle', array('id' => $params['membership_type_id'], 'return' => 'relationship_type_id, relationship_direction'));
    $relationshipTypes = $membershipType['relationship_type_id'];
    if(empty($relationshipTypes)) {
      return;
    }
    // if we don't have any contact data we can only filter on type
    if(empty($params['contact_id']) && empty($params['contact_id_a']) && empty($params['contact_id_a'])) {
      $params['relationship_type_id'] = array('IN' => $relationshipTypes);
      return;
    }
    else {
      $relationshipDirections = (array) $membershipType['relationship_direction'];
      // if we have contact_id_a OR contact_id_b we can make a call here
      // if we have contact??
      foreach ($relationshipDirections as $index => $mtdirection) {
        if(isset($params['contact_id_a']) && $mtdirection == 'a_b'  || $direction == 'a_b') {
          $types[] = $relationshipTypes[$index];
        }
        if(isset($params['contact_id_b']) && $mtdirection == 'b_a'  || $direction == 'b_a') {
          $types[] = $relationshipTypes[$index];
        }
      }
      if(!empty($types)) {
        $params['relationship_type_id'] = array('IN' => $types);
      }
      elseif(!empty($clauses)) {
        return explode(' OR ', $clauses);
      }
      else{
        // effectively setting it to return no results
        $params['relationship_type_id'] = 0;
      }
    }
  }


  /**
   * This function is a wrapper for contact relationship selector
   *
   * @param  array   $params associated array for params record id.
   *
   * @return array   $contactRelationships associated array of contact relationships
   * @access public
   */
  public static function getContactRelationshipSelector(&$params) {
    // format the params
    $params['offset']   = ($params['page'] - 1) * $params['rp'];
    $params['sort']     = CRM_Utils_Array::value('sortBy', $params);

    if ($params['context'] == 'past') {
      $relationshipStatus = CRM_Contact_BAO_Relationship::INACTIVE;
    }
    else {
      $relationshipStatus = CRM_Contact_BAO_Relationship::CURRENT;
    }

    // check logged in user for permission
    $page = new CRM_Core_Page();
    CRM_Contact_Page_View::checkUserPermission($page, $params['contact_id']);
    $permissions = array($page->_permission);
    if ($page->_permission == CRM_Core_Permission::EDIT) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    if ($params['context'] != 'user') {
      $links = CRM_Contact_Page_View_Relationship::links();
      $permissionedContacts = FALSE;
    }
    else {
      $links = CRM_Contact_Page_View_UserDashBoard::links();
      $permissionedContacts = TRUE;
      $mask = NULL;
    }
    // get contact relationships
    $relationships = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
      $relationshipStatus,
      $params['rp'], 0, 0,
      $links, $mask,
      $permissionedContacts,
      $params
    );

    $contactRelationships = array();
    $params['total'] = 0;
    if (!empty($relationships)) {
      // get the total relationships
      if ($params['context'] != 'user') {
        $params['total'] = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
        $relationshipStatus, 0, 1, 0, NULL, NULL, $permissionedContacts);
      }
      else {
        // FIX ME: we cannot directly determine total permissioned relationship, hence re-fire query
        $permissionedRelationships = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
          $relationshipStatus,
          0, 0, 0,
          NULL, NULL, TRUE
        );
        $params['total'] = count($permissionedRelationships);
      }

      // format params
      foreach ($relationships as $relationshipId => $values) {
        //Add image icon for related contacts: CRM-14919
        $icon = CRM_Contact_BAO_Contact_Utils::getImage($values['contact_type'],
          FALSE,
          $values['cid']
        );
        $contactRelationships[$relationshipId]['name'] = $icon.' '.CRM_Utils_System::href(
          $values['name'],
          'civicrm/contact/view',
          "reset=1&cid={$values['cid']}");

        $contactRelationships[$relationshipId]['relation'] = CRM_Utils_System::href(
          $values['relation'],
          'civicrm/contact/view/rel',
          "action=view&reset=1&cid={$values['cid']}&id={$values['id']}&rtype={$values['rtype']}");

        if ($params['context'] == 'current') {
          if (($params['contact_id'] == $values['contact_id_a'] AND $values['is_permission_a_b'] == 1) OR
            ($params['contact_id'] == $values['contact_id_b'] AND $values['is_permission_b_a'] == 1)
          ) {
            $contactRelationships[$relationshipId]['name'] .= '<span id="permission-a-b" class="crm-marker permission-relationship"> *</span>';
          }

          if (($values['cid'] == $values['contact_id_a'] AND $values['is_permission_a_b'] == 1) OR
            ($values['cid'] == $values['contact_id_b'] AND $values['is_permission_b_a'] == 1)
          ) {
            $contactRelationships[$relationshipId]['relation'] .= '<span id="permission-b-a" class="crm-marker permission-relationship"> *</span>';
          }
        }

        if (!empty($values['description'])) {
          $contactRelationships[$relationshipId]['relation'] .= "<p class='description'>{$values['description']}</p>";
        }

        $contactRelationships[$relationshipId]['start_date'] = CRM_Utils_Date::customFormat($values['start_date']);
        $contactRelationships[$relationshipId]['end_date'] = CRM_Utils_Date::customFormat($values['end_date']);
        $contactRelationships[$relationshipId]['city'] = $values['city'];
        $contactRelationships[$relationshipId]['state'] = $values['state'];
        $contactRelationships[$relationshipId]['email'] = $values['email'];
        $contactRelationships[$relationshipId]['phone'] = $values['phone'];
        $contactRelationships[$relationshipId]['links'] = $values['action'];
        $contactRelationships[$relationshipId]['id'] = $values['id'];
        $contactRelationships[$relationshipId]['is_active'] = $values['is_active'];
      }
    }
    return $contactRelationships;
  }

}

