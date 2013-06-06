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
class CRM_Contact_BAO_Contact_Utils {

  /**
   * given a contact type, get the contact image
   *
   * @param string  $contactType contact type
   * @param boolean $urlOnly  if we need to return only image url
   * @param int     $contactId contact id
   * @param boolean $addProfileOverlay  if profile overlay class should be added
   *
   * @return string
   * @access public
   * @static
   */
  static function getImage($contactType, $urlOnly = FALSE, $contactId = NULL, $addProfileOverlay = TRUE) {
    static $imageInfo = array();

    $contactType = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($contactType, CRM_Core_DAO::VALUE_SEPARATOR));
    $contactType = $contactType[0];

    if (!array_key_exists($contactType, $imageInfo)) {
      $imageInfo[$contactType] = array();

      $typeInfo = array();
      $params = array('name' => $contactType);
      CRM_Contact_BAO_ContactType::retrieve($params, $typeInfo);

      if (CRM_Utils_Array::value('image_URL', $typeInfo)) {
        $imageUrl = $typeInfo['image_URL'];
        $config = CRM_Core_Config::singleton();

        if (!preg_match("/^(\/|(http(s)?:)).+$/i", $imageUrl)) {
          $imageUrl = $config->resourceBase . $imageUrl;
        }
        $imageInfo[$contactType]['image'] = "<div class=\"icon crm-icon {$typeInfo['name']}-icon\" style=\"background: url('{$imageUrl}')\" title=\"{$contactType}\"></div>";
        $imageInfo[$contactType]['url'] = $imageUrl;
      }
      else {
        $isSubtype = (array_key_exists('parent_id', $typeInfo) &&
          $typeInfo['parent_id']
        ) ? TRUE : FALSE;

        if ($isSubtype) {
          $type = CRM_Contact_BAO_ContactType::getBasicType($typeInfo['name']) . '-subtype';
        }
        else {
          $type = CRM_Utils_Array::value('name', $typeInfo);
        }

        // do not add title since it hides contact name
        if ($addProfileOverlay) {
          $imageInfo[$contactType]['image'] = "<div class=\"icon crm-icon {$type}-icon\"></div>";
        }
        else{
          $imageInfo[$contactType]['image'] = "<div class=\"icon crm-icon {$type}-icon\" title=\"{$contactType}\"></div>";
        }
        $imageInfo[$contactType]['url'] = NULL;
      }
    }

    if ($addProfileOverlay) {
      static $summaryOverlayProfileId = NULL;
      if (!$summaryOverlayProfileId) {
        $summaryOverlayProfileId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', 'summary_overlay', 'id', 'name');
      }

      $profileURL = CRM_Utils_System::url('civicrm/profile/view',
        "reset=1&gid={$summaryOverlayProfileId}&id={$contactId}&snippet=4"
      );

      $imageInfo[$contactType]['summary-link'] = '<a href="' . $profileURL . '" class="crm-summary-link">' . $imageInfo[$contactType]['image'] . '</a>';
    }
    else {
      $imageInfo[$contactType]['summary-link'] = $imageInfo[$contactType]['image'];
    }

