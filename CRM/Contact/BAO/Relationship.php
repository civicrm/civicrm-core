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
 * Class CRM_Contact_BAO_Relationship.
 */
class CRM_Contact_BAO_Relationship extends CRM_Contact_DAO_Relationship {

  /**
   * Various constants to indicate different type of relationships.
   *
   * @var int
   */
  const ALL = 0, PAST = 1, DISABLED = 2, CURRENT = 4, INACTIVE = 8;

  /**
   * Constants for is_permission fields.
   * Note: the slightly non-obvious ordering is due to history...
   */
  const NONE = 0, EDIT = 1, VIEW = 2;

  /**
   * Create function - use the API instead.
   *
   * Note that the previous create function has been renamed 'legacyCreateMultiple'
   * and this is new in 4.6
   * All existing calls have been changed to legacyCreateMultiple except the api call - however, it is recommended
   * that you call that as the end to end testing here is based on the api & refactoring may still be done.
   *
   * @param array $params
   *
   * @return \CRM_Contact_BAO_Relationship
   * @throws \CRM_Core_Exception
   */
  public static function create(&$params) {

    $extendedParams = self::loadExistingRelationshipDetails($params);
    // When id is specified we always wan't to update, so we don't need to
    // check for duplicate relations.
    if (!isset($params['id']) && self::checkDuplicateRelationship($extendedParams, $extendedParams['contact_id_a'], $extendedParams['contact_id_b'], CRM_Utils_Array::value('id', $extendedParams, 0))) {
      throw new CRM_Core_Exception('Duplicate Relationship');
    }
    $params = $extendedParams;
    if (self::checkValidRelationship($params, $params, 0)) {
      throw new CRM_Core_Exception('Invalid Relationship');
    }
    $relationship = self::add($params);
    if (!empty($params['contact_id_a'])) {
      $ids = array(
        'contactTarget' => $relationship->contact_id_b,
        'contact' => $params['contact_id_a'],
      );

      //CRM-16087 removed additional call to function relatedMemberships which is already called by disableEnableRelationship
      //resulting in membership being created twice
      if (array_key_exists('is_active', $params) && empty($params['is_active'])) {
        $action = CRM_Core_Action::DISABLE;
        $active = FALSE;
      }
      else {
        $action = CRM_Core_Action::ENABLE;
        $active = TRUE;
      }
      $id = empty($params['id']) ? $relationship->id : $params['id'];
      self::disableEnableRelationship($id, $action, $params, $ids, $active);
    }

    if (empty($params['skipRecentView'])) {
      self::addRecent($params, $relationship);
    }

    return $relationship;
  }

  /**
   * Create multiple relationships for one contact.
   *
   * The relationship details are the same for each relationship except the secondary contact
   * id can be an array.
   *
   * @param array $params
   *   Parameters for creating multiple relationships.
   *   The parameters are the same as for relationship create function except that the non-primary
   *   end of the relationship should be an array of one or more contact IDs.
   * @param string $primaryContactLetter
   *   a or b to denote the primary contact for this action. The secondary may be multiple contacts
   *   and should be an array.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function createMultiple($params, $primaryContactLetter) {
    $secondaryContactLetter = ($primaryContactLetter == 'a') ? 'b' : 'a';
    $secondaryContactIDs = $params['contact_id_' . $secondaryContactLetter];
    $valid = $invalid = $duplicate = $saved = 0;
    $relationshipIDs = array();
    foreach ($secondaryContactIDs as $secondaryContactID) {
      try {
        $params['contact_id_' . $secondaryContactLetter] = $secondaryContactID;
        $relationship = civicrm_api3('relationship', 'create', $params);
        $relationshipIDs[] = $relationship['id'];
        $valid++;
      }
      catch (CiviCRM_API3_Exception $e) {
        switch ($e->getMessage()) {
          case 'Duplicate Relationship':
            $duplicate++;
            break;

          case 'Invalid Relationship':
            $invalid++;
            break;

          default:
            throw new CRM_Core_Exception('unknown relationship create error ' . $e->getMessage());
        }
      }
    }

    return array(
      'valid' => $valid,
      'invalid' => $invalid,
      'duplicate' => $duplicate,
      'saved' => $saved,
      'relationship_ids' => $relationshipIDs,
    );
  }

  /**
   * Takes an associative array and creates a relationship object.
   * @deprecated For single creates use the api instead (it's tested).
   * For multiple a new variant of this function needs to be written and migrated to as this is a bit
   * nasty
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   The array that holds all the db ids.
   *   per http://wiki.civicrm.org/confluence/display/CRM/Database+layer
   *  "we are moving away from the $ids param "
   *
   * @return CRM_Contact_BAO_Relationship
   */
  public static function legacyCreateMultiple(&$params, $ids = array()) {
    $valid = $invalid = $duplicate = $saved = 0;
    $relationships = $relationshipIds = array();
    $relationshipId = CRM_Utils_Array::value('relationship', $ids, CRM_Utils_Array::value('id', $params));

    //CRM-9015 - the hooks are called here & in add (since add doesn't call create)
    // but in future should be tidied per ticket
    if (empty($relationshipId)) {
      $hook = 'create';
    }
    else {
      $hook = 'edit';
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
        // check if the relationship is valid between contacts.
        // step 1: check if the relationship is valid if not valid skip and keep the count
        // step 2: check the if two contacts already have a relationship if yes skip and keep the count
        // step 3: if valid relationship then add the relation and keep the count

        // step 1
        $contactFields = self::setContactABFromIDs($params, $ids, $key);
        $errors = self::checkValidRelationship($contactFields, $ids, $key);
        if ($errors) {
          $invalid++;
          continue;
        }

        //CRM-16978:check duplicate relationship as per case id.
        if ($caseId = CRM_Utils_Array::value('case_id', $params)) {
          $contactFields['case_id'] = $caseId;
        }
        if (
        self::checkDuplicateRelationship(
          $contactFields,
          CRM_Utils_Array::value('contact', $ids),
          // step 2
          $key
        )
        ) {
          $duplicate++;
          continue;
        }

        $singleInstanceParams = array_merge($params, $contactFields);
        $relationship = self::add($singleInstanceParams);
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
      $contactFields = self::setContactABFromIDs($params, $ids, $ids['contactTarget']);
      if (!empty($ids['contact']) && !empty($ids['contactTarget'])) {
        if (self::checkValidRelationship($contactFields, $ids, $ids['contactTarget'])) {
          $validContacts = FALSE;
          $invalid++;
        }
      }
      if ($validContacts) {
        // editing an existing relationship
        $singleInstanceParams = array_merge($params, $contactFields);
        $relationship = self::add($singleInstanceParams, $ids, $ids['contactTarget']);
        $relationshipIds[] = $relationship->id;
        $relationships[$relationship->id] = $relationship;
        $saved++;
      }
    }

    // do not add to recent items for import, CRM-4399
    if (!(!empty($params['skipRecentView']) || $invalid || $duplicate)) {
      self::addRecent($params, $relationship);
    }

    return array($valid, $invalid, $duplicate, $saved, $relationshipIds, $relationships);
  }

