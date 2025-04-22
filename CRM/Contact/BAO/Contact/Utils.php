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

use Civi\Api4\Contact;
use Civi\Api4\Relationship;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_Contact_Utils {

  /**
   * Given a contact type or sub_type(s), generate markup for the contact type icon.
   *
   * @param string $contactTypes
   *   Contact type.
   * @param bool $urlOnly
   *   If we need to return only image url.
   * @param int $contactId
   *   Contact id.
   * @param bool $addProfileOverlay
   *   If profile overlay class should be added.
   * @param string $contactUrl
   *   URL to the contact page. Defaults to civicrm/contact/view
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getImage($contactTypes, $urlOnly = FALSE, $contactId = NULL, $addProfileOverlay = TRUE, $contactUrl = NULL) {
    // Ensure string data is unserialized
    $contactTypes = CRM_Utils_Array::explodePadded($contactTypes);

    $allContactTypeInfo = \CRM_Contact_BAO_ContactType::getAllContactTypes();

    $imageInfo = ['url' => NULL, 'image' => NULL];

    foreach ($contactTypes as $contactType) {
      $typeInfo = $allContactTypeInfo[$contactType];
      // Prefer the first type/subtype with an icon
      if (!empty($typeInfo['icon'])) {
        break;
      }

      // Fall back to using image_URL if no subtypes have an icon
      if (!empty($typeInfo['image_URL'])) {
        $imageUrl = $typeInfo['image_URL'];

        if (!preg_match("/^(\/|(http(s)?:)).+$/i", $imageUrl)) {
          $imageUrl = CRM_Core_Config::singleton()->resourceBase . $imageUrl;
        }
        $imageInfo['image'] = "<div class=\"icon crm-icon {$typeInfo['name']}-icon\" style=\"background: url('{$imageUrl}')\" title=\"{$contactType}\"></div>";
        $imageInfo['url'] = $imageUrl;
      }
    }

    // If subtype doesn't have an image or an icon, use the parent type
    if (empty($imageUrl) && empty($typeInfo['icon']) && !empty($typeInfo['parent'])) {
      $typeInfo = $allContactTypeInfo[$typeInfo['parent']];
    }

    // Prefer icon over image
    if (!empty($typeInfo['icon'])) {
      // do not add title since it hides contact name
      $title = $addProfileOverlay ? '' : htmlspecialchars($typeInfo['label']);
      $imageInfo['image'] = '<i class="crm-i fa-fw ' . $typeInfo['icon'] . '" title="' . $title . '"></i>';
    }

    if ($addProfileOverlay) {
      static $summaryOverlayProfileId = NULL;
      if (!$summaryOverlayProfileId) {
        $summaryOverlayProfileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', 'summary_overlay', 'id', 'name');
      }

      $contactURL = $contactUrl ?: CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$contactId}"
      );
      $profileURL = CRM_Utils_System::url('civicrm/profile/view',
        "reset=1&gid={$summaryOverlayProfileId}&id={$contactId}&snippet=4&is_show_email_task=1"
      );

      $imageInfo['summary-link'] = '<a href="' . $contactURL . '" data-tooltip-url="' . $profileURL . '" class="crm-summary-link" aria-labelledby="crm-contactname-content">' . $imageInfo['image'] . '</a>';
    }
    else {
      $imageInfo['summary-link'] = $imageInfo['image'];
    }

    return $urlOnly ? $imageInfo['url'] : $imageInfo['summary-link'];
  }

  /**
   * Function check for mix contact ids(individual+household etc...)
   *
   * @param array $contactIds
   *   Array of contact ids.
   *
   * @return bool
   *   true if mix contact array else false
   *
   */
  public static function checkContactType(&$contactIds) {
    if (empty($contactIds)) {
      return FALSE;
    }

    $idString = implode(',', $contactIds);
    $query = "
SELECT count( DISTINCT contact_type )
FROM   civicrm_contact
WHERE  id IN ( $idString )
";
    $count = CRM_Core_DAO::singleValueQuery($query);
    return $count > 1;
  }

  /**
   * Generate a checksum for a $entityId of type $entityType
   *
   * @param int $entityId
   * @param int $ts
   *   Timestamp that checksum was generated.
   * @param int $live
   *   Life of this checksum in hours/ 'inf' for infinite.
   * @param string $hash
   *   Contact hash, if sent, prevents a query in inner loop.
   * @param string $entityType
   * @param int|null $hashSize
   *
   * @return string
   *   (Underscore separated: $cs, $ts, $live )
   * @throws \CRM_Core_Exception
   */
  public static function generateChecksum($entityId, $ts = NULL, $live = NULL, $hash = NULL, $entityType = 'contact', $hashSize = NULL) {
    // return a warning message if we dont get a entityId
    // this typically happens when we do a message preview
    // or an anon mailing view - CRM-8298
    if (!$entityId) {
      return 'invalidChecksum';
    }

    if (!$hash) {
      if ($entityType === 'contact') {
        $hash = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $entityId, 'hash'
        );
      }
      elseif ($entityType === 'mailing') {
        $hash = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing',
          $entityId, 'hash'
        );
      }
    }

    if (!$hash) {
      // Ensure we cannot generate numeric hashes
      // to avoid breaking things elsewhere
      // See lab issue #5541
      do {
        $hash = bin2hex(random_bytes(16));
        if ($hashSize) {
          $hash = substr($hash, 0, $hashSize);
        }
      } while (is_numeric($hash));

      if ($entityType == 'contact') {
        CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact',
          $entityId,
          'hash', $hash
        );
      }
      elseif ($entityType == 'mailing') {
        CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Mailing',
          $entityId,
          'hash', $hash
        );
      }
    }

    if (!$ts) {
      $ts = CRM_Utils_Time::time();
    }

    if (!$live) {
      $days = Civi::settings()->get('checksum_timeout');
      $live = 24 * $days;
    }

    $cs = md5("{$hash}_{$entityId}_{$ts}_{$live}");
    return "{$cs}_{$ts}_{$live}";
  }

  /**
   * Make sure the checksum is valid for the passed in contactID.
   *
   * @param int $contactID
   * @param string $inputCheck
   *   Checksum to match against.
   *
   * @return bool
   *   true if valid, else false
   *
   * @throws \CRM_Core_Exception
   */
  public static function validChecksum($contactID, $inputCheck) {
    // Allow a hook to invalidate checksums
    $invalid = FALSE;
    CRM_Utils_Hook::invalidateChecksum($contactID, $inputCheck, $invalid);
    if ($invalid) {
      return FALSE;
    }

    $input = CRM_Utils_System::explode('_', $inputCheck, 3);

    $inputCS = $input[0] ?? NULL;
    $inputTS = $input[1] ?? NULL;
    $inputLF = $input[2] ?? NULL;

    $check = self::generateChecksum($contactID, $inputTS, $inputLF);
    // Joomla_11 - If $inputcheck is null without explicitly casting to a string
    // you get an error.
    if (!hash_equals($check, (string) $inputCheck)) {
      return FALSE;
    }

    // no life limit for checksum
    if ($inputLF == 'inf') {
      return TRUE;
    }

    // checksum matches so now check timestamp
    $now = time();
    return ($inputTS + ($inputLF * 60 * 60) >= $now);
  }

  /**
   * Create Current employer relationship for a individual.
   *
   * @param int $contactID
   *   Contact id of the individual.
   * @param int|string $employerIDorName
   *   (id or name).
   * @param int|null $previousEmployerID
   * @param bool $newContact
   *
   * @throws \CRM_Core_Exception
   */
  public static function createCurrentEmployerRelationship($contactID, $employerIDorName, $previousEmployerID = NULL, $newContact = FALSE): void {
    if (!$employerIDorName) {
      // This function is not called in core with no organization & should not be
      // Refs CRM-15368,CRM-15547
      CRM_Core_Error::deprecatedWarning('calling this function with no organization is deprecated');
      return;
    }
    if (is_numeric($employerIDorName)) {
      $employerID = $employerIDorName;
    }
    else {
      $employerName = $employerIDorName;
      $dupeIDs = CRM_Contact_BAO_Contact::getDuplicateContacts(['organization_name' => $employerName], 'Organization', 'Unsupervised', [], FALSE);
      if (!empty($dupeIDs)) {
        $employerID = (int) (reset($dupeIDs));
      }
      else {
        $contact = \Civi\Api4\Contact::get(FALSE)
          ->addSelect('employer_id.organization_name', 'employer_id')
          ->addWhere('id', '=', $contactID)
          ->execute()->first();
        if ($contact && (mb_strtolower($contact['employer_id.organization_name']) === mb_strtolower($employerName))) {
          $employerID = $contact['employer_id'];
        }
        else {
          $employerID = Contact::create(FALSE)
            ->setValues([
              'contact_type' => 'Organization',
              'organization_name' => $employerName,
            ])->execute()->first()['id'];
        }
      }
    }

    $relationshipTypeID = CRM_Contact_BAO_RelationshipType::getEmployeeRelationshipTypeID();
    if (!CRM_Contact_BAO_Contact::getContactType($contactID) || !CRM_Contact_BAO_Contact::getContactType($employerID)) {
      // There doesn't seem to be any reason to think this would ever be true but there
      // was a previous more complicated check.
      CRM_Core_Error::deprecatedWarning('attempting to create an employer with invalid contact types is deprecated');
      return;
    }

    $relationshipIds = [];
    $ids = [];
    $action = CRM_Core_Action::ADD;
    $existingRelationship = Relationship::get(FALSE)
      ->setWhere([
        ['contact_id_a', '=', $contactID],
        ['contact_id_b', '=', $employerID],
        ['OR', [['start_date', '<=', 'now'], ['start_date', 'IS EMPTY']]],
        ['OR', [['end_date', '>=', 'now'], ['end_date', 'IS EMPTY']]],
        ['relationship_type_id', '=', $relationshipTypeID],
        ['is_active', 'IN', [0, 1]],
      ])
      ->setSelect(['id', 'is_active', 'start_date', 'end_date', 'contact_id_a.employer_id', 'contact_id_a.organization_name', 'contact_id_b.organization_name'])
      ->addOrderBy('is_active', 'DESC')
      ->setLimit(1)
      ->execute()->first();

    if (!empty($existingRelationship)) {
      if ($existingRelationship['is_active']) {
        if ($existingRelationship['contact_id_a.organization_name'] !== $existingRelationship['contact_id_b.organization_name']) {
          self::setCurrentEmployer([$contactID => $employerID]);
        }
        // My work here is done.
        return;
      }

      $action = CRM_Core_Action::UPDATE;
      // No idea why we set these ids but it's either legacy cruft or used by `relatedMemberships`
      $ids['contact'] = $contactID;
      $ids['contactTarget'] = $employerID;
      $ids['relationship'] = $existingRelationship['id'];
      CRM_Contact_BAO_Relationship::setIsActive($existingRelationship['id'], TRUE);
    }
    else {
      $params = [
        'is_active' => TRUE,
        'contact_check' => [$employerID => TRUE],
        'contact_id_a' => $contactID,
        'contact_id_b' => $employerID,
        'relationship_type_id' => $relationshipTypeID,
      ];
      $relationship = CRM_Contact_BAO_Relationship::add($params);
      CRM_Contact_BAO_Relationship::addRecent($params, $relationship);
      $relationshipIds = [$relationship->id];
    }

    // In case we change employer, clean previous employer related records.
    if (!$previousEmployerID && !$newContact) {
      $previousEmployerID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id');
    }
    if ($previousEmployerID &&
      $previousEmployerID != $employerID
    ) {
      self::clearCurrentEmployer($contactID, $previousEmployerID);
    }

    // set current employer
    self::setCurrentEmployer([$contactID => $employerID]);

    //need to handle related memberships. CRM-3792
    // @todo - this probably duplicates the work done in the setIsActive
    // for duplicates...
    if ($previousEmployerID != $employerID) {
      CRM_Contact_BAO_Relationship::relatedMemberships($contactID, [
        'relationship_ids' => $relationshipIds,
        'is_active' => 1,
        'contact_check' => [$employerID => TRUE],
        'relationship_type_id' => $relationshipTypeID . '_a_b',
      ], $ids, $action);
    }
  }

  /**
   * Create related memberships for current employer.
   *
   * @param int $contactID
   *   Contact id of the individual.
   * @param int $employerID
   *   Contact id of the organization.
   * @param array $relationshipParams
   *   Relationship params.
   * @param bool $duplicate
   *   Are we triggered existing relationship.
   *
   * @param int $previousEmployerID
   *
   * @throws \CRM_Core_Exception
   */
  private static function currentEmployerRelatedMembership($contactID, $employerID, $relationshipParams, $duplicate = FALSE, $previousEmployerID = NULL) {

  }

  /**
   * Set current employer id and organization name.
   *
   * @param array $currentEmployerParams
   *   Associated array of contact id and its employer id.
   */
  public static function setCurrentEmployer($currentEmployerParams) {
    foreach ($currentEmployerParams as $contactId => $orgId) {
      $query = "UPDATE civicrm_contact contact_a,civicrm_contact contact_b
SET contact_a.employer_id=contact_b.id, contact_a.organization_name=contact_b.organization_name
WHERE contact_a.id ={$contactId} AND contact_b.id={$orgId}; ";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * Update cached current employer name.
   *
   * @param int $organizationId
   *   Current employer id.
   */
  public static function updateCurrentEmployer($organizationId) {
    $query = "UPDATE civicrm_contact contact_a,civicrm_contact contact_b
SET contact_a.organization_name=contact_b.organization_name
WHERE contact_a.employer_id=contact_b.id AND contact_b.id={$organizationId}; ";

    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Clear cached current employer name.
   *
   * @param int $contactId
   *   Contact id ( mostly individual contact id).
   * @param int $employerId
   *   Contact id ( mostly organization contact id).
   *
   * @throws \CRM_Core_Exception
   */
  public static function clearCurrentEmployer($contactId, $employerId = NULL) {
    $query = "UPDATE civicrm_contact
SET organization_name=NULL, employer_id = NULL
WHERE id={$contactId}; ";

    $dao = CRM_Core_DAO::executeQuery($query);

    // need to handle related memberships. CRM-3792
    if ($employerId) {
      //1. disable corresponding relationship.
      //2. delete related membership.

      //get the relationship type id of "Employee of"
      $relTypeId = CRM_Contact_BAO_RelationshipType::getEmployeeRelationshipTypeID();
      $relMembershipParams['relationship_type_id'] = $relTypeId . '_a_b';
      $relMembershipParams['contact_check'][$employerId] = 1;

      //get relationship id.
      if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($relMembershipParams, (int) $contactId, (int) $employerId)) {
        $relationship = new CRM_Contact_DAO_Relationship();
        $relationship->contact_id_a = $contactId;
        $relationship->contact_id_b = $employerId;
        $relationship->relationship_type_id = $relTypeId;

        if ($relationship->find(TRUE)) {
          CRM_Contact_BAO_Relationship::setIsActive($relationship->id, FALSE);
          CRM_Contact_BAO_Relationship::relatedMemberships($contactId, $relMembershipParams,
            $ids = [],
            CRM_Core_Action::DELETE
          );
        }
      }
    }
  }

  /**
   * Build form for related contacts / on behalf of organization.
   *
   * @param CRM_Core_Form $form
   * @param string $contactType
   *   contact type.
   * @param int $countryID
   * @param int $stateID
   * @param string $title
   *   fieldset title.
   *
   * @deprecated since 5.74 will be removed around 5.80
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildOnBehalfForm(&$form, $contactType, $countryID, $stateID, $title) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $form->assign('contact_type', $contactType);
    $form->assign('fieldSetTitle', $title);
    $form->assign('contactEditMode', TRUE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');
    if ($form->_contactId) {
      $form->assign('orgId', $form->_contactId);
    }

    switch ($contactType) {
      case 'Organization':
        $form->add('text', 'organization_name', ts('Organization Name'), $attributes['organization_name'], TRUE);
        break;

      case 'Household':
        $form->add('text', 'household_name', ts('Household Name'), $attributes['household_name']);
        break;

      default:
        // individual
        $form->addElement('select', 'prefix_id', ts('Prefix'),
          ['' => ts('- prefix -')] + CRM_Contact_DAO_Contact::buildOptions('prefix_id')
        );
        $form->addElement('text', 'first_name', ts('First Name'),
          $attributes['first_name']
        );
        $form->addElement('text', 'middle_name', ts('Middle Name'),
          $attributes['middle_name']
        );
        $form->addElement('text', 'last_name', ts('Last Name'),
          $attributes['last_name']
        );
        $form->addElement('select', 'suffix_id', ts('Suffix'),
          ['' => ts('- suffix -')] + CRM_Contact_DAO_Contact::buildOptions('suffix_id')
        );
    }

    $addressSequence = CRM_Utils_Address::sequence(\Civi::settings()->get('address_format'));
    $form->assign('addressSequence', array_fill_keys($addressSequence, 1));

    //Primary Phone
    $form->addElement('text',
      'phone[1][phone]',
      ts('Primary Phone'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone',
        'phone'
      )
    );
    //Primary Email
    $form->addElement('text',
      'email[1][email]',
      ts('Primary Email'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email',
        'email'
      )
    );
    //build the address block
    CRM_Contact_Form_Edit_Address::buildQuickForm($form);
  }

  /**
   * Clear cache employer name and employer id
   * of all employee when employer get deleted.
   *
   * @param int $employerId
   *   Contact id of employer ( organization id ).
   */
  public static function clearAllEmployee($employerId) {
    $query = "
UPDATE civicrm_contact
   SET organization_name=NULL, employer_id = NULL
 WHERE employer_id={$employerId}; ";

    $dao = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Given an array of contact ids this function will return array with links to view contact page.
   *
   * @param array $contactIDs
   *   Associated contact id's.
   * @param bool $addViewLink
   * @param bool $addEditLink
   * @param int $originalId
   *   Associated with the contact which is edited.
   *
   *
   * @return array
   *   returns array with links to contact view
   */
  public static function formatContactIDSToLinks($contactIDs, $addViewLink = TRUE, $addEditLink = TRUE, $originalId = NULL) {
    $contactLinks = [];
    if (!is_array($contactIDs) || empty($contactIDs)) {
      return $contactLinks;
    }

    // does contact has sufficient permissions.
    $permissions = [
      'view' => 'view all contacts',
      'edit' => 'edit all contacts',
      'merge' => 'merge duplicate contacts',
    ];

    $permissionedContactIds = [];
    foreach ($permissions as $task => $permission) {
      // give permission.
      if (CRM_Core_Permission::check($permission)) {
        foreach ($contactIDs as $contactId) {
          $permissionedContactIds[$contactId][$task] = TRUE;
        }
        continue;
      }

      // check permission on acl basis.
      if (in_array($task, ['view', 'edit'])) {
        $aclPermission = CRM_Core_Permission::VIEW;
        if ($task == 'edit') {
          $aclPermission = CRM_Core_Permission::EDIT;
        }
        foreach ($contactIDs as $contactId) {
          if (CRM_Contact_BAO_Contact_Permission::allow($contactId, $aclPermission)) {
            $permissionedContactIds[$contactId][$task] = TRUE;
          }
        }
      }
    }

    // retrieve display names for all contacts
    $query = '
   SELECT  c.id, c.display_name, c.contact_type, ce.email
     FROM  civicrm_contact c
LEFT JOIN  civicrm_email ce ON ( ce.contact_id=c.id AND ce.is_primary = 1 )
    WHERE  c.id IN  (' . implode(',', $contactIDs) . ' ) LIMIT 20';

    $dao = CRM_Core_DAO::executeQuery($query);

    $contactLinks['msg'] = NULL;
    $i = 0;
    while ($dao->fetch()) {

      $contactLinks['rows'][$i]['display_name'] = $dao->display_name;
      $contactLinks['rows'][$i]['primary_email'] = $dao->email;

      // get the permission for current contact id.
      $hasPermissions = $permissionedContactIds[$dao->id] ?? NULL;
      if (!is_array($hasPermissions) || empty($hasPermissions)) {
        $i++;
        continue;
      }

      // do check for view.
      if (array_key_exists('view', $hasPermissions)) {
        $contactLinks['rows'][$i]['view'] = '<a class="action-item" href="' . CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $dao->id) . '" target="_blank">' . ts('View') . '</a>';
        if (!$contactLinks['msg']) {
          $contactLinks['msg'] = 'view';
        }
      }
      if (array_key_exists('edit', $hasPermissions)) {
        $contactLinks['rows'][$i]['edit'] = '<a class="action-item" href="' . CRM_Utils_System::url('civicrm/contact/add', 'reset=1&action=update&cid=' . $dao->id) . '" target="_blank">' . ts('Edit') . '</a>';
        if (!$contactLinks['msg'] || $contactLinks['msg'] != 'merge') {
          $contactLinks['msg'] = 'edit';
        }
      }
      if (!empty($originalId) && array_key_exists('merge', $hasPermissions)) {
        $rgBao = new CRM_Dedupe_BAO_DedupeRuleGroup();
        $rgBao->contact_type = $dao->contact_type;
        $rgBao->used = 'Supervised';
        if ($rgBao->find(TRUE)) {
          $rgid = $rgBao->id;
        }
        if ($rgid && isset($dao->id)) {
          //get an url to merge the contact
          $contactLinks['rows'][$i]['merge'] = '<a class="action-item" href="' . CRM_Utils_System::url('civicrm/contact/merge', "reset=1&cid=" . $originalId . '&oid=' . $dao->id . '&action=update&rgid=' . $rgid) . '">' . ts('Merge') . '</a>';
          $contactLinks['msg'] = 'merge';
        }
      }

      $i++;
    }

    return $contactLinks;
  }

  /**
   * This function retrieve component related contact information.
   *
   * @param array $componentIds
   *   Array of component Ids.
   * @param string $componentName
   * @param array $returnProperties
   *   Array of return elements.
   *
   * @return array
   *   array of contact info.
   */
  public static function contactDetails($componentIds, $componentName, $returnProperties = []) {
    $contactDetails = [];
    if (empty($componentIds) ||
      !in_array($componentName, ['CiviContribute', 'CiviMember', 'CiviEvent', 'Activity', 'CiviCase'])
    ) {
      return $contactDetails;
    }

    if (empty($returnProperties)) {
      $autocompleteContactSearch = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options'
      );
      $returnProperties = array_fill_keys(array_merge(['sort_name'],
        array_keys($autocompleteContactSearch)
      ), 1);
    }

    $compTable = NULL;
    if ($componentName == 'CiviContribute') {
      $compTable = 'civicrm_contribution';
    }
    elseif ($componentName == 'CiviMember') {
      $compTable = 'civicrm_membership';
    }
    elseif ($componentName == 'Activity') {
      $compTable = 'civicrm_activity';
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    }
    elseif ($componentName == 'CiviCase') {
      $compTable = 'civicrm_case';
    }
    else {
      $compTable = 'civicrm_participant';
    }

    $select = $from = [];
    foreach ($returnProperties as $property => $ignore) {
      $value = (in_array($property, [
        'city',
        'street_address',
        'postal_code',
      ])) ? 'address' : $property;
      switch ($property) {
        case 'sort_name':
          if ($componentName == 'Activity') {
            $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
            $select[] = "contact.$property as $property";
            $from[$value] = "
INNER JOIN civicrm_activity_contact acs ON (acs.activity_id = {$compTable}.id AND acs.record_type_id = {$sourceID})
INNER JOIN civicrm_contact contact ON ( contact.id = acs.contact_id )";
          }
          elseif ($componentName == 'CiviCase') {
            $select[] = "contact.$property as $property";
            $from[$value] = "
INNER JOIN civicrm_case_contact ccs ON (ccs.case_id = {$compTable}.id)
INNER JOIN civicrm_contact contact ON ( contact.id = ccs.contact_id )";
          }
          else {
            $select[] = "$property as $property";
            $from[$value] = "INNER JOIN civicrm_contact contact ON ( contact.id = $compTable.contact_id )";
          }
          break;

        case 'target_sort_name':
          $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
          $select[] = "contact_target.sort_name as $property";
          $from[$value] = "
INNER JOIN civicrm_activity_contact act ON (act.activity_id = {$compTable}.id AND act.record_type_id = {$targetID})
INNER JOIN civicrm_contact contact_target ON ( contact_target.id = act.contact_id )";
          break;

        case 'email':
        case 'phone':
        case 'city':
        case 'street_address':
        case 'postal_code':
          $select[] = "$property as $property";
          // Grab target contact properties if this is for activity
          if ($componentName == 'Activity') {
            $from[$value] = "LEFT JOIN civicrm_{$value} {$value} ON ( contact_target.id = {$value}.contact_id AND {$value}.is_primary = 1 ) ";
          }
          else {
            $from[$value] = "LEFT JOIN civicrm_{$value} {$value} ON ( contact.id = {$value}.contact_id AND {$value}.is_primary = 1 ) ";
          }
          break;

        case 'country':
        case 'state_province':
          $select[] = "{$property}.name as $property";
          if (!in_array('address', $from)) {
            // Grab target contact properties if this is for activity
            if ($componentName == 'Activity') {
              $from['address'] = 'LEFT JOIN civicrm_address address ON ( contact_target.id = address.contact_id AND address.is_primary = 1) ';
            }
            else {
              $from['address'] = 'LEFT JOIN civicrm_address address ON ( contact.id = address.contact_id AND address.is_primary = 1) ';
            }
          }
          $from[$value] = " LEFT JOIN civicrm_{$value} {$value} ON ( address.{$value}_id = {$value}.id  ) ";
          break;
      }
    }

    //finally retrieve contact details.
    if (!empty($select) && !empty($from)) {
      $fromClause = implode(' ', $from);
      $selectClause = implode(', ', $select);
      $whereClause = "{$compTable}.id IN (" . implode(',', $componentIds) . ')';
      $groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($select, ["{$compTable}.id", 'contact.id']);

      $query = "
  SELECT  contact.id as contactId, $compTable.id as componentId, $selectClause
    FROM  $compTable as $compTable $fromClause
   WHERE  $whereClause
   {$groupBy}";

      $contact = CRM_Core_DAO::executeQuery($query);
      while ($contact->fetch()) {
        $contactDetails[$contact->componentId]['contact_id'] = $contact->contactId;
        foreach ($returnProperties as $property => $ignore) {
          $contactDetails[$contact->componentId][$property] = $contact->$property;
        }
      }
    }

    return $contactDetails;
  }

  /**
   * Function handles shared contact address processing.
   * In this function we just modify submitted values so that new address created for the user
   * has same address as shared contact address. We copy the address so that search etc will be
   * much more efficient.
   *
   * @param array $address
   *   This is associated array which contains submitted form values.
   */
  public static function processSharedAddress(&$address) {
    if (!is_array($address)) {
      return;
    }

    // In create mode sharing a contact's address is pretty straight forward.
    // In update mode we should check if the user stops sharing. If yes:
    // - Set the master_id to an empty value
    // Normal update process will automatically create new address with submitted values

    // 1. loop through entire submitted address array
    $skipFields = ['is_primary', 'location_type_id', 'is_billing', 'master_id', 'add_relationship', 'id', 'contact_id'];
    foreach ($address as & $values) {
      // 2. check if "Use another contact's address" is checked, if not continue
      // Additionally, if master_id is set (address was shared), set master_id to empty value.
      if (empty($values['use_shared_address'])) {
        if (!empty($values['master_id'])) {
          $values['master_id'] = '';
        }
        continue;
      }

      // Set add_relationship checkbox value
      $values['add_relationship'] = !empty($values['add_relationship']);

      // 3. get the address details for master_id
      $masterAddress = new CRM_Core_BAO_Address();
      $masterAddress->id = $values['master_id'] ?? NULL;
      $masterAddress->find(TRUE);

      // 4. CRM-10336: Empty all fields (execept the fields to skip)
      foreach ($values as $field => $submittedValue) {
        if (!in_array($field, $skipFields)) {
          $values[$field] = '';
        }
      }

      // 5. update address params to match shared address
      // make sure you preserve specific form values like location type, is_primary_ is_billing, master_id
      foreach ($masterAddress as $field => $value) {
        if (!in_array($field, $skipFields)) {
          if (isset($masterAddress->$field)) {
            $values[$field] = $masterAddress->$field;
          }
        }
      }
    }
  }

  /**
   * Get the list of contact name give address associated array.
   *
   * @param array $addresses
   *   Associated array of.
   *
   * @return array
   *   associated array of contact names
   */
  public static function getAddressShareContactNames($addresses) {
    $contactNames = [];
    // get the list of master id's for address
    $masterAddressIds = [];
    foreach ($addresses as $key => $addressValue) {
      if (!empty($addressValue['master_id'])) {
        $masterAddressIds[] = $addressValue['master_id'];
      }
    }

    if (!empty($masterAddressIds)) {
      $query = 'SELECT ca.id, cc.display_name, cc.id as cid, cc.is_deleted
                      FROM civicrm_contact cc
                           INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                      WHERE ca.id IN  ( ' . implode(',', $masterAddressIds) . ')';
      $dao = CRM_Core_DAO::executeQuery($query);

      while ($dao->fetch()) {
        $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$dao->cid}");
        $contactNames[$dao->id] = [
          'name' => "<a href='{$contactViewUrl}'>{$dao->display_name}</a>",
          'is_deleted' => $dao->is_deleted,
          'contact_id' => $dao->cid,
        ];
      }
    }
    return $contactNames;
  }

  /**
   * Clear the contact cache so things are kosher. We started off being super aggressive with clearing
   * caches, but are backing off from this with every release. Compromise between ease of coding versus
   * performance versus being accurate at that very instant
   *
   * @param bool $isEmptyPrevNextTable
   *   Should the civicrm_prev_next table be cleared of any contact entries.
   *   This is currently done from import but not other places and would
   *   likely affect user experience in unexpected ways. Existing behaviour retained
   *   ... reluctantly.
   */
  public static function clearContactCaches($isEmptyPrevNextTable = FALSE): void {
    if (!CRM_Core_Config::isPermitCacheFlushMode()) {
      return;
    }
    if ($isEmptyPrevNextTable) {
      // These two calls are redundant in default deployments, but they're
      // meaningful if "prevnext" is memory-backed.
      Civi::service('prevnext')->deleteItem();
      CRM_Core_BAO_PrevNextCache::deleteItem();
    }

    CRM_ACL_BAO_Cache::opportunisticCacheFlush();
    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();
  }

  /**
   * @param array $params
   *
   * @throws Exception
   */
  public static function updateGreeting($params) {
    $contactType = $params['ct'];
    $greeting = $params['gt'];
    $valueID = $id = $params['id'] ?? NULL;
    $force = $params['force'] ?? NULL;
    $limit = $params['limit'] ?? NULL;

    // if valueID is not passed use default value
    if (!$valueID) {
      $valueID = $id = self::defaultGreeting($contactType, $greeting);
    }

    $filter = [
      'contact_type' => $contactType,
      'greeting_type' => $greeting,
    ];

    $allGreetings = CRM_Core_PseudoConstant::greeting($filter);
    $originalGreetingString = $greetingString = $allGreetings[$valueID] ?? NULL;
    if (!$greetingString) {
      throw new CRM_Core_Exception(ts('Incorrect greeting value id %1, or no default greeting for this contact type and greeting type.', [1 => $valueID]));
    }

    // Process ALL contacts only when force=1 or force=2 is passed. Else only contacts with NULL greeting or addressee value are updated.
    $processAll = $processOnlyIdSet = FALSE;
    if ($force == 1) {
      $processAll = TRUE;
    }
    elseif ($force == 2) {
      $processOnlyIdSet = TRUE;
    }

    //FIXME : apiQuery should handle these clause.
    $filterContactFldIds = $filterIds = [];
    $idFldName = $displayFldName = NULL;
    if (in_array($greeting, CRM_Contact_BAO_Contact::$_greetingTypes)) {
      $idFldName = $greeting . '_id';
      $displayFldName = $greeting . '_display';
    }

    if ($idFldName) {
      $queryParams = [1 => [$contactType, 'String']];

      // if $force == 1 then update all contacts else only
      // those with NULL greeting or addressee value CRM-9476
      if ($processAll) {
        $sql = "SELECT DISTINCT id, $idFldName FROM civicrm_contact WHERE contact_type = %1 ";
      }
      else {
        $sql = "
          SELECT DISTINCT id, $idFldName
          FROM civicrm_contact
          WHERE contact_type = %1
          AND ({$idFldName} IS NULL
          OR ( {$idFldName} IS NOT NULL AND ({$displayFldName} IS NULL OR {$displayFldName} = '')) )";
      }

      if ($limit) {
        $sql .= " LIMIT 0, %2";
        $queryParams += [2 => [$limit, 'Integer']];
      }

      $dao = CRM_Core_DAO::executeQuery($sql, $queryParams);
      while ($dao->fetch()) {
        $filterContactFldIds[$dao->id] = $dao->$idFldName;

        if (!CRM_Utils_System::isNull($dao->$idFldName)) {
          $filterIds[$dao->id] = $dao->$idFldName;
        }
      }
    }

    if (empty($filterContactFldIds)) {
      return;
    }
    // perform token replacement and build update SQL
    $contactIds = [];
    $cacheFieldQuery = "UPDATE civicrm_contact SET {$greeting}_display = CASE id ";
    foreach (array_keys($filterContactFldIds) as $contactID) {
      if (!$processAll &&
        !array_key_exists($contactID, $filterContactFldIds)
      ) {
        continue;
      }

      if ($processOnlyIdSet && !array_key_exists($contactID, $filterIds)) {
        continue;
      }

      if ($id) {
        $greetingString = $originalGreetingString;
        $contactIds[] = $contactID;
      }
      else {
        $greetingBuffer = $allGreetings[$filterContactFldIds[$contactID]] ?? NULL;
        if ($greetingBuffer) {
          $greetingString = $greetingBuffer;
        }
      }

      CRM_Utils_Token::replaceGreetingTokens($greetingString, [], $contactID, 'CRM_UpdateGreeting', TRUE);
      $greetingString = CRM_Utils_String::parseOneOffStringThroughSmarty($greetingString);
      $greetingString = CRM_Core_DAO::escapeString($greetingString);
      $cacheFieldQuery .= " WHEN {$contactID} THEN '{$greetingString}' ";

      $allContactIds[] = $contactID;
    }

    if (!empty($allContactIds)) {
      $cacheFieldQuery .= " ELSE {$greeting}_display
                              END;";
      if (!empty($contactIds)) {
        // need to update greeting _id field.
        // reset greeting _custom
        $resetCustomGreeting = '';
        if ($valueID != 4) {
          $resetCustomGreeting = ", {$greeting}_custom = NULL ";
        }

        $queryString = "
UPDATE civicrm_contact
SET {$greeting}_id = {$valueID}
    {$resetCustomGreeting}
WHERE id IN (" . implode(',', $contactIds) . ")";
        CRM_Core_DAO::executeQuery($queryString);
      }

      // now update cache field
      CRM_Core_DAO::executeQuery($cacheFieldQuery);
    }
  }

  /**
   * Fetch the default greeting for a given contact type.
   *
   * @param string $contactType
   *   Contact type.
   * @param string $greetingType
   *   Greeting type.
   *
   * @return int|null
   */
  public static function defaultGreeting($contactType, $greetingType) {
    $contactTypeFilters = [
      'Individual' => 1,
      'Household' => 2,
      'Organization' => 3,
    ];
    if (!isset($contactTypeFilters[$contactType])) {
      return NULL;
    }
    $filter = $contactTypeFilters[$contactType];

    $id = CRM_Core_OptionGroup::values($greetingType, NULL, NULL, NULL,
      " AND is_default = 1 AND (filter = {$filter} OR filter = 0)",
      'value'
    );
    if (!empty($id)) {
      return current($id);
    }
  }

  /**
   * Get the tokens that will need to be resolved to populate the contact's greetings.
   *
   * @param array $contactParams
   *
   * @return array
   *   Array of tokens. The ALL ke
   */
  public static function getTokensRequiredForContactGreetings($contactParams) {
    $tokens = [];
    foreach (['addressee', 'email_greeting', 'postal_greeting'] as $greeting) {
      $string = '';
      if (!empty($contactParams[$greeting . '_id'])) {
        $string = CRM_Core_PseudoConstant::getLabel('CRM_Contact_BAO_Contact', $greeting . '_id', $contactParams[$greeting . '_id']);
      }
      $string = $contactParams[$greeting . '_custom'] ?? $string;
      if (empty($string)) {
        $tokens[$greeting] = [];
      }
      else {
        $tokens[$greeting] = CRM_Utils_Token::getTokens($string);
      }
    }
    $allTokens = array_merge_recursive($tokens['addressee'], $tokens['email_greeting'], $tokens['postal_greeting']);
    $tokens['all'] = $allTokens;
    return $tokens;
  }

  /**
   * Determine if a contact ID is real/valid.
   *
   * @param int $contactId
   *   The hypothetical contact ID
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function isContactId($contactId) {
    if ($contactId) {
      // ensure that this is a valid contact id (for session inconsistency rules)
      $cid = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactId,
        'id',
        'id'
      );
      if ($cid) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