    return $urlOnly ? $imageInfo[$contactType]['url'] : $imageInfo[$contactType]['summary-link'];
  }

  /**
   * function check for mix contact ids(individual+household etc...)
   *
   * @param array $contactIds array of contact ids
   *
   * @return boolen true or false true if mix contact array else fale
   *
   * @access public
   * @static
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
    $count = CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    return $count > 1 ? TRUE : FALSE;
  }

  /**
   * Generate a checksum for a contactID
   *
   * @param int    $contactID
   * @param int    $ts         timestamp that checksum was generated
   * @param int    $live       life of this checksum in hours/ 'inf' for infinite
   * @param string $hash       contact hash, if sent, prevents a query in inner loop
   *
   * @return array ( $cs, $ts, $live )
   * @static
   * @access public
   */
  static function generateChecksum($contactID, $ts = NULL, $live = NULL, $hash = NULL) {
    // return a warning message if we dont get a contactID
    // this typically happens when we do a message preview
    // or an anon mailing view - CRM-8298
    if (!$contactID) {
      return 'invalidChecksum';
    }

    if (!$hash) {
      $hash = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactID, 'hash'
      );
    }

    if (!$hash) {
      $hash = md5(uniqid(rand(), TRUE));
      CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Contact',
        $contactID,
        'hash', $hash
      );
    }

    if (!$ts) {
      $ts = time();
    }

    if (!$live) {
      $days = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'checksum_timeout',
        NULL,
        7
      );
      $live = 24 * $days;
    }

    $cs = md5("{$hash}_{$contactID}_{$ts}_{$live}");
    return "{$cs}_{$ts}_{$live}";
  }

  /**
   * Make sure the checksum is valid for the passed in contactID
   *
   * @param int    $contactID
   * @param string $inputCheck checksum to match against
   *
   * @return boolean           true if valid, else false
   * @static
   * @access public
   */
  static function validChecksum($contactID, $inputCheck) {

    $input = CRM_Utils_System::explode('_', $inputCheck, 3);

    $inputCS = CRM_Utils_Array::value(0, $input);
    $inputTS = CRM_Utils_Array::value(1, $input);
    $inputLF = CRM_Utils_Array::value(2, $input);

    $check = self::generateChecksum($contactID, $inputTS, $inputLF);

    if ($check != $inputCheck) {
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
   * Function to get the count of  contact loctions
   *
   * @param int $contactId contact id
   *
   * @return int $locationCount max locations for the contact
   * @static
   * @access public
   */
  static function maxLocations($contactId) {
    $contactLocations = array();

    // find number of location blocks for this contact and adjust value accordinly
    // get location type from email
    $query = "
( SELECT location_type_id FROM civicrm_email   WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_phone   WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_im      WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_address WHERE contact_id = {$contactId} )
";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    return $dao->N;
  }

  /**
   * Create Current employer relationship for a individual
   *
   * @param int    $contactID        contact id of the individual
   * @param string $organization     it can be name or id of organization
   *
   * @access public
   * @static
   */
  static function createCurrentEmployerRelationship($contactID, $organization) {
    $organizationId = NULL;

    // if organization id is passed.
    if (is_numeric($organization)) {
      $organizationId = $organization;
    }
    else {
      $orgName = explode('::', $organization);
      trim($orgName[0]);

      $organizationParams = array();
      $organizationParams['organization_name'] = $orgName[0];

      $dedupeParams = CRM_Dedupe_Finder::formatParams($organizationParams, 'Organization');

      $dedupeParams['check_permission'] = FALSE;
      $dupeIDs = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Organization', 'Supervised');

      if (is_array($dupeIDs) && !empty($dupeIDs)) {
        // we should create relationship only w/ first org CRM-4193
        foreach ($dupeIDs as $orgId) {
          $organizationId = $orgId;
          break;
        }
      }
      else {
        //create new organization
        $newOrg = array(
          'contact_type' => 'Organization',
          'organization_name' => trim($orgName[0]),
        );
        $org = CRM_Contact_BAO_Contact::create($newOrg);
        $organizationId = $org->id;
      }
    }

    if ($organizationId) {
      $cid = array('contact' => $contactID);

      // get the relationship type id of "Employee of"
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Employee of', 'id', 'name_a_b');
      if (!$relTypeId) {
        CRM_Core_Error::fatal(ts("You seem to have deleted the relationship type 'Employee of'"));
      }

      // create employee of relationship
      $relationshipParams = array(
        'is_active' => TRUE,
        'relationship_type_id' => $relTypeId . '_a_b',
        'contact_check' => array($organizationId => TRUE),
      );
      list($valid, $invalid, $duplicate,
        $saved, $relationshipIds
      ) = CRM_Contact_BAO_Relationship::create($relationshipParams, $cid);


      // In case we change employer, clean prveovious employer related records.
      $previousEmployerID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id');
      if ($previousEmployerID &&
        $previousEmployerID != $organizationId
      ) {
        self::clearCurrentEmployer($contactID, $previousEmployerID);
      }

      // set current employer
      self::setCurrentEmployer(array($contactID => $organizationId));

      $relationshipParams['relationship_ids'] = $relationshipIds;
      // handle related meberships. CRM-3792
      self::currentEmployerRelatedMembership($contactID, $organizationId, $relationshipParams, $duplicate);
    }
  }

  /**
   * create related memberships for current employer
   *
   * @param int     $contactID          contact id of the individual
   * @param int     $employerID         contact id of the organization.
   * @param array   $relationshipParams relationship params.
   * @param boolean $duplicate          are we triggered existing relationship.
   *
   * @access public
   * @static
   */
  static function currentEmployerRelatedMembership($contactID, $employerID, $relationshipParams, $duplicate = FALSE) {
    $ids = array();
    $action = CRM_Core_Action::ADD;

    //we do not know that triggered relationship record is active.
    if ($duplicate) {
      $relationship = new CRM_Contact_DAO_Relationship();
      $relationship->contact_id_a = $contactID;
      $relationship->contact_id_b = $employerID;
      $relationship->relationship_type_id = $relationshipParams['relationship_type_id'];
      if ($relationship->find(TRUE)) {
        $action = CRM_Core_Action::UPDATE;
        $ids['contact'] = $contactID;
        $ids['contactTarget'] = $employerID;
        $ids['relationship'] = $relationship->id;
        CRM_Contact_BAO_Relationship::setIsActive($relationship->id, TRUE);
      }
      $relationship->free();
    }

    //need to handle related meberships. CRM-3792
    CRM_Contact_BAO_Relationship::relatedMemberships($contactID, $relationshipParams, $ids, $action);
  }

  /**
   * Function to set current employer id and organization name
   *
   * @param array $currentEmployerParams associated array of contact id and its employer id
   *
   */
  static function setCurrentEmployer($currentEmployerParams) {
    foreach ($currentEmployerParams as $contactId => $orgId) {
      $query = "UPDATE civicrm_contact contact_a,civicrm_contact contact_b
SET contact_a.employer_id=contact_b.id, contact_a.organization_name=contact_b.organization_name
WHERE contact_a.id ={$contactId} AND contact_b.id={$orgId}; ";

      //FIXME : currently civicrm mysql_query support only single statement
      //execution, though mysql 5.0 support multiple statement execution.
      $dao = CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * Function to update cached current employer name
   *
   * @param int $organizationId current employer id
   *
   */
  static function updateCurrentEmployer($organizationId) {
    $query = "UPDATE civicrm_contact contact_a,civicrm_contact contact_b
SET contact_a.organization_name=contact_b.organization_name
WHERE contact_a.employer_id=contact_b.id AND contact_b.id={$organizationId}; ";

    $dao = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Function to clear cached current employer name
   *
   * @param int $contactId contact id ( mostly individual contact id)
   * @param int $employerId contact id ( mostly organization contact id)
   *
   */
  static function clearCurrentEmployer($contactId, $employerId = NULL) {
    $query = "UPDATE civicrm_contact
SET organization_name=NULL, employer_id = NULL
WHERE id={$contactId}; ";

    $dao = CRM_Core_DAO::executeQuery($query);

    // need to handle related meberships. CRM-3792
    if ($employerId) {
      //1. disable corresponding relationship.
      //2. delete related membership.

      //get the relationship type id of "Employee of"
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Employee of', 'id', 'name_a_b');
      if (!$relTypeId) {
        CRM_Core_Error::fatal(ts("You seem to have deleted the relationship type 'Employee of'"));
      }
      $relMembershipParams['relationship_type_id'] = $relTypeId . '_a_b';
      $relMembershipParams['contact_check'][$employerId] = 1;

      //get relationship id.
      if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($relMembershipParams, $contactId, $employerId)) {
        $relationship = new CRM_Contact_DAO_Relationship();
        $relationship->contact_id_a = $contactId;
        $relationship->contact_id_b = $employerId;
        $relationship->relationship_type_id = $relTypeId;

        if ($relationship->find(TRUE)) {
          CRM_Contact_BAO_Relationship::setIsActive($relationship->id, FALSE);
          CRM_Contact_BAO_Relationship::relatedMemberships($contactId, $relMembershipParams,
            $ids = array(
              ), CRM_Core_Action::DELETE
          );
        }
        $relationship->free();
      }
    }
  }

  /**
   * Function to build form for related contacts / on behalf of organization.
   *
   * @param $form              object  invoking Object
   * @param $contactType       string  contact type
   * @param $title             string  fieldset title
   * @param $maxLocationBlocks int     number of location blocks
   *
   * @static
   *
   */
  static function buildOnBehalfForm(&$form,
    $contactType       = 'Individual',
    $countryID         = NULL,
    $stateID           = NULL,
    $title             = 'Contact Information',
    $contactEditMode   = FALSE,
    $maxLocationBlocks = 1
  ) {
    if ($title == 'Contact Information') {
      $title = ts('Contact Information');
    }

    $config = CRM_Core_Config::singleton();

    $form->assign('contact_type', $contactType);
    $form->assign('fieldSetTitle', $title);
    $form->assign('contactEditMode', $contactEditMode);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');
    if ($form->_contactId) {
      $form->assign('orgId', $form->_contactId);
    }

    switch ($contactType) {
      case 'Organization':
        $session = CRM_Core_Session::singleton();
        $contactID = $session->get('userID');

        if ($contactID) {
          $employers = CRM_Contact_BAO_Relationship::getPermissionedEmployer($contactID);
        }

        $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', 'cid=', FALSE, NULL, FALSE);
        $form->assign('locDataURL', $locDataURL);

        if (!$contactEditMode && $contactID && (count($employers) >= 1)) {

          $dataURL = CRM_Utils_System::url('civicrm/ajax/employer',
            'cid=' . $contactID,
            FALSE, NULL, FALSE
          );
          $form->assign('employerDataURL', $dataURL);

          $form->add('text', 'organization_id', ts('Select an existing related Organization OR Enter a new one'));
          $form->add('hidden', 'onbehalfof_id', '', array('id' => 'onbehalfof_id'));
          $orgOptions = array('0' => ts('Create new organization'),
            '1' => ts('Select existing organization'),
          );
          $orgOptionExtra = array('onclick' => "showHideByValue('org_option','true','select_org','table-row','radio',true);showHideByValue('org_option','true','create_org','table-row','radio',false);");
          $form->addRadio('org_option', ts('options'), $orgOptions, $orgOptionExtra);
          $form->assign('relatedOrganizationFound', TRUE);
        }

        $isRequired = FALSE;
        if (CRM_Utils_Array::value('is_for_organization', $form->_values) == 2) {
          $isRequired = TRUE;
        }
        $form->add('text', 'organization_name', ts('Organization Name'), $attributes['organization_name'], $isRequired);
        break;

      case 'Household':
        $form->add('text', 'household_name', ts('Household Name'),
          $attributes['household_name']
        );
        break;

      default:
        // individual
        $form->addElement('select', 'prefix_id', ts('Prefix'),
          array('' => ts('- prefix -')) + CRM_Core_PseudoConstant::individualPrefix()
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
          array('' => ts('- suffix -')) + CRM_Core_PseudoConstant::individualSuffix()
        );
    }

    $addressSequence = $config->addressSequence();
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

    // also fix the state country selector
    CRM_Contact_Form_Edit_Address::fixStateSelect($form,
      'address[1][country_id]',
      'address[1][state_province_id]',
      "address[1][county_id]",
      $countryID,
      $stateID
    );
  }

  /**
   * Function to clear cache employer name and employer id
   * of all employee when employer get deleted.
   *
   * @param int $employerId contact id of employer ( organization id )
   *
   */
  static function clearAllEmployee($employerId) {
    $query = "
UPDATE civicrm_contact
   SET organization_name=NULL, employer_id = NULL
 WHERE employer_id={$employerId}; ";

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
  }

  /**
   * Given an array of contact ids this function will return array with links to view contact page
   *
   * @param array $contactIDs associated contact id's
   * @param int $originalId associated with the contact which is edited
   *
   *
   * @return array $contactViewLinks returns array with links to contact view
   * @static
   * @access public
   */
  static function formatContactIDSToLinks($contactIDs, $addViewLink = TRUE, $addEditLink = TRUE, $originalId = NULL) {
    $contactLinks = array();
    if (!is_array($contactIDs) || empty($contactIDs)) {
      return $contactLinks;
    }

    // does contact has sufficient permissions.
    $permissions = array(
      'view' => 'view all contacts',
      'edit' => 'edit all contacts',
      'merge' => 'merge duplicate contacts',
    );

    $permissionedContactIds = array();
    foreach ($permissions as $task => $permission) {
      // give permission.
      if (CRM_Core_Permission::check($permission)) {
        foreach ($contactIDs as $contactId) {
          $permissionedContactIds[$contactId][$task] = TRUE;
        }
        continue;
      }

      // check permission on acl basis.
      if (in_array($task, array(
        'view', 'edit'))) {
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
      $hasPermissions = CRM_Utils_Array::value($dao->id, $permissionedContactIds);
      if (!is_array($hasPermissions) || empty($hasPermissions)) {
        $i++;
        continue;
      }

      // do check for view.
      if (array_key_exists('view', $hasPermissions)) {
        $contactLinks['rows'][$i]['view'] = '<a class="action-item action-item-first" href="' . CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $dao->id) . '" target="_blank">' . ts('View') . '</a>';
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
        $rgBao               = new CRM_Dedupe_BAO_RuleGroup();
        $rgBao->contact_type = $dao->contact_type;
        $rgBao->used         = 'Supervised';
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
   * @param array  $componentIds     array of component Ids.
   * @param array  $returnProperties array of return elements.
   *
   * @return $contactDetails array of contact info.
   * @static
   */
  static function contactDetails($componentIds, $componentName, $returnProperties = array(
    )) {
    $contactDetails = array();
    if (empty($componentIds) ||
      !in_array($componentName, array('CiviContribute', 'CiviMember', 'CiviEvent', 'Activity'))
    ) {
      return $contactDetails;
    }

    if (empty($returnProperties)) {
      $autocompleteContactSearch = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options'
      );
      $returnProperties = array_fill_keys(array_merge(array('sort_name'),
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
    }
    else {
      $compTable = 'civicrm_participant';
    }

    $select = $from = array();
    foreach ($returnProperties as $property => $ignore) {
      $value = (in_array($property, array(
        'city', 'street_address'))) ? 'address' : $property;
      switch ($property) {
        case 'sort_name':
          if ($componentName == 'Activity') {
            $select[] = "contact_source.$property as $property";
            $from[$value] = "INNER JOIN civicrm_contact contact ON ( contact.id = $compTable.source_contact_id )";
          }
          else {
            $select[] = "$property as $property";
            $from[$value] = "INNER JOIN civicrm_contact contact ON ( contact.id = $compTable.contact_id )";
          }
          break;

        case 'target_sort_name':
          $select[] = "contact_target.sort_name as $property";
          $from[$value] = "INNER JOIN civicrm_contact contact_source ON ( contact_source.id = $compTable.source_contact_id )
                                 LEFT JOIN civicrm_activity_target ON (civicrm_activity_target.activity_id = $compTable.id)
                                 LEFT JOIN civicrm_contact as contact_target ON ( contact_target.id = civicrm_activity_target.target_contact_id )";
          break;

        case 'email':
        case 'phone':
        case 'city':
        case 'street_address':
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
      $fromClause   = implode(' ', $from);
      $selectClause = implode(', ', $select);
      $whereClause  = "{$compTable}.id IN (" . implode(',', $componentIds) . ')';

      $query = "
  SELECT  contact.id as contactId, $compTable.id as componentId, $selectClause
    FROM  $compTable as $compTable $fromClause
   WHERE  $whereClause
Group By  componentId";

      $contact = CRM_Core_DAO::executeQuery($query);
      while ($contact->fetch()) {
        $contactDetails[$contact->componentId]['contact_id'] = $contact->contactId;
        foreach ($returnProperties as $property => $ignore) {
          $contactDetails[$contact->componentId][$property] = $contact->$property;
        }
      }
      $contact->free();
    }

    return $contactDetails;
  }

  /**
   * Function handles shared contact address processing
   * In this function we just modify submitted values so that new address created for the user
   * has same address as shared contact address. We copy the address so that search etc will be
   * much efficient.
   *
   * @param array $address this is associated array which contains submitted form values
   *
   * @return void
   * @static
   * @access public
   */
  static function processSharedAddress(&$address) {
    if (!is_array($address)) {
      return;
    }

    // Sharing contact address during create mode is pretty straight forward.
    // In update mode we should check following:
    // - We should check if user has uncheck shared contact address
    // - If yes then unset the master_id or may be just delete the address that copied master
    //    Normal update process will automatically create new address with submitted values

    // 1. loop through entire subnitted address array
    $masterAddress = array();
    $skipFields = array('is_primary', 'location_type_id', 'is_billing', 'master_id');
    foreach ($address as & $values) {
      // 2. check if master id exists, if not continue
      if (!CRM_Utils_Array::value('master_id', $values) ||
        !CRM_Utils_Array::value('use_shared_address', $values)
      ) {
        // we should unset master id when use uncheck share address for existing address
        $values['master_id'] = 'null';
        continue;
      }

      // 3. get the address details for master_id
      $masterAddress = new CRM_Core_BAO_Address();
      $masterAddress->id = CRM_Utils_Array::value('master_id', $values);
      $masterAddress->find(TRUE);

      // 4. modify submitted params and update it with shared contact address
      // make sure you preserve specific form values like location type, is_primary_ is_billing, master_id
      // CRM-10336: Also empty any fields from the existing address block if they don't exist in master (otherwise they will persist)
      foreach ($values as $field => $submittedValue) {
        if (!in_array($field, $skipFields)){
          if (isset($masterAddress->$field)) {
          $values[$field] = $masterAddress->$field;
          } else {
            $values[$field] = '';
        }
      }
    }
  }
  }

  /**
   * Function to get the list of contact name give address associated array
   *
   * @param array $addresses associated array of
   *
   * @return $contactNames associated array of contact names
   * @static
   */
  static function getAddressShareContactNames(&$addresses) {
    $contactNames = array();
    // get the list of master id's for address
    $masterAddressIds = array();
    foreach ($addresses as $key => $addressValue) {
      if (CRM_Utils_Array::value('master_id', $addressValue)) {
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
        $contactNames[$dao->id] = array(
          'name' => "<a href='{$contactViewUrl}'>{$dao->display_name}</a>",
          'is_deleted' => $dao->is_deleted,
        );
      }
    }
    return $contactNames;
  }

  /**
   * Clear the contact cache so things are kosher. We started off being super aggressive with clearing
   * caches, but are backing off from this with every release. Compromise between ease of coding versus
   * performance versus being accurate at that very instant
   *
   * @param $contactID - the contactID that was edited / deleted
   *
   * @return void
   * @static
   */
  static function clearContactCaches($contactID = NULL) {
    // clear acl cache if any.
    CRM_ACL_BAO_Cache::resetCache();

    if (empty($contactID)) {
      // also clear prev/next dedupe cache - if no contactID passed in
      CRM_Core_BAO_PrevNextCache::deleteItem();
    }

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();
  }

  public static function updateGreeting($params) {
    $contactType = $params['ct'];
    $greeting    = $params['gt'];
    $valueID     = $id = CRM_Utils_Array::value('id', $params);
    $force       = CRM_Utils_Array::value('force', $params);

    // if valueID is not passed use default value
    if (!$valueID) {
      $valueID = $id = self::defaultGreeting($contactType, $greeting);
    }

    $filter = array(
      'contact_type' => $contactType,
      'greeting_type' => $greeting,
    );

    $allGreetings = CRM_Core_PseudoConstant::greeting($filter);
    $originalGreetingString = $greetingString = CRM_Utils_Array::value($valueID, $allGreetings);
    if (!$greetingString) {
      CRM_Core_Error::fatal(ts('Incorrect greeting value id %1, or no default greeting for this contact type and greeting type.', array(1 => $valueID)));
    }

    // build return properties based on tokens
    $greetingTokens = CRM_Utils_Token::getTokens($greetingString);
    $tokens = CRM_Utils_Array::value('contact', $greetingTokens);
    $greetingsReturnProperties = array();
    if (is_array($tokens)) {
      $greetingsReturnProperties = array_fill_keys(array_values($tokens), 1);
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
    $filterContactFldIds = $filterIds = array();
    $idFldName = $displayFldName = NULL;
    if (in_array($greeting, CRM_Contact_BAO_Contact::$_greetingTypes)) {
      $idFldName = $greeting . '_id';
      $displayFldName = $greeting . '_display';
    }

    if ($idFldName) {
      // if $force == 1 then update all contacts else only
      // those with NULL greeting or addressee value CRM-9476
      if ($processAll) {
        $sql = "SELECT DISTINCT id, $idFldName FROM civicrm_contact WHERE contact_type = %1 ";
      }
      else {
        $sql = "SELECT DISTINCT id, $idFldName FROM civicrm_contact WHERE contact_type = %1
                     AND ( {$idFldName} IS NULL OR ( {$idFldName} IS NOT NULL AND {$displayFldName} IS NULL ) ) ";
      }

      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($contactType, 'String')));
      while ($dao->fetch()) {
        $filterContactFldIds[$dao->id] = $dao->$idFldName;

        if (!CRM_Utils_System::isNull($dao->$idFldName)) {
          $filterIds[$dao->id] = $dao->$idFldName;
        }
      }
    }

    if (empty($filterContactFldIds)) {
      $filterContactFldIds[] = 0;
    }

    // retrieve only required contact information
    $extraParams[] = array('contact_type', '=', $contactType, 0, 0);
    // we do token replacement in the replaceGreetingTokens hook
    list($greetingDetails) = CRM_Utils_Token::getTokenDetails(array_keys($filterContactFldIds),
      $greetingsReturnProperties,
      FALSE, FALSE, $extraParams
    );
    // perform token replacement and build update SQL
    $contactIds = array();
    $cacheFieldQuery = "UPDATE civicrm_contact SET {$greeting}_display = CASE id ";
    foreach ($greetingDetails as $contactID => $contactDetails) {
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
        if ($greetingBuffer = CRM_Utils_Array::value($filterContactFldIds[$contactID], $allGreetings)) {
          $greetingString = $greetingBuffer;
        }
      }

      CRM_Utils_Token::replaceGreetingTokens($greetingString, $contactDetails, $contactID, 'CRM_UpdateGreeting');
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
   * Fetch the default greeting for a given contact type
   *
   * @param string $contactType contact type
   * @param string $greetingType greeting type
   *
   * @return int or null
   */
  static function defaultGreeting($contactType, $greetingType) {
    $contactTypeFilters = array('Individual' => 1, 'Household' => 2, 'Organization' => 3);
    if (!isset($contactTypeFilters[$contactType])) {
      return;
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
}