  /**
   * This is the function that check/add if the relationship created is valid.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   The array that holds all the db ids.
   * @param int $contactId
   *   This is contact id for adding relationship.
   *
   * @return CRM_Contact_BAO_Relationship
   */
  public static function add(&$params, $ids = array(), $contactId = NULL) {
    $relationshipId = CRM_Utils_Array::value('relationship', $ids, CRM_Utils_Array::value('id', $params));

    $hook = 'create';
    if ($relationshipId) {
      $hook = 'edit';
    }
    //@todo hook are called from create and add - remove one
    CRM_Utils_Hook::pre($hook, 'Relationship', $relationshipId, $params);

    $relationshipTypes = CRM_Utils_Array::value('relationship_type_id', $params);

    // explode the string with _ to get the relationship type id
    // and to know which contact has to be inserted in
    // contact_id_a and which one in contact_id_b
    list($type) = explode('_', $relationshipTypes);

    // check if the relationship type is Head of Household then update the
    // household's primary contact with this contact.
    if ($type == 6) {
      CRM_Contact_BAO_Household::updatePrimaryContact($params['contact_id_b'], $params['contact_id_a']);
    }
    if (!empty($relationshipId) && self::isCurrentEmployerNeedingToBeCleared($params, $relationshipId, $type)) {
      CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($params['contact_id_a']);
    }
    $relationship = new CRM_Contact_BAO_Relationship();
    //@todo this code needs to be updated for the possibility that not all fields are set
    // by using $relationship->copyValues($params);
    // (update)
    $relationship->contact_id_b = $params['contact_id_b'];
    $relationship->contact_id_a = $params['contact_id_a'];
    $relationship->relationship_type_id = $type;
    $relationship->id = $relationshipId;

    $dateFields = array('end_date', 'start_date');

    foreach (self::getdefaults() as $defaultField => $defaultValue) {
      if (isset($params[$defaultField])) {
        if (in_array($defaultField, $dateFields)) {
          $relationship->$defaultField = CRM_Utils_Date::format(CRM_Utils_Array::value($defaultField, $params));
          if (!$relationship->$defaultField) {
            $relationship->$defaultField = 'NULL';
          }
        }
        else {
          $relationship->$defaultField = $params[$defaultField];
        }
      }
      elseif (!$relationshipId) {
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
   * Add relationship to recent links.
   *
   * @param array $params
   * @param CRM_Contact_DAO_Relationship $relationship
   */
  public static function addRecent($params, $relationship) {
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

    CRM_Utils_Recent::add($title,
      $url,
      $relationship->id,
      'Relationship',
      $relationship->contact_id_a,
      NULL,
      $recentOther
    );
  }

  /**
   * Load contact ids and relationship type id when doing a create call if not provided.
   *
   * There are are various checks done in create which require this information which is optional
   * when using id.
   *
   * @param array $params
   *   Parameters passed to create call.
   *
   * @return array
   *   Parameters with missing fields added if required.
   */
  public static function loadExistingRelationshipDetails($params) {
    if (!empty($params['contact_id_a'])
      && !empty($params['contact_id_b'])
      && is_numeric($params['relationship_type_id'])) {
      return $params;
    }
    if (empty($params['id'])) {
      return $params;
    }

    $fieldsToFill = array('contact_id_a', 'contact_id_b', 'relationship_type_id');
    $result = CRM_Core_DAO::executeQuery("SELECT " . implode(',', $fieldsToFill) . " FROM civicrm_relationship WHERE id = %1", array(
      1 => array(
        $params['id'],
        'Integer',
      ),
    ));
    while ($result->fetch()) {
      foreach ($fieldsToFill as $field) {
        $params[$field] = !empty($params[$field]) ? $params[$field] : $result->$field;
      }
    }
    return $params;
  }

  /**
   * Resolve passed in contact IDs to contact_id_a & contact_id_b.
   *
   * @param array $params
   * @param array $ids
   * @param null $contactID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function setContactABFromIDs($params, $ids = array(), $contactID = NULL) {
    $returnFields = array();

    // $ids['contact'] is deprecated but comes from legacyCreateMultiple function.
    if (empty($ids['contact'])) {
      if (!empty($params['id'])) {
        return self::loadExistingRelationshipDetails($params);
      }
      throw new CRM_Core_Exception('Cannot create relationship, insufficient contact IDs provided');
    }
    if (isset($params['relationship_type_id']) && !is_numeric($params['relationship_type_id'])) {
      $relationshipTypes = CRM_Utils_Array::value('relationship_type_id', $params);
      list($relationshipTypeID, $first) = explode('_', $relationshipTypes);
      $returnFields['relationship_type_id'] = $relationshipTypeID;

      foreach (array('a', 'b') as $contactLetter) {
        if (empty($params['contact_' . $contactLetter])) {
          if ($first == $contactLetter) {
            $returnFields['contact_id_' . $contactLetter] = CRM_Utils_Array::value('contact', $ids);
          }
          else {
            $returnFields['contact_id_' . $contactLetter] = $contactID;
          }
        }
      }
    }

    return $returnFields;
  }

  /**
   * Specify defaults for creating a relationship.
   *
   * @return array
   *   array of defaults for creating relationship
   */
  public static function getdefaults() {
    return array(
      'is_active' => 0,
      'is_permission_a_b' => self::NONE,
      'is_permission_b_a' => self::NONE,
      'description' => '',
      'start_date' => 'NULL',
      'case_id' => NULL,
      'end_date' => 'NULL',
    );
  }


  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    // return if no data present
    if (!is_array(CRM_Utils_Array::value('contact_check', $params))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get get list of relationship type based on the contact type.
   *
   * @param int $contactId
   *   This is the contact id of the current contact.
   * @param null $contactSuffix
   * @param string $relationshipId
   *   The id of the existing relationship if any.
   * @param string $contactType
   *   Contact type.
   * @param bool $all
   *   If true returns relationship types in both the direction.
   * @param string $column
   *   Name/label that going to retrieve from db.
   * @param bool $biDirectional
   * @param array $contactSubType
   *   Includes relationship types between this subtype.
   * @param bool $onlySubTypeRelationTypes
   *   If set only subtype which is passed by $contactSubType
   *   related relationship types get return
   *
   * @return array
   *   array reference of all relationship types with context to current contact.
   */
  public static function getContactRelationshipType(
    $contactId = NULL,
    $contactSuffix = NULL,
    $relationshipId = NULL,
    $contactType = NULL,
    $all = FALSE,
    $column = 'label',
    $biDirectional = TRUE,
    $contactSubType = NULL,
    $onlySubTypeRelationTypes = FALSE
  ) {

    $relationshipType = array();
    $allRelationshipType = CRM_Core_PseudoConstant::relationshipType($column);

    $otherContactType = NULL;
    if ($relationshipId) {
      $relationship = new CRM_Contact_DAO_Relationship();
      $relationship->id = $relationshipId;
      if ($relationship->find(TRUE)) {
        $contact = new CRM_Contact_DAO_Contact();
        $contact->id = ($relationship->contact_id_a == $contactId) ? $relationship->contact_id_b : $relationship->contact_id_a;

        if ($contact->find(TRUE)) {
          $otherContactType = $contact->contact_type;
          //CRM-5125 for contact subtype specific relationshiptypes
          if ($contact->contact_sub_type) {
            $otherContactSubType = $contact->contact_sub_type;
          }
        }
      }
    }

    $contactSubType = (array) $contactSubType;
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
        (in_array($value['contact_sub_type_a'], $contactSubType) ||
          (!$value['contact_sub_type_a'] && !$onlySubTypeRelationTypes)
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
        (in_array($value['contact_sub_type_b'], $contactSubType) ||
          (!$value['contact_sub_type_b'] && !$onlySubTypeRelationTypes)
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
      $relationshipType = self::removeRelationshipTypeDuplicates($relationshipType, $contactSuffix);
    }

    // sort the relationshipType in ascending order CRM-7736
    asort($relationshipType);
    return $relationshipType;
  }

  /**
   * Given a list of relationship types, return the list with duplicate types
   * removed, being careful to retain only the duplicate which matches the given
   * 'a_b' or 'b_a' suffix.
   *
   * @param array $relationshipTypeList A list of relationship types, in the format
   *   returned by self::getContactRelationshipType().
   * @param string $suffix Either 'a_b' or 'b_a'; defaults to 'a_b'
   *
   * @return array The modified value of $relationshipType
   */
  public static function removeRelationshipTypeDuplicates($relationshipTypeList, $suffix = NULL) {
    if (empty($suffix)) {
      $suffix = 'a_b';
    }

    // Find those labels which are listed more than once.
    $duplicateValues = array_diff_assoc($relationshipTypeList, array_unique($relationshipTypeList));

    // For each duplicate label, find its keys, and remove from $relationshipType
    // the key which does not match $suffix.
    foreach ($duplicateValues as $value) {
      $keys = array_keys($relationshipTypeList, $value);
      foreach ($keys as $key) {
        if (substr($key, -3) != $suffix) {
          unset($relationshipTypeList[$key]);
        }
      }
    }
    return $relationshipTypeList;
  }

  /**
   * Delete current employer relationship.
   *
   * @param int $id
   * @param int $action
   *
   * @return CRM_Contact_DAO_Relationship
   */
  public static function clearCurrentEmployer($id, $action) {
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->id = $id;
    $relationship->find(TRUE);

    //to delete relationship between household and individual                                                                                          \
    //or between individual and organization
    if (($action & CRM_Core_Action::DISABLE) || ($action & CRM_Core_Action::DELETE)) {
      $relTypes = CRM_Utils_Array::index(array('name_a_b'), CRM_Core_PseudoConstant::relationshipType('name'));
      if (
        (isset($relTypes['Employee of']) && $relationship->relationship_type_id == $relTypes['Employee of']['id']) ||
        (isset($relTypes['Household Member of']) && $relationship->relationship_type_id == $relTypes['Household Member of']['id'])
      ) {
        $sharedContact = new CRM_Contact_DAO_Contact();
        $sharedContact->id = $relationship->contact_id_a;
        $sharedContact->find(TRUE);

        // CRM-15881 UPDATES
        // changed FROM "...relationship->relationship_type_id == 4..." TO "...relationship->relationship_type_id == 5..."
        // As the system should be looking for type "employer of" (id 5) and not "sibling of" (id 4)
        // As suggested by @davecivicrm, the employee relationship type id is fetched using the CRM_Core_DAO::getFieldValue() class and method, since these ids differ from system to system.
        $employerRelTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Employee of', 'id', 'name_a_b');

        if ($relationship->relationship_type_id == $employerRelTypeId && $relationship->contact_id_b == $sharedContact->employer_id) {
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($relationship->contact_id_a);
        }

      }
    }
    return $relationship;
  }

  /**
   * Delete the relationship.
   *
   * @param int $id
   *   Relationship id.
   *
   * @return null
   */
  public static function del($id) {
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
   * Disable/enable the relationship.
   *
   * @param int $id
   *   Relationship id.
   *
   * @param int $action
   * @param array $params
   * @param array $ids
   * @param bool $active
   */
  public static function disableEnableRelationship($id, $action, $params = array(), $ids = array(), $active = FALSE) {
    $relationship = self::clearCurrentEmployer($id, $action);

    if ($id) {
      // create $params array which is required to delete memberships
      // of the related contacts.
      if (empty($params)) {
        $params = array(
          'relationship_type_id' => "{$relationship->relationship_type_id}_a_b",
          'contact_check' => array($relationship->contact_id_b => 1),
        );
      }
      $contact_id_a = empty($params['contact_id_a']) ? $relationship->contact_id_a : $params['contact_id_a'];
      // calling relatedMemberships to delete/add the memberships of
      // related contacts.
      if ($action & CRM_Core_Action::DISABLE) {
        CRM_Contact_BAO_Relationship::relatedMemberships($contact_id_a,
          $params,
          $ids,
          CRM_Core_Action::DELETE,
          $active
        );
      }
      elseif ($action & CRM_Core_Action::ENABLE) {
        $ids['contact'] = empty($ids['contact']) ? $contact_id_a : $ids['contact'];
        CRM_Contact_BAO_Relationship::relatedMemberships($contact_id_a,
          $params,
          $ids,
          empty($params['id']) ? CRM_Core_Action::ADD : CRM_Core_Action::UPDATE,
          $active
        );
      }
    }
  }

  /**
   * Delete the object records that are associated with this contact.
   *
   * @param int $contactId
   *   Id of the contact to delete.
   */
  public static function deleteContact($contactId) {
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->contact_id_a = $contactId;
    $relationship->delete();

    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->contact_id_b = $contactId;
    $relationship->delete();

    CRM_Contact_BAO_Household::updatePrimaryContact(NULL, $contactId);
  }

  /**
   * Get the other contact in a relationship.
   *
   * @param int $id
   *   Relationship id.
   *
   * $returns  returns the contact ids in the realtionship
   *
   * @return \CRM_Contact_DAO_Relationship
   */
  public static function getRelationshipByID($id) {
    $relationship = new CRM_Contact_DAO_Relationship();

    $relationship->id = $id;
    $relationship->selectAdd();
    $relationship->selectAdd('contact_id_a, contact_id_b');
    $relationship->find(TRUE);

    return $relationship;
  }

  /**
   * Check if the relationship type selected between two contacts is correct.
   *
   * @param int $contact_a
   *   1st contact id.
   * @param int $contact_b
   *   2nd contact id.
   * @param int $relationshipTypeId
   *   Relationship type id.
   *
   * @return bool
   *   true if it is valid relationship else false
   */
  public static function checkRelationshipType($contact_a, $contact_b, $relationshipTypeId) {
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
   * This function does the validtion for valid relationship.
   *
   * @param array $params
   *   This array contains the values there are subitted by the form.
   * @param array $ids
   *   The array that holds all the db ids.
   * @param int $contactId
   *   This is contact id for adding relationship.
   *
   * @return string
   */
  public static function checkValidRelationship($params, $ids, $contactId) {
    $errors = '';
    // function to check if the relationship selected is correct
    // i.e. employer relationship can exit between Individual and Organization (not between Individual and Individual)
    if (!CRM_Contact_BAO_Relationship::checkRelationshipType($params['contact_id_a'], $params['contact_id_b'],
      $params['relationship_type_id'])) {
      $errors = 'Please select valid relationship between these two contacts.';
    }
    return $errors;
  }

  /**
   * This function checks for duplicate relationship.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param int $id
   *   This the id of the contact whom we are adding relationship.
   * @param int $contactId
   *   This is contact id for adding relationship.
   * @param int $relationshipId
   *   This is relationship id for the contact.
   *
   * @return bool
   *   true if record exists else false
   */
  public static function checkDuplicateRelationship(&$params, $id, $contactId = 0, $relationshipId = 0) {
    $relationshipTypeId = CRM_Utils_Array::value('relationship_type_id', $params);
    list($type) = explode('_', $relationshipTypeId);

    $queryString = "
SELECT id
FROM   civicrm_relationship
WHERE  relationship_type_id = " . CRM_Utils_Type::escape($type, 'Integer');

    /*
     * CRM-11792 - date fields from API are in ISO format, but this function
     * supports date arrays BAO has increasingly standardised to ISO format
     * so I believe this function should support ISO rather than make API
     * format it - however, need to support array format for now to avoid breakage
     * @ time of writing this function is called from Relationship::legacyCreateMultiple (twice)
     * CRM_BAO_Contact_Utils::clearCurrentEmployer (seemingly without dates)
     * CRM_Contact_Form_Task_AddToOrganization::postProcess &
     * CRM_Contact_Form_Task_AddToHousehold::postProcess
     * (I don't think the last 2 support dates but not sure
     */

    $dateFields = array('end_date', 'start_date');
    foreach ($dateFields as $dateField) {
      if (array_key_exists($dateField, $params)) {
        if (empty($params[$dateField]) || $params[$dateField] == 'null') {
          //this is most likely coming from an api call & probably loaded
          // from the DB to deal with some of the
          // other myriad of excessive checks still in place both in
          // the api & the create functions
          $queryString .= " AND $dateField IS NULL";
          continue;
        }
        elseif (is_array($params[$dateField])) {
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
    while ($relationship->fetch()) {
      // Check whether the custom field values are identical.
      $result = self::checkDuplicateCustomFields($params, $relationship->id);
      if ($result) {
        $relationship->free();
        return TRUE;
      }
    }
    $relationship->free();
    return FALSE;
  }

  /**
   * this function checks whether the values of the custom fields in $params are
   * the same as the values of the custom fields of the relation with given
   * $relationshipId.
   *
   * @param array $params (reference) an assoc array of name/value pairs
   * @param int $relationshipId ID of an existing duplicate relation
   *
   * @return boolean true if custom field values are identical
   * @access private
   * @static
   */
  private static function checkDuplicateCustomFields(&$params, $relationshipId) {
    // Get the custom values of the existing relationship.
    $existingValues = CRM_Core_BAO_CustomValueTable::getEntityValues($relationshipId, 'Relationship');
    // Create a similar array for the new relationship.
    $newValues = array();
    if (array_key_exists('custom', $params)) {
      // $params['custom'] seems to be an array. Each value is again an array.
      // This array contains one value (key -1), and this value seems to be
      // an array with the information about the custom value.
      foreach ($params['custom'] as $value) {
        foreach ($value as $customValue) {
          $newValues[$customValue['custom_field_id']] = $customValue['value'];
        }
      }
    }

    // Calculate difference between arrays. If the only key-value pairs
    // that are in one array but not in the other are empty, the
    // custom fields are considered to be equal.
    // See https://github.com/civicrm/civicrm-core/pull/6515#issuecomment-137985667
    $diff1 = array_diff_assoc($existingValues, $newValues);
    $diff2 = array_diff_assoc($newValues, $existingValues);

    return !array_filter($diff1) && !array_filter($diff2);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @throws CiviCRM_API3_Exception
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
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

    if (is_array($result) && !empty($result['is_error']) && $result['error_message'] != 'Duplicate Relationship') {
      throw new CiviCRM_API3_Exception($result['error_message'], CRM_Utils_Array::value('error_code', $result, 'undefined'), $result);
    }

    return TRUE;
  }

  /**
   * Fetch a relationship object and store the values in the values array.
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return array
   *   (reference)   the values that could be potentially assigned to smarty
   */
  public static function &getValues(&$params, &$values) {
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
    $v['totalCount'] = count($v['data']);

    $values['relationship']['data'] = &$v['data'];
    $values['relationship']['totalCount'] = &$v['totalCount'];

    return $v;
  }

  /**
   * Helper function to form the sql for relationship retrieval.
   *
   * @param int $contactId
   *   Contact id.
   * @param int $status
   *   (check const at top of file).
   * @param int $numRelationship
   *   No of relationships to display (limit).
   * @param int $count
   *   Get the no of relationships.
   * $param int $relationshipId relationship id
   * @param int $relationshipId
   * @param string $direction
   *   The direction we are interested in a_b or b_a.
   * @param array $params
   *   Array of extra values including relationship_type_id per api spec.
   *
   * @return array
   *   [select, from, where]
   */
  public static function makeURLClause($contactId, $status, $numRelationship, $count, $relationshipId, $direction, $params = array()) {
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
                              civicrm_contact.contact_sub_type as contact_sub_type,
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

    if (!$count) {
      $from .= "
LEFT JOIN  civicrm_address ON (civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1)
LEFT JOIN  civicrm_phone   ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary = 1)
LEFT JOIN  civicrm_email   ON (civicrm_email.contact_id = civicrm_contact.id AND civicrm_email.is_primary = 1)
LEFT JOIN  civicrm_state_province ON (civicrm_address.state_province_id = civicrm_state_province.id)
LEFT JOIN  civicrm_country ON (civicrm_address.country_id = civicrm_country.id)
";
    }

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
    if (!empty($params['membership_type_id']) && empty($params['relationship_type_id'])) {
      $where .= self::membershipTypeToRelationshipTypes($params, $direction);
    }
    if (!empty($params['relationship_type_id'])) {
      if (is_array($params['relationship_type_id'])) {
        $where .= " AND " . CRM_Core_DAO::createSQLFilter('relationship_type_id', $params['relationship_type_id'], 'Integer');
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
   * Get a list of relationships.
   *
   * @param int $contactId
   *   Contact id.
   * @param int $status
   *   1: Past 2: Disabled 3: Current.
   * @param int $numRelationship
   *   No of relationships to display (limit).
   * @param int $count
   *   Get the no of relationships.
   * @param int $relationshipId
   * @param array $links
   *   the list of links to display
   * @param int $permissionMask
   *   the permission mask to be applied for the actions
   * @param bool $permissionedContact
   *   to return only permissioned Contact
   * @param array $params
   * @param bool $includeTotalCount
   *   Should we return a count of total accessable relationships
   *
   * @return array|int
   *   relationship records
   */
  public static function getRelationship(
    $contactId = NULL,
    $status = 0, $numRelationship = 0,
    $count = 0, $relationshipId = 0,
    $links = NULL, $permissionMask = NULL,
    $permissionedContact = FALSE,
    $params = array(), $includeTotalCount = FALSE
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
    $queryString = $select1 . $from1 . $where1 . $select2 . $from2 . $where2;

    $relationship = new CRM_Contact_DAO_Relationship();

    $relationship->query($queryString . $order . $limit);
    $row = array();
    if ($count) {
      $relationshipCount = 0;
      while ($relationship->fetch()) {
        $relationshipCount += $relationship->cnt1 + $relationship->cnt2;
      }
      return $relationshipCount;
    }
    else {

      if ($includeTotalCount) {
        $values['total_relationships'] = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM ({$queryString}) AS r");
      }

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
        }
        // temporary hold the value of $mask.
        $tempMask = $mask;
      }

      while ($relationship->fetch()) {
        $rid = $relationship->civicrm_relationship_id;
        $cid = $relationship->civicrm_contact_id;

        if ($permissionedContact &&
          (!CRM_Contact_BAO_Contact_Permission::allow($cid))
        ) {
          continue;
        }
        if ($status != self::INACTIVE && $links) {
          // assign the original value to $mask
          $mask = $tempMask;
          // display action links if $cid has edit permission for the relationship.
          if (!($permissionMask & CRM_Core_Permission::EDIT) && CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT)) {
            $permissions[] = CRM_Core_Permission::EDIT;
            $permissions[] = CRM_Core_Permission::DELETE;
            $permissionMask = CRM_Core_Action::mask($permissions);
          }
          $mask = $mask & $permissionMask;
        }
        $values[$rid]['id'] = $rid;
        $values[$rid]['cid'] = $cid;
        $values[$rid]['contact_id_a'] = $relationship->contact_id_a;
        $values[$rid]['contact_id_b'] = $relationship->contact_id_b;
        $values[$rid]['contact_type'] = $relationship->contact_type;
        $values[$rid]['contact_sub_type'] = $relationship->contact_sub_type;
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
            $mask = $mask & $permissionMask;
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
   * Get get list of relationship type based on the target contact type.
   *
   * @param string $targetContactType
   *   It's valid contact tpye(may be Individual , Organization , Household).
   *
   * @return array
   *   array reference of all relationship types with context to current contact type .
   */
  static public function getRelationType($targetContactType) {
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
   * Create / update / delete membership for related contacts.
   *
   * This function will create/update/delete membership for related
   * contact based on 1) contact have active membership 2) that
   * membership is is extedned by the same relationship type to that
   * of the existing relationship.
   *
   * @param int $contactId
   *   contact id.
   * @param array $params
   *   array of values submitted by POST.
   * @param array $ids
   *   array of ids.
   * @param \const|int $action which action called this function
   *
   * @param bool $active
   *
   * @throws \CRM_Core_Exception
   */
  public static function relatedMemberships($contactId, &$params, $ids, $action = CRM_Core_Action::ADD, $active = TRUE) {
    // Check the end date and set the status of the relationship
    // accordingly.
    $status = self::CURRENT;
    $targetContact = $targetContact = CRM_Utils_Array::value('contact_check', $params, array());
    $today = date('Ymd');

    // If a relationship hasn't yet started, just return for now
    // TODO: handle edge-case of updating start_date of an existing relationship
    if (!empty($params['start_date'])) {
      $startDate = substr(CRM_Utils_Date::format($params['start_date']), 0, 8);
      if ($today < $startDate) {
        return;
      }
    }

    if (!empty($params['end_date'])) {
      $endDate = substr(CRM_Utils_Date::format($params['end_date']), 0, 8);
      if ($today > $endDate) {
        $status = self::PAST;
      }
    }

    if (($action & CRM_Core_Action::ADD) && ($status & self::PAST)) {
      // If relationship is PAST and action is ADD, do nothing.
      return;
    }

    $rel = explode('_', $params['relationship_type_id']);

    $relTypeId = $rel[0];
    if (!empty($rel[1])) {
      $relDirection = "_{$rel[1]}_{$rel[2]}";
    }
    else {
      // this call is coming from somewhere where the direction was resolved early on (e.g an api call)
      // so we can assume _a_b
      $relDirection = "_a_b";
      $targetContact = array($params['contact_id_b'] => 1);
    }

    if (($action & CRM_Core_Action::ADD) ||
      ($action & CRM_Core_Action::DELETE)
    ) {
      $contact = $contactId;
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

    // CRM-15829 UPDATES
    // If we're looking for active memberships we must consider pending (id: 5) ones too.
    // Hence we can't just call CRM_Member_BAO_Membership::getValues below with the active flag, is it would completely miss pending relatioships.
    // As suggested by @davecivicrm, the pending status id is fetched using the CRM_Member_PseudoConstant::membershipStatus() class and method, since these ids differ from system to system.
    $pendingStatusId = array_search('Pending', CRM_Member_PseudoConstant::membershipStatus());

    $query = 'SELECT * FROM `civicrm_membership_status`';
    if ($active) {
      $query .= ' WHERE `is_current_member` = 1 OR `id` = %1 ';
    }

    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($pendingStatusId, 'Integer')));

    while ($dao->fetch()) {
      $membershipStatusRecordIds[$dao->id] = $dao->id;
    }

    // Now get the active memberships for all the contacts.
    // If contact have any valid membership(s), then add it to
    // 'values' array.
    foreach ($values as $cid => $subValues) {
      $memParams = array('contact_id' => $cid);
      $memberships = array();

      // CRM-15829 UPDATES
      // Since we want PENDING memberships as well, the $active flag needs to be set to false so that this will return all memberships and we can then filter the memberships based on the status IDs recieved above.
      CRM_Member_BAO_Membership::getValues($memParams, $memberships, FALSE, TRUE);

      // CRM-15829 UPDATES
      // filter out the memberships returned by CRM_Member_BAO_Membership::getValues based on the status IDs fetched on line ~1462
      foreach ($memberships as $key => $membership) {

        if (!isset($memberships[$key]['status_id'])) {
          continue;
        }

        $membershipStatusId = $memberships[$key]['status_id'];
        if (!isset($membershipStatusRecordIds[$membershipStatusId])) {
          unset($memberships[$key]);
        }
      }

      if (empty($memberships)) {
        continue;
      }

      //get ownerMembershipIds for related Membership
      //this is to handle memberships being deleted and recreated
      if (!empty($memberships['owner_membership_ids'])) {
        $ownerMemIds[$cid] = $memberships['owner_membership_ids'];
        unset($memberships['owner_membership_ids']);
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

      $relatedContacts = array_keys(CRM_Utils_Array::value('relatedContacts', $details, array()));
      $mainRelatedContactId = reset($relatedContacts);

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
          if (in_array($values[$cid]['relationshipTypeId'], $relTypeIds
          //CRM-16300 check if owner membership exist for related membership
          ) && !empty($membershipValues['owner_membership_id']) && !empty($values[$mainRelatedContactId]['memberships'][$membershipValues['owner_membership_id']])) {
            CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipValues['owner_membership_id'], $membershipValues['membership_contact_id']);
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
              'join_date',
              'start_date',
              'end_date',
            ) as $dateField) {
              if (!empty($membershipValues[$dateField])) {
                $membershipValues[$dateField] = CRM_Utils_Date::processDate($membershipValues[$dateField]);
              }
            }

            if ($action & CRM_Core_Action::UPDATE) {
              //if updated relationship is already related to contact don't delete existing inherited membership
              if (in_array($relTypeId, $relTypeIds
                ) && !empty($values[$relatedContactId]['memberships']) && !empty($ownerMemIds
                ) && in_array($membershipValues['owner_membership_id'], $ownerMemIds[$relatedContactId])) {
                continue;
              }

              //delete the membership record for related
              //contact before creating new membership record.
              CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId, $relatedContactId);
            }
            //skip status calculation for pay later memberships.
            if (!empty($membershipValues['status_id']) && $membershipValues['status_id'] == $pendingStatusId) {
              $membershipValues['skipStatusCal'] = TRUE;
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
          // change the status of the membership record to expired for
          // previous relationship -- CRM-12078.
          // CRM-16087 we need to pass ownerMembershipId to isRelatedMembershipExpired function
          if (empty($params['relationship_ids']) && !empty($params['id'])) {
            $relIds = array($params['id']);
          }
          else {
            $relIds = CRM_Utils_Array::value('relationship_ids', $params);
          }
          if (self::isRelatedMembershipExpired($relTypeIds, $contactId, $mainRelatedContactId, $relTypeId,
          $relIds) && !empty($membershipValues['owner_membership_id']
          ) && !empty($values[$mainRelatedContactId]['memberships'][$membershipValues['owner_membership_id']])) {
            $membershipValues['status_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', 'Expired', 'id', 'label');
            $type = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membershipValues['membership_type_id'], 'name', 'id');
            CRM_Member_BAO_Membership::add($membershipValues);
            CRM_Core_Session::setStatus(ts("Inherited membership {$type} status was changed to Expired due to the change in relationship type."), ts('Record Updated'), 'alert');
          }
        }
      }
    }
  }

  /**
   * Helper function to check whether the membership is expired or not.
   *
   * Function takes a list of related membership types and if it is not also passed a
   * relationship ID of that types evaluates whether the membership status should be changed to expired.
   *
   * @param array $membershipTypeRelationshipTypeIDs
   *   Relation type IDs related to the given membership type.
   * @param int $contactId
   * @param int $mainRelatedContactId
   * @param int $relTypeId
   * @param array $relIds
   *
   * @return bool
   */
  public static function isRelatedMembershipExpired($membershipTypeRelationshipTypeIDs, $contactId, $mainRelatedContactId, $relTypeId, $relIds) {
    if (empty($membershipTypeRelationshipTypeIDs) || in_array($relTypeId, $membershipTypeRelationshipTypeIDs)) {
      return FALSE;
    }

    if (empty($relIds)) {
      return FALSE;
    }

    $relParamas = array(
      1 => array($contactId, 'Integer'),
      2 => array($mainRelatedContactId, 'Integer'),
    );

    if ($contactId == $mainRelatedContactId) {
      $recordsFound = (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $membershipTypeRelationshipTypeIDs) . " )  AND
contact_id_a IN ( %1 ) OR contact_id_b IN ( %1 ) AND id IN (" . implode(',', $relIds) . ")", $relParamas);
      if ($recordsFound) {
        return FALSE;
      }
      return TRUE;
    }

    $recordsFound = (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $membershipTypeRelationshipTypeIDs) . " ) AND contact_id_a IN ( %1, %2 ) AND contact_id_b IN ( %1, %2 ) AND id NOT IN (" . implode(',', $relIds) . ")", $relParamas);

    if ($recordsFound) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get Current Employer for Contact.
   *
   * @param $contactIds
   *   Contact Ids.
   *
   * @return array
   *   array of the current employer
   */
  public static function getCurrentEmployer($contactIds) {
    $contacts = implode(',', $contactIds);

    $query = "
SELECT organization_name, id, employer_id
FROM civicrm_contact
WHERE id IN ( {$contacts} )
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $currentEmployer = array();
    while ($dao->fetch()) {
      $currentEmployer[$dao->id]['org_id'] = $dao->employer_id;
      $currentEmployer[$dao->id]['org_name'] = $dao->organization_name;
    }

    return $currentEmployer;
  }

  /**
   * Function to return list of permissioned contacts for a given contact and relationship type.
   *
   * @param int $contactID
   *   contact id whose permissioned contacts are to be found.
   * @param int $relTypeId
   *   one or more relationship type id's.
   * @param string $name
   * @param string $contactType
   *
   * @return array
   *   Array of contacts
   */
  public static function getPermissionedContacts($contactID, $relTypeId = NULL, $name = NULL, $contactType = NULL) {
    $contacts = array();
    $args = array(1 => array($contactID, 'Integer'));
    $relationshipTypeClause = $contactTypeClause = '';

    if ($relTypeId) {
      // @todo relTypeId is only ever passed in as an int. Change this to reflect that -
      // probably being overly conservative by not doing so but working on stable release.
      $relationshipTypeClause = 'AND cr.relationship_type_id IN (%2) ';
      $args[2] = array($relTypeId, 'String');
    }

    if ($contactType) {
      $contactTypeClause = ' AND cr.relationship_type_id = crt.id AND crt.contact_type_b = %3 ';
      $args[3] = array($contactType, 'String');
    }

    $query = "
SELECT cc.id as id, cc.sort_name as name
FROM civicrm_relationship cr, civicrm_contact cc, civicrm_relationship_type crt
WHERE
cr.contact_id_a         = %1 AND
cr.is_permission_a_b    = 1 AND
IF(cr.end_date IS NULL, 1, (DATEDIFF( CURDATE( ), cr.end_date ) <= 0)) AND
cr.is_active = 1 AND
cc.id = cr.contact_id_b AND
cc.is_deleted = 0
$relationshipTypeClause
$contactTypeClause
";

    if (!empty($name)) {
      $name = CRM_Utils_Type::escape($name, 'String');
      $query .= "
AND cc.sort_name LIKE '%$name%'";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $args);
    while ($dao->fetch()) {
      $contacts[$dao->id] = array(
        'name' => $dao->name,
        'value' => $dao->id,
      );
    }

    return $contacts;
  }

  /**
   * Merge relationships from otherContact to mainContact.
   *
   * Called during contact merge operation
   *
   * @param int $mainId
   *   Contact id of main contact record.
   * @param int $otherId
   *   Contact id of record which is going to merge.
   * @param array $sqls
   *   (reference) array of sql statements to append to.
   *
   * @see CRM_Dedupe_Merger::cpTables()
   */
  public static function mergeRelationships($mainId, $otherId, &$sqls) {
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
   * @return bool
   *   True on success, false if error is encountered.
   */
  public static function disableExpiredRelationships() {
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
   * Function filters the query by possible relationships for the membership type.
   *
   * It is intended to be called when constructing queries for the api (reciprocal & non-reciprocal)
   * and to add clauses to limit the return to those relationships which COULD inherit a membership type
   * (as opposed to those who inherit a particular membership
   *
   * @param array $params
   *   Api input array.
   * @param null $direction
   *
   * @return array|void
   */
  public static function membershipTypeToRelationshipTypes(&$params, $direction = NULL) {
    $membershipType = civicrm_api3('membership_type', 'getsingle', array(
      'id' => $params['membership_type_id'],
      'return' => 'relationship_type_id, relationship_direction',
    ));
    $relationshipTypes = $membershipType['relationship_type_id'];
    if (empty($relationshipTypes)) {
      return NULL;
    }
    // if we don't have any contact data we can only filter on type
    if (empty($params['contact_id']) && empty($params['contact_id_a']) && empty($params['contact_id_a'])) {
      $params['relationship_type_id'] = array('IN' => $relationshipTypes);
      return NULL;
    }
    else {
      $relationshipDirections = (array) $membershipType['relationship_direction'];
      // if we have contact_id_a OR contact_id_b we can make a call here
      // if we have contact??
      foreach ($relationshipDirections as $index => $mtdirection) {
        if (isset($params['contact_id_a']) && $mtdirection == 'a_b' || $direction == 'a_b') {
          $types[] = $relationshipTypes[$index];
        }
        if (isset($params['contact_id_b']) && $mtdirection == 'b_a' || $direction == 'b_a') {
          $types[] = $relationshipTypes[$index];
        }
      }
      if (!empty($types)) {
        $params['relationship_type_id'] = array('IN' => $types);
      }
      elseif (!empty($clauses)) {
        return explode(' OR ', $clauses);
      }
      else {
        // effectively setting it to return no results
        $params['relationship_type_id'] = 0;
      }
    }
  }


  /**
   * Wrapper for contact relationship selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of contact relationships
   */
  public static function getContactRelationshipSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);

    if ($params['context'] == 'past') {
      $relationshipStatus = CRM_Contact_BAO_Relationship::INACTIVE;
    }
    elseif ($params['context'] == 'all') {
      $relationshipStatus = CRM_Contact_BAO_Relationship::ALL;
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

    $permissionedContacts = TRUE;
    if ($params['context'] != 'user') {
      $links = CRM_Contact_Page_View_Relationship::links();
    }
    else {
      $links = CRM_Contact_Page_View_UserDashBoard::links();
      $mask = NULL;
    }
    // get contact relationships
    $relationships = CRM_Contact_BAO_Relationship::getRelationship($params['contact_id'],
      $relationshipStatus,
      $params['rp'], 0, 0,
      $links, $mask,
      $permissionedContacts,
      $params, TRUE
    );

    $contactRelationships = array();
    $params['total'] = $relationships['total_relationships'];
    unset($relationships['total_relationships']);
    if (!empty($relationships)) {

      $displayName = CRM_Contact_BAO_Contact::displayName($params['contact_id']);

      // format params
      foreach ($relationships as $relationshipId => $values) {
        $relationship = array();

        $relationship['DT_RowId'] = $values['id'];
        $relationship['DT_RowClass'] = 'crm-entity';
        if ($values['is_active'] == 0) {
          $relationship['DT_RowClass'] .= ' disabled';
        }

        $relationship['DT_RowAttr'] = array();
        $relationship['DT_RowAttr']['data-entity'] = 'relationship';
        $relationship['DT_RowAttr']['data-id'] = $values['id'];

        //Add image icon for related contacts: CRM-14919; CRM-19668
        $contactType = (!empty($values['contact_sub_type'])) ? $values['contact_sub_type'] : $values['contact_type'];
        $icon = CRM_Contact_BAO_Contact_Utils::getImage($contactType,
          FALSE,
          $values['cid']
        );
        $relationship['sort_name'] = $icon . ' ' . CRM_Utils_System::href(
            $values['name'],
            'civicrm/contact/view',
            "reset=1&cid={$values['cid']}");

        $relationship['relation'] = CRM_Utils_System::href(
          $values['relation'],
          'civicrm/contact/view/rel',
          "action=view&reset=1&cid={$values['cid']}&id={$values['id']}&rtype={$values['rtype']}");

        if (!empty($values['description'])) {
          $relationship['relation'] .= "<p class='description'>{$values['description']}</p>";
        }

        if ($params['context'] == 'current') {
          $smarty = CRM_Core_Smarty::singleton();

          $contactCombos = [
            [
              'permContact' => $params['contact_id'],
              'permDisplayName' => $displayName,
              'otherContact' => $values['cid'],
              'otherDisplayName' => $values['display_name'],
              'columnKey' => 'sort_name',
            ],
            [
              'permContact' => $values['cid'],
              'permDisplayName' => $values['display_name'],
              'otherContact' => $params['contact_id'],
              'otherDisplayName' => $displayName,
              'columnKey' => 'relation',
            ],
          ];

          foreach ($contactCombos as $combo) {
            foreach ([CRM_Contact_BAO_Relationship::EDIT, CRM_Contact_BAO_Relationship::VIEW] as $permType) {
              $smarty->assign('permType', $permType);
              if (($combo['permContact'] == $values['contact_id_a'] and $values['is_permission_a_b'] == $permType)
                || ($combo['permContact'] == $values['contact_id_b'] and $values['is_permission_b_a'] == $permType)
              ) {
                $smarty->assign('permDisplayName', $combo['permDisplayName']);
                $smarty->assign('otherDisplayName', $combo['otherDisplayName']);
                $relationship[$combo['columnKey']] .= $smarty->fetch('CRM/Contact/Page/View/RelationshipPerm.tpl');
              }
            }
          }
        }

        $relationship['start_date'] = CRM_Utils_Date::customFormat($values['start_date']);
        $relationship['end_date'] = CRM_Utils_Date::customFormat($values['end_date']);
        $relationship['city'] = $values['city'];
        $relationship['state'] = $values['state'];
        $relationship['email'] = $values['email'];
        $relationship['phone'] = $values['phone'];
        $relationship['links'] = $values['action'];

        array_push($contactRelationships, $relationship);
      }
    }

    $relationshipsDT = array();
    $relationshipsDT['data'] = $contactRelationships;
    $relationshipsDT['recordsTotal'] = $params['total'];
    $relationshipsDT['recordsFiltered'] = $params['total'];

    return $relationshipsDT;
  }

  /**
   * @inheritdoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    if ($fieldName === 'relationship_type_id') {
      return self::buildRelationshipTypeOptions($props);
    }

    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * Builds a list of options available for relationship types
   *
   * @param array $params
   *   - contact_type: Limits by contact type on the "A" side
   *   - relationship_id: Used to find the value for contact type for "B" side.
   *     If contact_a matches provided contact_id then type of contact_b will
   *     be used. Otherwise uses type of contact_a. Must be used with contact_id
   *   - contact_id: Limits by contact types of this contact on the "A" side
   *   - is_form: Returns array with keys indexed for use in a quickform
   *   - relationship_direction: For relationship types with duplicate names
   *     on both sides, defines which option should be returned, a_b or b_a
   *
   * @return array
   */
  public static function buildRelationshipTypeOptions($params = array()) {
    $contactId = CRM_Utils_Array::value('contact_id', $params);
    $direction = CRM_Utils_Array::value('relationship_direction', $params, 'a_b');
    $relationshipId = CRM_Utils_Array::value('relationship_id', $params);
    $contactType = CRM_Utils_Array::value('contact_type', $params);
    $isForm = CRM_Utils_Array::value('is_form', $params);
    $showAll = FALSE;

    // getContactRelationshipType will return an empty set if these are not set
    if (!$contactId && !$relationshipId && !$contactType) {
      $showAll = TRUE;
    }

    $labels = self::getContactRelationshipType(
      $contactId,
      $direction,
      $relationshipId,
      $contactType,
      $showAll,
      'label'
    );

    if ($isForm) {
      return $labels;
    }

    $names = self::getContactRelationshipType(
      $contactId,
      $direction,
      $relationshipId,
      $contactType,
      $showAll,
      'name'
    );

    // ensure $names contains only entries in $labels
    $names = array_intersect_key($names, $labels);

    $nameToLabels = array_combine($names, $labels);

    return $nameToLabels;
  }

  /**
   * Process the params from api, form and check if current
   * employer should be set or unset.
   *
   * @param array $params
   * @param int $relationshipId
   * @param int|NULL $updatedRelTypeID
   *
   * @return bool
   *   TRUE if current employer needs to be cleared.
   */
  public static function isCurrentEmployerNeedingToBeCleared($params, $relationshipId, $updatedRelTypeID = NULL) {
    $existingTypeID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Relationship', $relationshipId, 'relationship_type_id');
    $existingTypeName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $existingTypeID, 'name_b_a');
    $updatedRelTypeID = $updatedRelTypeID ? $updatedRelTypeID : $existingTypeID;

    if ($existingTypeName !== 'Employer of') {
      return FALSE;
    }
    //Clear employer if relationship is expired.
    if (!empty($params['end_date']) && strtotime($params['end_date']) < time()) {
      return TRUE;
    }
    //current employer checkbox is disabled on the form.
    //inactive or relationship type(employer of) is updated.
    if ((isset($params['is_current_employer']) && empty($params['is_current_employer']))
      || ((isset($params['is_active']) && empty($params['is_active'])))
      || $existingTypeID != $updatedRelTypeID) {
      return TRUE;
    }

    return FALSE;
  }

}
