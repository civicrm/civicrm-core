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

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\MembershipType;
use Civi\Api4\Relationship;

/**
 * Class CRM_Contact_BAO_Relationship.
 */
class CRM_Contact_BAO_Relationship extends CRM_Contact_DAO_Relationship implements \Civi\Core\HookInterface {

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
   * The list of column headers
   * @var array
   */
  private static $columnHeaders;

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
    // When id is specified we always want to update, so we don't need to check for duplicate relations.
    if (!isset($params['id']) && self::checkDuplicateRelationship($extendedParams, (int) $extendedParams['contact_id_a'], (int) $extendedParams['contact_id_b'], $extendedParams['id'] ?? 0)) {
      throw new CRM_Core_Exception('Duplicate Relationship');
    }
    $params = $extendedParams;
    // Check if this is a "simple" disable relationship. If it is don't check the relationshipType
    $disableRelationship = !empty($params['id']) && array_key_exists('is_active', $params) && empty($params['is_active']);
    if (!$disableRelationship && !CRM_Contact_BAO_Relationship::checkRelationshipType($params['contact_id_a'], $params['contact_id_b'], $params['relationship_type_id'])) {
      throw new CRM_Core_Exception('Invalid Relationship');
    }
    $relationship = self::add($params);
    if (!empty($params['contact_id_a'])) {
      $ids = [
        'contactTarget' => $relationship->contact_id_b,
        'contact' => $params['contact_id_a'],
      ];

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
    $relationshipIDs = [];
    foreach ($secondaryContactIDs as $secondaryContactID) {
      try {
        $params['contact_id_' . $secondaryContactLetter] = $secondaryContactID;
        $relationship = civicrm_api3('relationship', 'create', $params);
        $relationshipIDs[] = $relationship['id'];
        $valid++;
      }
      catch (CRM_Core_Exception $e) {
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

    return [
      'valid' => $valid,
      'invalid' => $invalid,
      'duplicate' => $duplicate,
      'saved' => $saved,
      'relationship_ids' => $relationshipIDs,
    ];
  }

  /**
   * This is the function that check/add if the relationship created is valid.
   *
   * @param array $params
   *   Array of name/value pairs.
   * @param array $ids
   *   The array that holds all the db ids.
   *
   * @return CRM_Contact_BAO_Relationship
   *
   * @throws \CRM_Core_Exception
   */
  public static function add($params, $ids = []) {
    $params['id'] = $ids['relationship'] ?? $params['id'] ?? NULL;

    $hook = 'create';
    if ($params['id']) {
      $hook = 'edit';
    }
    CRM_Utils_Hook::pre($hook, 'Relationship', $params['id'], $params);

    $relationshipTypes = $params['relationship_type_id'] ?? '';
    // explode the string with _ to get the relationship type id
    // and to know which contact has to be inserted in
    // contact_id_a and which one in contact_id_b
    [$relationshipTypeID] = explode('_', $relationshipTypes);

    $relationship = new CRM_Contact_BAO_Relationship();
    if (!empty($params['id'])) {
      $relationship->id = $params['id'];
      // Only load the relationship if we're missing required params
      $requiredParams = ['contact_id_a', 'contact_id_b', 'relationship_type_id'];
      foreach ($requiredParams as $requiredKey) {
        if (!isset($params[$requiredKey])) {
          $relationship->find(TRUE);
          break;
        }
      }

    }
    $relationship->copyValues($params);
    // @todo we could probably set $params['relationship_type_id'] above but it's unclear
    //   what that would do with the code below this. So for now be conservative and set it manually.
    if (!empty($relationshipTypeID)) {
      $relationship->relationship_type_id = $relationshipTypeID;
    }

    $params['contact_id_a'] = $relationship->contact_id_a;
    $params['contact_id_b'] = $relationship->contact_id_b;

    // check if the relationship type is Head of Household then update the
    // household's primary contact with this contact.
    try {
      $headOfHouseHoldID = civicrm_api3('RelationshipType', 'getvalue', [
        'return' => "id",
        'name_a_b' => "Head of Household for",
      ]);
      if ($relationshipTypeID == $headOfHouseHoldID) {
        CRM_Contact_BAO_Household::updatePrimaryContact($relationship->contact_id_b, $relationship->contact_id_a);
      }
    }
    catch (Exception $e) {
      // No "Head of Household" relationship found so we skip specific processing
    }

    if (!empty($params['id']) && self::isCurrentEmployerNeedingToBeCleared($relationship->toArray(), $params['id'], $relationshipTypeID)) {
      CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($relationship->contact_id_a);
    }

    $dateFields = ['end_date', 'start_date'];

    foreach (self::getdefaults() as $defaultField => $defaultValue) {
      if (isset($params[$defaultField])) {
        if (in_array($defaultField, $dateFields)) {
          $relationship->$defaultField = CRM_Utils_Date::format($params[$defaultField] ?? NULL);
          if (!$relationship->$defaultField) {
            $relationship->$defaultField = 'NULL';
          }
        }
        else {
          $relationship->$defaultField = $params[$defaultField];
        }
      }
      elseif (empty($params['id'])) {
        $relationship->$defaultField = $defaultValue;
      }
    }

    $relationship->save();
    // is_current_employer is an optional parameter that triggers updating the employer_id field to reflect
    // the relationship being updated. As of writing only truthy versions of the parameter are respected.
    // https://github.com/civicrm/civicrm-core/pull/13331 attempted to cover both but stalled in QA
    // so currently we have a cut down version.
    if (!empty($params['is_current_employer'])) {
      if (!$relationship->relationship_type_id || !$relationship->contact_id_a || !$relationship->contact_id_b) {
        $relationship->fetch();
      }
      if (self::isRelationshipTypeCurrentEmployer($relationship->relationship_type_id)) {
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer([$relationship->contact_id_a => $relationship->contact_id_b]);
      }
    }
    // add custom field values
    if (!empty($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_relationship', $relationship->id);
    }

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
    $recentOther = [];
    if (($session->get('userID') == $relationship->contact_id_a) ||
      CRM_Contact_BAO_Contact_Permission::allow($relationship->contact_id_a, CRM_Core_Permission::EDIT)
    ) {
      $rType = substr($params['relationship_type_id'], -3);
      $recentOther = [
        'editUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=update&reset=1&id={$relationship->id}&cid={$relationship->contact_id_a}&rtype={$rType}&context=home"
        ),
        'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=delete&reset=1&id={$relationship->id}&cid={$relationship->contact_id_a}&rtype={$rType}&context=home"
        ),
      ];
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

    $fieldsToFill = ['contact_id_a', 'contact_id_b', 'relationship_type_id'];
    $result = CRM_Core_DAO::executeQuery("SELECT " . implode(',', $fieldsToFill) . " FROM civicrm_relationship WHERE id = %1", [
      1 => [
        $params['id'],
        'Integer',
      ],
    ]);
    while ($result->fetch()) {
      foreach ($fieldsToFill as $field) {
        $params[$field] = !empty($params[$field]) ? $params[$field] : $result->$field;
      }
    }
    return $params;
  }

  /**
   * Specify defaults for creating a relationship.
   *
   * @return array
   *   array of defaults for creating relationship
   */
  public static function getdefaults() {
    return [
      'is_active' => 1,
      'is_permission_a_b' => self::NONE,
      'is_permission_b_a' => self::NONE,
      'description' => '',
      'start_date' => 'NULL',
      'case_id' => NULL,
      'end_date' => 'NULL',
    ];
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *
   * @deprecated
   * @return bool
   */
  public static function dataExists($params) {
    CRM_Core_Error::deprecatedFunctionWarning('obsolete');
    return (isset($params['contact_check']) && is_array($params['contact_check']));
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

    $relationshipType = [];
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
      $relTypes = CRM_Utils_Array::index(['name_a_b'], CRM_Core_PseudoConstant::relationshipType('name'));
      if (
        (isset($relTypes['Employee of']) && $relationship->relationship_type_id == $relTypes['Employee of']['id']) ||
        (isset($relTypes['Household Member of']) && $relationship->relationship_type_id == $relTypes['Household Member of']['id'])
      ) {
        $sharedContact = new CRM_Contact_DAO_Contact();
        $sharedContact->id = $relationship->contact_id_a;
        $sharedContact->find(TRUE);

        $employerRelTypeId = CRM_Contact_BAO_RelationshipType::getEmployeeRelationshipTypeID();
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
   * @return CRM_Contact_DAO_Relationship
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated
   */
  public static function del($id) {
    return static::deleteRecord(['id' => $id]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete' && $event->id) {
      self::clearCurrentEmployer($event->id, CRM_Core_Action::DELETE);
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'delete' && $event->id) {
      if (CRM_Core_Permission::access('CiviMember')) {
        // create $params array which isrequired to delete memberships
        // of the related contacts.
        $params = [
          'relationship_type_id' => "{$event->object->relationship_type_id}_a_b",
          'contact_check' => [$event->object->contact_id_b => 1],
        ];

        $ids = [];
        // calling relatedMemberships to delete the memberships of
        // related contacts.
        self::relatedMemberships($event->object->contact_id_a,
          $params,
          $ids,
          CRM_Core_Action::DELETE,
          FALSE
        );
      }
    }
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function disableEnableRelationship($id, $action, $params = [], $ids = [], $active = FALSE) {
    $relationship = self::clearCurrentEmployer($id, $action);

    if ($id && CRM_Core_Component::isEnabled('CiviMember')) {
      self::updateMembershipsByRelationship($params, $relationship, $action, $ids, $active);
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
   * $returns  returns the contact ids in the relationship
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
   * @deprecated
   *
   * @return string
   */
  public static function checkValidRelationship($params, $ids, $contactId) {
    $errors = '';
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
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
   *   An assoc array of name/value pairs.
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
  public static function checkDuplicateRelationship(array $params, int $id, int $contactId = 0, int $relationshipId = 0): bool {
    $relationshipTypeId = $params['relationship_type_id'] ?? NULL;
    [$type] = explode('_', $relationshipTypeId);

    $queryString = '
SELECT id
FROM   civicrm_relationship
WHERE  is_active = 1 AND relationship_type_id = ' . CRM_Utils_Type::escape($type, 'Integer');

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

    $dateFields = ['end_date', 'start_date'];
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
      ' AND ( ( contact_id_a = ' . CRM_Utils_Type::escape($id, 'Integer') .
      ' AND contact_id_b = ' . CRM_Utils_Type::escape($contactId, 'Integer') .
      ' ) OR ( contact_id_a = ' . CRM_Utils_Type::escape($contactId, 'Integer') .
      ' AND contact_id_b = ' . CRM_Utils_Type::escape($id, 'Integer') . " ) ) ";

    //if caseId is provided, include it duplicate checking.
    $caseId = $params['case_id'] ?? NULL;
    if ($caseId) {
      $queryString .= ' AND case_id = ' . CRM_Utils_Type::escape($caseId, 'Integer');
    }

    if ($relationshipId) {
      $queryString .= ' AND id !=' . CRM_Utils_Type::escape($relationshipId, 'Integer');
    }

    $relationship = CRM_Core_DAO::executeQuery($queryString);
    while ($relationship->fetch()) {
      // Check whether the custom field values are identical.
      if (self::checkDuplicateCustomFields($params['custom'] ?? [], $relationship->id)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * this function checks whether the values of the custom fields in $params are
   * the same as the values of the custom fields of the relation with given
   * $relationshipId.
   *
   * @param array $params an assoc array of name/value pairs
   * @param int $relationshipId ID of an existing duplicate relation
   *
   * @return boolean true if custom field values are identical
   * @access private
   * @static
   */
  private static function checkDuplicateCustomFields($params, $relationshipId) {
    // Get the custom values of the existing relationship.
    $existingValues = CRM_Core_BAO_CustomValueTable::getEntityValues($relationshipId, 'Relationship');
    // Create a similar array for the new relationship.
    $newValues = [];
    if (!is_array($params)) {
      // No idea when this would happen....
      CRM_Core_Error::deprecatedWarning('params should be an array');
    }
    else {
      // $params seems to be an array, as it should be. Each value is again an array.
      // This array contains one value (key -1), and this value seems to be
      // an array with the information about the custom value.
      foreach ($params as $value) {
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
   * @return bool
   *
   * @throws CRM_Core_Exception
   */
  public static function setIsActive($id, $is_active) {
    // as both the create & add functions have a bunch of logic in them that
    // doesn't seem to cope with a normal update we will call the api which
    // has tested handling for this
    // however, a longer term solution would be to simplify the add, create & api functions
    // to be more standard. It is debatable @ that point whether it's better to call the BAO
    // direct as the api is more tested.
    $result = civicrm_api('relationship', 'create', [
      'id' => $id,
      'is_active' => $is_active,
      'version' => 3,
    ]);

    if (is_array($result) && !empty($result['is_error']) && $result['error_message'] != 'Duplicate Relationship') {
      throw new CRM_Core_Exception($result['error_message'], $result['error_code'] ?? 'undefined', $result);
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
  public static function &getValues($params, &$values) {
    if (empty($params)) {
      return NULL;
    }
    $v = [];

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
   *
   * @throws \CRM_Core_Exception
   */
  public static function makeURLClause($contactId, $status, $numRelationship, $count, $relationshipId, $direction, $params = []) {
    $select = $from = $where = '';

    $select = '( ';
    if ($count) {
      if ($direction === 'a_b') {
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
                              civicrm_relationship.created_date as created_date,
                              civicrm_relationship.modified_date as modified_date,
                              civicrm_relationship.description as description,
                              civicrm_relationship.is_active as is_active,
                              civicrm_relationship.is_permission_a_b as is_permission_a_b,
                              civicrm_relationship.is_permission_b_a as is_permission_b_a,
                              civicrm_relationship.case_id as case_id';

      if ($direction === 'a_b') {
        $select .= ', civicrm_relationship_type.label_a_b as label_a_b,
                              civicrm_relationship_type.label_b_a as relation ';
      }
      else {
        $select .= ', civicrm_relationship_type.label_a_b as label_a_b,
                              civicrm_relationship_type.label_a_b as relation ';
      }
    }

    $from = '
      FROM  civicrm_relationship
INNER JOIN  civicrm_relationship_type ON ( civicrm_relationship.relationship_type_id = civicrm_relationship_type.id )
INNER JOIN  civicrm_contact ';
    if ($direction === 'a_b') {
      $from .= 'ON ( civicrm_contact.id = civicrm_relationship.contact_id_a ) ';
    }
    else {
      $from .= 'ON ( civicrm_contact.id = civicrm_relationship.contact_id_b ) ';
    }

    if (!$count) {
      $from .= '
LEFT JOIN  civicrm_address ON (civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1)
LEFT JOIN  civicrm_phone   ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary = 1)
LEFT JOIN  civicrm_email   ON (civicrm_email.contact_id = civicrm_contact.id AND civicrm_email.is_primary = 1)
LEFT JOIN  civicrm_state_province ON (civicrm_address.state_province_id = civicrm_state_province.id)
LEFT JOIN  civicrm_country ON (civicrm_address.country_id = civicrm_country.id)
';
    }

    $where = 'WHERE ( 1 )';
    if ($contactId) {
      if ($direction === 'a_b') {
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
    if ($direction === 'a_b') {
      $where .= ' ) UNION ';
    }
    else {
      $where .= ' ) ';
    }

    return [$select, $from, $where];
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function getRelationship(
    $contactId = NULL,
    $status = 0, $numRelationship = 0,
    $count = 0, $relationshipId = 0,
    $links = NULL, $permissionMask = NULL,
    $permissionedContact = FALSE,
    $params = [], $includeTotalCount = FALSE
  ) {
    $values = [];
    if (!$contactId && !$relationshipId) {
      return $values;
    }

    [$select1, $from1, $where1] = self::makeURLClause($contactId, $status, $numRelationship,
      $count, $relationshipId, 'a_b', $params
    );
    [$select2, $from2, $where2] = self::makeURLClause($contactId, $status, $numRelationship,
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

    $relationship = CRM_Core_DAO::executeQuery($queryString . $order . $limit);
    $row = [];
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
        $values[$rid]['created_date'] = $relationship->created_date;
        $values[$rid]['modified_date'] = $relationship->modified_date;
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
          $replace = [
            'id' => $rid,
            'rtype' => $values[$rid]['rtype'],
            'cid' => $contactId,
            'cbid' => $values[$rid]['cid'],
            'caseid' => $values[$rid]['case_id'],
            'clientid' => $contactId,
          ];

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
              $links[CRM_Core_Action::MAX_ACTION]['name'] = ts('Manage Case #%1', [1 => $values[$rid]['case_id']]);
              $links[CRM_Core_Action::MAX_ACTION]['class'] = 'no-popup';

              // Also make sure we have the right client cid since can get here from multiple relationship tabs.
              if ($values[$rid]['rtype'] == 'b_a') {
                $replace['clientid'] = $values[$rid]['cid'];
              }
              $values[$rid]['case'] = '<a href="' . CRM_Utils_System::url('civicrm/case/ajax/details', sprintf('caseId=%d&cid=%d&snippet=4', $values[$rid]['case_id'], $values[$rid]['cid'])) . '" class="action-item crm-hover-button crm-summary-link"><i class="crm-i fa-folder-open-o" aria-hidden="true"></i></a>';
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

      return $values;
    }
  }

  /**
   * Get list of relationship type based on the target contact type.
   * Both directions of relationships are included if their labels are not the same.
   *
   * @param string $targetContactType
   *   A valid contact type (may be Individual, Organization, Household).
   *
   * @return array
   *   array reference of all relationship types with context to current contact type.
   */
  public static function getRelationType($targetContactType) {
    $relationshipType = [];
    $allRelationshipType = CRM_Core_PseudoConstant::relationshipType();

    foreach ($allRelationshipType as $key => $type) {
      if ($type['contact_type_b'] == $targetContactType || empty($type['contact_type_b'])) {
        $relationshipType[$key . '_a_b'] = $type['label_a_b'];
      }
      if (($type['contact_type_a'] == $targetContactType || empty($type['contact_type_a']))
        && $type['label_a_b'] != $type['label_b_a']
      ) {
        $relationshipType[$key . '_b_a'] = $type['label_b_a'];
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
  public static function relatedMemberships($contactId, $params, $ids, $action = CRM_Core_Action::ADD, $active = TRUE) {
    // Check the end date and set the status of the relationship accordingly.
    $status = self::CURRENT;
    $targetContact = $params['contact_check'] ?? [];
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
      $targetContact = [$params['contact_id_b'] => 1];
    }

    if (($action & CRM_Core_Action::ADD) ||
      ($action & CRM_Core_Action::DELETE)
    ) {
      $contact = $contactId;
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      $contact = (int) $ids['contact'];
      $targetContact = [$ids['contactTarget'] => 1];
    }

    // Build the 'values' array for
    // 1. ContactA
    // 2. ContactB
    // This will allow us to check if either of the contacts in relationship have active memberships.

    $values = [];

    // 1. ContactA
    $values[$contact] = [
      'relatedContacts' => $targetContact,
      'relationshipTypeId' => $relTypeId,
      'relationshipTypeDirection' => $relDirection,
    ];
    // 2. ContactB
    if (!empty($targetContact)) {
      foreach ($targetContact as $cid => $donCare) {
        $values[$cid] = [
          'relatedContacts' => [$contact => 1],
          'relationshipTypeId' => $relTypeId,
        ];

        $relTypeParams = ['id' => $relTypeId];
        $relTypeValues = [];
        CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $relTypeValues);

        if (($relTypeValues['name_a_b'] ?? NULL) == ($relTypeValues['name_b_a'] ?? NULL)) {
          $values[$cid]['relationshipTypeDirection'] = '_a_b';
        }
        else {
          $values[$cid]['relationshipTypeDirection'] = ($relDirection == '_a_b') ? '_b_a' : '_a_b';
        }
      }
    }

    $deceasedStatusId = array_search('Deceased', CRM_Member_PseudoConstant::membershipStatus());

    $relationshipProcessor = new CRM_Member_Utils_RelationshipProcessor(array_keys($values), $active);
    foreach ($values as $cid => $details) {
      $relatedContacts = array_keys($details['relatedContacts'] ?? []);
      $mainRelatedContactId = reset($relatedContacts);

      foreach ($relationshipProcessor->getRelationshipMembershipsForContact((int) $cid) as $membershipId => $membershipValues) {
        $membershipInherittedFromContactID = NULL;
        if (!empty($membershipValues['owner_membership_id'])) {
          // @todo - $membership already has this now.
          // Use get not getsingle so that we get e-notice noise but not a fatal is the membership has already been deleted.
          $inheritedFromMembership = civicrm_api3('Membership', 'get', ['id' => $membershipValues['owner_membership_id'], 'sequential' => 1])['values'][0];
          $membershipInherittedFromContactID = (int) $inheritedFromMembership['contact_id'];
        }
        $relTypeIds = [];
        if ($action & CRM_Core_Action::DELETE) {
          // @todo don't return relTypeId here - but it seems to be used later in a cryptic way (hint cryptic is not a complement).
          [$relTypeId, $isDeletable] = self::isInheritedMembershipInvalidated($membershipValues, $values, $cid);
          if ($isDeletable) {
            CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipValues['owner_membership_id'], $membershipValues['contact_id']);
          }
          continue;
        }
        if (($action & CRM_Core_Action::UPDATE) &&
          ($status & self::PAST) &&
          ($membershipValues['owner_membership_id'])
        ) {
          // If relationship is PAST and action is UPDATE then delete the RELATED membership
          CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipValues['owner_membership_id'],
            $membershipValues['contact_id']
          );
          continue;
        }

        // add / edit the memberships for related
        // contacts.

        // @todo - all these lines get 'relTypeDirs' - but it's already a key in the $membership array.
        // Get the Membership Type Details.
        $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($membershipValues['membership_type_id']);
        // Check if contact's relationship type exists in membership type
        $relTypeDirs = [];
        if (!empty($membershipType['relationship_type_id'])) {
          $relTypeIds = (array) $membershipType['relationship_type_id'];
        }
        if (!empty($membershipType['relationship_direction'])) {
          $relDirections = (array) $membershipType['relationship_direction'];
        }
        foreach ($relTypeIds as $key => $value) {
          $relTypeDirs[] = $value . '_' . $relDirections[$key];
        }
        $relTypeDir = $details['relationshipTypeId'] . $details['relationshipTypeDirection'];
        if (in_array($relTypeDir, $relTypeDirs)) {
          // Check if relationship being created/updated is similar to that of membership type's relationship.

          $membershipValues['owner_membership_id'] = $membershipId;
          unset($membershipValues['id']);
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

            if (in_array($action, [CRM_Core_Action::UPDATE, CRM_Core_Action::ADD, CRM_Core_Action::ENABLE])) {
              //if updated relationship is already related to contact don't delete existing inherited membership
              if (in_array((int) $relatedContactId, $membershipValues['inheriting_contact_ids'], TRUE)
                || $relatedContactId === $membershipValues['owner_contact_id']
              ) {
                continue;
              }

              // delete the membership record for related contact before creating new membership record.
              CRM_Member_BAO_Membership::deleteRelatedMemberships($membershipId, $relatedContactId);
            }
            // skip status calculation for pay later memberships.
            if ('Pending' === CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membershipValues['status_id'])) {
              $membershipValues['skipStatusCal'] = TRUE;
            }
            // As long as the membership itself was not created by inheritance from the same contact
            // that stands to inherit the membership we add an inherited membership.
            if ($membershipInherittedFromContactID !== (int) $membershipValues['contact_id']) {
              $membershipValues = self::addInheritedMembership($membershipValues);
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
            $relIds = [$params['id']];
          }
          else {
            $relIds = $params['relationship_ids'] ?? NULL;
          }
          if (self::isRelatedMembershipExpired($relTypeIds, $contactId, $mainRelatedContactId, $relTypeId,
              $relIds) && !empty($membershipValues['owner_membership_id']
            ) && !empty($values[$mainRelatedContactId]['memberships'][$membershipValues['owner_membership_id']])) {
            $membershipValues['status_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', 'Expired', 'id', 'label');
            $type = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membershipValues['membership_type_id'], 'name', 'id');
            CRM_Member_BAO_Membership::add($membershipValues);
            CRM_Core_Session::setStatus(ts("Inherited membership %1 status was changed to Expired due to the change in relationship type.", [1 => $type]), ts('Record Updated'), 'alert');
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

    $relParamas = [
      1 => [$contactId, 'Integer'],
      2 => [$mainRelatedContactId, 'Integer'],
    ];

    if ($contactId == $mainRelatedContactId) {
      $recordsFound = (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $membershipTypeRelationshipTypeIDs) . " )  AND
contact_id_a IN ( %1 ) OR contact_id_b IN ( %1 ) AND id IN (" . implode(',', $relIds) . ")", $relParamas);
      if ($recordsFound) {
        return FALSE;
      }
      return TRUE;
    }

    return !CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_relationship WHERE relationship_type_id IN ( " . implode(',', $membershipTypeRelationshipTypeIDs) . " ) AND contact_id_a IN ( %1, %2 ) AND contact_id_b IN ( %1, %2 ) AND id NOT IN (" . implode(',', $relIds) . ")", $relParamas);
  }

  /**
   * Get Current Employer for Contact.
   *
   * @param array $contactIds
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
    $currentEmployer = [];
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
    $contacts = [];
    $args = [1 => [$contactID, 'Integer']];
    $relationshipTypeClause = $contactTypeClause = '';

    if ($relTypeId) {
      // @todo relTypeId is only ever passed in as an int. Change this to reflect that -
      // probably being overly conservative by not doing so but working on stable release.
      $relationshipTypeClause = 'AND cr.relationship_type_id IN (%2) ';
      $args[2] = [$relTypeId, 'String'];
    }

    if ($contactType) {
      $contactTypeClause = ' AND cr.relationship_type_id = crt.id AND crt.contact_type_b = %3 ';
      $args[3] = [$contactType, 'String'];
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
      $contacts[$dao->id] = [
        'name' => $dao->name,
        'value' => $dao->id,
      ];
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
      WHERE (contact_id_a = $mainId AND contact_id_b = $otherId AND case_id IS NULL)
         OR (contact_id_b = $mainId AND contact_id_a = $otherId AND case_id IS NULL)";

    // Delete relationship from other contact if main contact already has that relationship
    $sqls[] = "DELETE r2
      FROM civicrm_relationship r1, civicrm_relationship r2
      WHERE r1.relationship_type_id = r2.relationship_type_id
      AND r1.id <> r2.id
      AND r1.case_id IS NULL AND r2.case_id IS NULL
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
   * @throws \CRM_Core_Exception
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
   * @param string $direction
   *
   * @return array|void
   * @throws \CRM_Core_Exception
   */
  public static function membershipTypeToRelationshipTypes(&$params, $direction = NULL) {
    $membershipType = civicrm_api3('membership_type', 'getsingle', [
      'id' => $params['membership_type_id'],
      'return' => 'relationship_type_id, relationship_direction',
    ]);
    $relationshipTypes = $membershipType['relationship_type_id'];
    if (empty($relationshipTypes)) {
      return NULL;
    }
    // if we don't have any contact data we can only filter on type
    if (empty($params['contact_id']) && empty($params['contact_id_a']) && empty($params['contact_id_a'])) {
      $params['relationship_type_id'] = ['IN' => $relationshipTypes];
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
        $params['relationship_type_id'] = ['IN' => $types];
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
   * @deprecated since 5.68. Will be removed around 5.74.
   *
   * Only-used-by-user-dashboard.
   */
  public static function getContactRelationshipSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;

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
    $permissions = [$page->_permission];
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

    $contactRelationships = [];
    $params['total'] = $relationships['total_relationships'];
    unset($relationships['total_relationships']);
    if (!empty($relationships)) {

      $displayName = CRM_Contact_BAO_Contact::displayName($params['contact_id']);

      // format params
      foreach ($relationships as $relationshipId => $values) {
        $relationship = [];

        $relationship['DT_RowId'] = $values['id'];
        $relationship['DT_RowClass'] = 'crm-entity';
        if ($values['is_active'] == 0) {
          $relationship['DT_RowClass'] .= ' disabled';
        }

        $relationship['DT_RowAttr'] = [];
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

        $relationship['relation'] = ($values['case'] ?? '') . CRM_Utils_System::href(
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

    $columnHeaders = self::getColumnHeaders();
    $selector = NULL;
    CRM_Utils_Hook::searchColumns('relationship.rows', $columnHeaders, $contactRelationships, $selector);

    $relationshipsDT = [];
    $relationshipsDT['data'] = $contactRelationships;
    $relationshipsDT['recordsTotal'] = $params['total'];
    $relationshipsDT['recordsFiltered'] = $params['total'];

    return $relationshipsDT;
  }

  /**
   * @deprecated since 5.68. Will be removed around 5.74.
   *
   * Only-used-by-user-dashboard.
   */
  public static function getColumnHeaders() {
    return [
      'relation' => [
        'name' => ts('Relationship'),
        'sort' => 'relation',
        'direction' => CRM_Utils_Sort::ASCENDING,
      ],
      'sort_name' => [
        'name' => '',
        'sort' => 'sort_name',
        'direction' => CRM_Utils_Sort::ASCENDING,
      ],
      'start_date' => [
        'name' => ts('Start'),
        'sort' => 'start_date',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'end_date' => [
        'name' => ts('End'),
        'sort' => 'end_date',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'city' => [
        'name' => ts('City'),
        'sort' => 'city',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'state' => [
        'name' => ts('State/Prov'),
        'sort' => 'state',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'email' => [
        'name' => ts('Email'),
        'sort' => 'email',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'phone' => [
        'name' => ts('Phone'),
        'sort' => 'phone',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
      'links' => [
        'name' => '',
        'sort' => 'links',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ],
    ];
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    // Quickform-specific format, for use when editing relationship type options in a popup from the contact relationship form
    if ($fieldName === 'relationship_type_id' && !empty($props['is_form'])) {
      return self::getContactRelationshipType(
        $props['contact_id'] ?? NULL,
        $props['relationship_direction'] ?? 'a_b',
        $props['relationship_id'] ?? NULL,
        $props['contact_type'] ?? NULL
      );
    }

    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * Process the params from api, form and check if current
   * employer should be set or unset.
   *
   * @param array $params
   * @param int $relationshipId
   * @param int|null $updatedRelTypeID
   *
   * @return bool
   *   TRUE if current employer needs to be cleared.
   * @throws \CRM_Core_Exception
   */
  public static function isCurrentEmployerNeedingToBeCleared($params, $relationshipId, $updatedRelTypeID = NULL) {
    $existingTypeID = (int) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Relationship', $relationshipId, 'relationship_type_id');
    $updatedRelTypeID = $updatedRelTypeID ?: $existingTypeID;
    $currentEmployerID = (int) civicrm_api3('Contact', 'getvalue', ['return' => 'current_employer_id', 'id' => $params['contact_id_a']]);

    if ($currentEmployerID !== (int) $params['contact_id_b'] || !self::isRelationshipTypeCurrentEmployer($existingTypeID)) {
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
      // If there are no other active employer relationships between the same 2 contacts...
      if (!civicrm_api3('Relationship', 'getcount', [
        'is_active' => 1,
        'relationship_type_id' => $existingTypeID,
        'id' => ['<>' => $params['id']],
        'contact_id_a' => $params['contact_id_a'],
        'contact_id_b' => $params['contact_id_b'],
      ])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Is this a current employer relationship type.
   *
   * @todo - this could use cached pseudoconstant lookups.
   *
   * @param int $existingTypeID
   *
   * @return bool
   */
  private static function isRelationshipTypeCurrentEmployer(int $existingTypeID): bool {
    $isCurrentEmployerRelationshipType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $existingTypeID, 'name_b_a') === 'Employer of';
    return $isCurrentEmployerRelationshipType;
  }

  /**
   * Is the inherited relationship invalidated by this relationship change.
   *
   * @param array $membershipValues
   * @param array $values
   * @param int $cid
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function isInheritedMembershipInvalidated($membershipValues, array $values, $cid): array {
    // @todo most of this can go - it's just the weird historical returning of $relTypeId that it does.
    // now we have caching the parent fn can just call CRM_Member_BAO_MembershipType::getMembershipType
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($membershipValues['membership_type_id']);
    $relTypeIds = $membershipType['relationship_type_id'];
    $membershipInheritedFrom = $membershipValues['owner_membership_id'] ?? NULL;
    if (!$membershipInheritedFrom || !in_array($values[$cid]['relationshipTypeId'], $relTypeIds)) {
      return [implode(',', $relTypeIds), FALSE];
    }
    //CRM-16300 check if owner membership exist for related membership
    return [implode(',', $relTypeIds), !self::isContactHasValidRelationshipToInheritMembershipType((int) $cid, (int) $membershipValues['membership_type_id'], (int) $membershipValues['owner_membership_id'])];
  }

  /**
   * Is there a valid relationship confering this membership type on this contact.
   *
   * @param int $contactID
   * @param int $membershipTypeID
   * @param int $parentMembershipID
   *   Id of the membership being inherited.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  private static function isContactHasValidRelationshipToInheritMembershipType(int $contactID, int $membershipTypeID, int $parentMembershipID): bool {
    $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID);

    $existingRelationships = Relationship::get(FALSE)
      ->addWhere('relationship_type_id', 'IN', $membershipType['relationship_type_id'])
      ->addClause('OR', ['contact_id_a', '=', $contactID], ['contact_id_b', '=', $contactID])
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->indexBy('id')
      ->getArrayCopy();

    if (empty($existingRelationships)) {
      return FALSE;
    }

    $membershipInheritedFromContactID = (int) civicrm_api3('Membership', 'getvalue', ['return' => 'contact_id', 'id' => $parentMembershipID]);
    // I don't think the api can correctly filter by start & end because of handling for NULL
    // so we filter them out here.
    foreach ($existingRelationships as $index => $existingRelationship) {
      $otherContactID = (int) (($contactID === (int) $existingRelationship['contact_id_a']) ? $existingRelationship['contact_id_b'] : $existingRelationship['contact_id_a']);
      if (!empty($existingRelationship['start_date'])
        && strtotime($existingRelationship['start_date']) > time()
      ) {
        unset($existingRelationships[$index]);
        continue;
      }
      if (!empty($existingRelationship['end_date'])
        && strtotime($existingRelationship['end_date']) < time()
      ) {
        unset($existingRelationships[$index]);
        continue;
      }
      if ($membershipInheritedFromContactID !== $otherContactID
      ) {
        // This is a weird scenario - they have been inheriting the  membership
        // just not from this relationship - and some max_related calcs etc would be required
        // - ie because they are no longer inheriting from this relationship's 'allowance'
        // and now are inheriting from the other relationships  'allowance', if it has not
        // already hit 'max_related'
        // For now ignore here & hope it's handled elsewhere - at least that's consistent with
        // before this function was added.
        unset($existingRelationships[$index]);
        continue;
      }
      if (!civicrm_api3('Contact', 'getcount', ['id' => $otherContactID, 'is_deleted' => 0])) {
        // Can't inherit from a deleted contact.
        unset($existingRelationships[$index]);
        continue;
      }
    }
    return !empty($existingRelationships);
  }

  /**
   * Add an inherited membership, provided max related not exceeded.
   *
   * @param array $membershipValues
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function addInheritedMembership($membershipValues) {
    $query = "
SELECT count(*)
  FROM civicrm_membership
    LEFT JOIN civicrm_membership_status ON (civicrm_membership_status.id = civicrm_membership.status_id)
 WHERE membership_type_id = {$membershipValues['membership_type_id']}
           AND owner_membership_id = {$membershipValues['owner_membership_id']}
    AND is_current_member = 1";
    $result = CRM_Core_DAO::singleValueQuery($query);
    if ($result < ($membershipValues['max_related'] ?? PHP_INT_MAX)) {
      // Convert custom_xx_id fields to custom_xx
      // See https://lab.civicrm.org/dev/membership/-/issues/37
      // This makes sure the value is copied and not the looked up value.
      // Which is the case when the custom field is a contact reference field.
      // The custom_xx contains the display name of the contact, instead of the contact id.
      // The contact id is then available in custom_xx_id.
      foreach ($membershipValues as $field => $value) {
        if (stripos($field, 'custom_') !== 0) {
          // No a custom field
          continue;
        }
        $custom_id = substr($field, 7);
        if (substr($custom_id, -3) === '_id') {
          $custom_id = substr($custom_id, 0, -3);
          unset($membershipValues[$field]);
          $membershipValues['custom_' . $custom_id] = $value;
        }
      }

      civicrm_api3('Membership', 'create', $membershipValues);
    }
    return $membershipValues;
  }

  /**
   * @param array $params
   * @param \CRM_Contact_DAO_Relationship $relationship
   * @param int $action
   * @param array $ids
   * @param bool $active
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected static function updateMembershipsByRelationship(array $params, CRM_Contact_DAO_Relationship $relationship, int $action, array $ids, bool $active): void {
    // create $params array which is required to delete memberships
    // of the related contacts.
    if (empty($params)) {
      $params = [
        'relationship_type_id' => "{$relationship->relationship_type_id}_a_b",
        'contact_check' => [$relationship->contact_id_b => 1],
      ];
    }
    $contact_id_a = empty($params['contact_id_a']) ? $relationship->contact_id_a : $params['contact_id_a'];

    // Check if relationship can be used for related memberships
    $membershipTypes = MembershipType::get(FALSE)
      ->addSelect('relationship_type_id')
      ->addGroupBy('relationship_type_id')
      ->addWhere('relationship_type_id', 'IS NOT EMPTY')
      ->execute();
    foreach ($membershipTypes as $membershipType) {
      // We have to loop through them because relationship_type_id is an array and we can't filter by a single
      // relationship id using API.
      if (in_array($relationship->relationship_type_id, $membershipType['relationship_type_id'])) {
        $relationshipIsUsedForRelatedMemberships = TRUE;
      }
    }
    if (empty($relationshipIsUsedForRelatedMemberships)) {
      // This relationship is not configured for any related membership types
      return;
    }
    // Call relatedMemberships to delete/add the memberships of related contacts.
    if ($action & CRM_Core_Action::DISABLE) {
      // @todo this could call a subset of the function that just relates to
      // cleaning up no-longer-inherited relationships
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

  /**
   * Check related contact access.
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    $delegateAction = $e->getActionName() === 'get' ? 'get' : 'update';

    // Delegate relationship permissions to contacts a & b
    foreach (['a', 'b'] as $ab) {
      if (empty($record["contact_id_$ab"]) && !empty($record['id'])) {
        $record["contact_id_$ab"] = CRM_Core_DAO::getFieldValue(__CLASS__, $record['id'], "contact_id_$ab");
      }
      if (!empty($record["contact_id_$ab"]) && !\Civi\Api4\Utils\CoreUtil::checkAccessDelegated('Contact', $delegateAction, ['id' => $record["contact_id_$ab"]], $userID)) {
        $e->setAuthorized(FALSE);
        break;
      }
    }
  }

}
