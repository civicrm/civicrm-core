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

/**
 * This class generates form components for custom data
 *
 */
class CRM_Profile_Form extends CRM_Core_Form {
  const
    MODE_REGISTER = 1,
    MODE_SEARCH = 2,
    MODE_CREATE = 4,
    MODE_EDIT = 8;

  protected $_mode;

  protected $_skipPermission = FALSE;

  /**
   * The contact id that we are editing.
   *
   * @var int
   */
  protected $_id;

  /**
   * The group id that we are editing.
   *
   * @var int
   */
  protected $_gid;

  /**
   * @var array
   * Details of the UFGroup used on this page
   */
  protected $_ufGroup = ['name' => 'unknown'];

  /**
   * The group id that we are passing in url.
   *
   * @var int
   *
   * @deprecated
   * @internal
   */
  public $_grid;

  /**
   * The title of the category we are editing.
   *
   * @var string
   */
  protected $_title;

  /**
   * The fields needed to build this form.
   *
   * @var array
   */
  public $_fields;

  /**
   * store contact details.
   *
   * @var array
   */
  protected $_contact;

  /**
   * Do we allow updates of the contact.
   *
   * @var int
   */
  public $_isUpdateDupe = 0;

  /**
   * Dedupe using a specific rule (CRM-6131).
   * Not currently exposed in profile settings, but can be set in a buildForm hook.
   * @var int
   */
  public $_ruleGroupID = NULL;

  protected $_isPermissionedChecksum = FALSE;

  /**
   * THe context from which we came from, allows us to go there if redirect not set.
   *
   * @var string
   */
  protected $_context;

  /**
   * THe contact type for registration case.
   *
   * @var string
   */
  protected $_ctype = NULL;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode.
   * @var array
   */
  protected $_profileIds = [];

  /**
   * Contact profile having activity fields?
   *
   * @var string
   */
  protected $_isContactActivityProfile = FALSE;

  /**
   * Activity Id connected to the profile.
   *
   * @var string
   */
  protected $_activityId = NULL;


  protected $_multiRecordFields = NULL;

  protected $_recordId = NULL;

  /**
   * Action for multi record profile (create/edit/delete).
   *
   * @var string
   */
  protected $_multiRecord = NULL;

  protected $_multiRecordProfile = FALSE;

  protected $_recordExists = TRUE;

  protected $_customGroupTitle = NULL;

  protected $_deleteButtonName = NULL;

  protected $_customGroupId = NULL;

  protected $_mail;

  protected $_currentUserID = NULL;
  protected $_session = NULL;
  protected $_maxRecordLimit = NULL;

  /**
   * Check for any duplicates.
   *
   * Depending on form settings & usage scenario we potentially use the found id,
   * create links to found ids or add an error.
   *
   * @param array $errors
   * @param array $fields
   * @param CRM_Profile_Form $form
   *
   * @return array
   */
  protected static function handleDuplicateChecking(&$errors, $fields, $form) {
    if ($form->_mode == CRM_Profile_Form::MODE_CREATE) {
      // fix for CRM-2888
      $exceptions = [];
    }
    else {
      // for edit mode we need to allow our own record to be a dupe match!
      $exceptions = [CRM_Core_Session::singleton()->get('userID')];
    }
    $contactType = CRM_Core_BAO_UFGroup::getContactType($form->_gid);
    // If all profile fields is of Contact Type then consider
    // profile is of Individual type(default).
    if (!$contactType) {
      $contactType = 'Individual';
    }

    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts(
      $fields, $contactType,
      ($form->_context === 'dialog' ? 'Supervised' : 'Unsupervised'),
      $exceptions,
      FALSE,
      $form->_ruleGroupID
    );
    if ($ids) {
      if ($form->_isUpdateDupe == 2) {
        CRM_Core_Session::setStatus(ts('Note: this contact may be a duplicate of an existing record.'), ts('Possible Duplicate Detected'), 'alert');
      }
      elseif ($form->_isUpdateDupe == 1) {
        $form->_id = $ids[0];
      }
      else {
        if ($form->isEntityReferenceContactCreateMode()) {
          $contactLinks = CRM_Contact_BAO_Contact_Utils::formatContactIDSToLinks($ids, TRUE, TRUE);

          $duplicateContactsLinks = '<div class="matching-contacts-found">';
          $duplicateContactsLinks .= ts('One matching contact was found. ', [
            'count' => count($contactLinks['rows']),
            'plural' => '%count matching contacts were found.<br />',
          ]);
          if ($contactLinks['msg'] == 'view') {
            $duplicateContactsLinks .= ts('You can View the existing contact.', [
              'count' => count($contactLinks['rows']),
              'plural' => 'You can View the existing contacts.',
            ]);
          }
          else {
            $duplicateContactsLinks .= ts('You can View or Edit the existing contact.', [
              'count' => count($contactLinks['rows']),
              'plural' => 'You can View or Edit the existing contacts.',
            ]);
          }
          $duplicateContactsLinks .= '</div>';
          $duplicateContactsLinks .= '<table class="matching-contacts-actions">';
          $row = '';
          for ($i = 0; $i < count($contactLinks['rows']); $i++) {
            $row .= '  <tr>   ';
            $row .= '    <td class="matching-contacts-name"> ';
            $row .= $contactLinks['rows'][$i]['display_name'];
            $row .= '    </td>';
            $row .= '    <td class="matching-contacts-email"> ';
            $row .= $contactLinks['rows'][$i]['primary_email'];
            $row .= '    </td>';
            $row .= '    <td class="action-items"> ';
            $row .= $contactLinks['rows'][$i]['view'] . ' ';
            $row .= $contactLinks['rows'][$i]['edit'];
            $row .= '    </td>';
            $row .= '  </tr>   ';
          }

          $duplicateContactsLinks .= $row . '</table>';
          $duplicateContactsLinks .= "If you're sure this record is not a duplicate, click the 'Save Matching Contact' button below.";

          $errors['_qf_default'] = $duplicateContactsLinks;

          // The button 'Save Matching Contact' is added in buildForm
          // but we only decide here whether ot not to show it - ie
          // if validation failed due to there being duplicates.
          CRM_Core_Smarty::singleton()->assign('showSaveDuplicateButton', 1);
        }
        else {
          $errors['_qf_default'] = ts('A record already exists with the same information.');
        }
      }
    }
    return $errors;
  }

  /**
   * Is this being called from an entity reference field.
   *
   * E.g clicking on 'New Organization' from the employer field
   * would create a link with the context = 'dialog' in the url.
   *
   * @return bool
   */
  public function isEntityReferenceContactCreateMode(): bool {
    return $this->_context === 'dialog';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Profile';
  }

  /**
   * Get the active UFGroups (profiles) on this form
   * Many forms load one or more UFGroups (profiles).
   * This provides a standard function to retrieve the IDs of those profiles from the form
   * so that you can implement things such as "is is_captcha field set on any of the active profiles on this form?"
   *
   * NOT SUPPORTED FOR USE OUTSIDE CORE EXTENSIONS - Added for reCAPTCHA core extension.
   *
   * @return array
   */
  public function getUFGroupIDs() {
    return [$this->_gid];
  }

  /**
   * Are we using the profile in create mode?
   *
   * @return bool
   */
  public function getIsCreateMode() {
    return ($this->_mode == self::MODE_CREATE);
  }

  /**
   * Pre processing work done here.
   *
   * gets session variables for table name, id of entity in table, type of entity and stores them.
   */
  public function preProcess() {
    $this->_id = $this->get('id');
    $this->_profileIds = $this->get('profileIds');
    $this->_grid = CRM_Utils_Request::retrieve('grid', 'Integer', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    //unset from session when $_GET doesn't have it
    //except when the form is submitted
    if (empty($_POST)) {
      if (!array_key_exists('multiRecord', $_GET)) {
        $this->set('multiRecord', NULL);
      }
      if (!array_key_exists('recordId', $_GET)) {
        $this->set('recordId', NULL);
      }
    }

    $this->_currentUserID = CRM_Core_Session::singleton()->get('userID');

    if ($this->_mode == self::MODE_EDIT) {
      //specifies the action being done on a multi record field
      $multiRecordAction = CRM_Utils_Request::retrieve('multiRecord', 'String', $this);
      $this->_multiRecord = (!is_numeric($multiRecordAction)) ? CRM_Core_Action::resolve($multiRecordAction) : $multiRecordAction;
      if ($this->_multiRecord) {
        $this->set('multiRecord', $this->_multiRecord);
      }

      if ($this->_multiRecord &&
        !in_array($this->_multiRecord, [CRM_Core_Action::UPDATE, CRM_Core_Action::ADD, CRM_Core_Action::DELETE])
      ) {
        CRM_Core_Error::statusBounce(ts('Proper action not specified for this custom value record profile'));
      }
    }

    $gids = explode(',', (CRM_Utils_Request::retrieve('gid', 'String', NULL, FALSE, 0) ?? ''));

    if ((count($gids) > 1) && !$this->_profileIds && empty($this->_profileIds)) {
      if (!empty($gids)) {
        foreach ($gids as $pfId) {
          $this->_profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
      }

      // check if we are rendering mixed profiles
      if (CRM_Core_BAO_UFGroup::checkForMixProfiles($this->_profileIds)) {
        CRM_Core_Error::statusBounce(ts('You cannot combine profiles of multiple types.'));
      }

      // for now consider 1'st profile as primary profile and validate it
      // i.e check for profile type etc.
      // FIX ME: validations for other than primary
      $this->_gid = $this->_profileIds[0];
      $this->set('gid', $this->_gid);
      $this->set('profileIds', $this->_profileIds);
    }

    if (!$this->_gid) {
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0);
      $this->set('gid', $this->_gid);
    }

    $this->_activityId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE, 0, 'GET');
    if (is_numeric($this->_activityId)) {
      $latestRevisionId = CRM_Activity_BAO_Activity::getLatestActivityId($this->_activityId);
      if ($latestRevisionId) {
        $this->_activityId = $latestRevisionId;
      }
    }
    $this->_isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($this->_gid);

    //get values for ufGroupName and dupe update.
    if ($this->_gid) {
      $dao = new CRM_Core_DAO_UFGroup();
      $dao->id = $this->_gid;
      if ($dao->find(TRUE)) {
        $this->_isUpdateDupe = $dao->is_update_dupe;
        $this->_ufGroup = (array) $dao;
      }

      if (empty($this->_ufGroup['is_active'])) {
        CRM_Core_Error::statusBounce(ts('The requested profile (gid=%1) is inactive or does not exist.', [
          1 => $this->_gid,
        ]));
      }
    }
    $this->assign('ufGroupName', $this->_ufGroup['name']);

    $gids = empty($this->_profileIds) ? $this->_gid : $this->_profileIds;

    // if we don't have a gid use the default, else just use that specific gid
    if (($this->_mode == self::MODE_REGISTER || $this->_mode == self::MODE_CREATE) && !$this->_gid) {
      $this->_ctype = CRM_Utils_Request::retrieve('ctype', 'String', $this, FALSE, 'Individual', 'REQUEST');
      $this->_fields = CRM_Core_BAO_UFGroup::getRegistrationFields($this->_action, $this->_mode, $this->_ctype);
    }
    elseif ($this->_mode == self::MODE_SEARCH) {
      $this->_fields = CRM_Core_BAO_UFGroup::getListingFields($this->_action,
        CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY | CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
        FALSE,
        $gids,
        TRUE, NULL,
        $this->_skipPermission,
        CRM_Core_Permission::SEARCH
      );
    }
    else {
      $this->_fields = CRM_Core_BAO_UFGroup::getFields($gids, FALSE, NULL,
        NULL, NULL,
        FALSE, NULL,
        $this->_skipPermission,
        NULL,
        ($this->_action == CRM_Core_Action::ADD) ? CRM_Core_Permission::CREATE : CRM_Core_Permission::EDIT
      );
      $multiRecordFieldListing = FALSE;
      //using selector for listing of multi-record fields
      if ($this->_mode == self::MODE_EDIT && $this->_gid) {
        CRM_Core_BAO_UFGroup::shiftMultiRecordFields($this->_fields, $this->_multiRecordFields);

        if ($this->_multiRecord) {
          if ($this->_multiRecord != CRM_Core_Action::ADD) {
            $this->_recordId = CRM_Utils_Request::retrieve('recordId', 'Positive', $this);
          }
          else {
            $this->_recordId = NULL;
            $this->set('recordId', NULL);
          }
          //record id is necessary for _multiRecord view and update/edit action
          if (!$this->_recordId
            && ($this->_multiRecord == CRM_Core_Action::UPDATE || $this->_multiRecord == CRM_Core_Action::DELETE)
          ) {
            CRM_Core_Error::statusBounce(ts('The requested Profile (gid=%1) requires record id while performing this action',
              [1 => $this->_gid]
            ));
          }
          elseif (empty($this->_multiRecordFields)) {
            CRM_Core_Error::statusBounce(ts('No Multi-Record Fields configured for this profile (gid=%1)',
              [1 => $this->_gid]
            ));
          }

          $fieldId = CRM_Core_BAO_CustomField::getKeyID(key($this->_multiRecordFields));
          $customGroupDetails = CRM_Core_BAO_CustomGroup::getGroupTitles([$fieldId]);
          $this->_customGroupTitle = $customGroupDetails[$fieldId]['groupTitle'];
          $this->_customGroupId = $customGroupDetails[$fieldId]['groupID'];

          if ($this->_multiRecord == CRM_Core_Action::UPDATE || $this->_multiRecord == CRM_Core_Action::DELETE) {
            //record exists check
            foreach ($this->_multiRecordFields as $key => $field) {
              $fieldIds[] = CRM_Core_BAO_CustomField::getKeyID($key);
            }
            $getValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_id, NULL, $fieldIds, TRUE);

            if (array_key_exists($this->_recordId, $getValues)) {
              $this->_recordExists = TRUE;
            }
            else {
              $this->_recordExists = FALSE;
              if ($this->_multiRecord & CRM_Core_Action::UPDATE) {
                CRM_Core_Session::setStatus(ts('Note: The record %1 doesnot exists. Upon save a new record will be create', [1 => $this->_recordId]), ts('Record doesnot exist'), 'alert');
              }
            }
          }
          if ($this->_multiRecord & CRM_Core_Action::ADD) {
            $this->_maxRecordLimit = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($customGroupDetails[$fieldId]['groupID'], $this->_id);
            if ($this->_maxRecordLimit) {
              CRM_Core_Session::setStatus(ts('You cannot add a new record as  maximum allowed limit is reached'), ts('Sorry'), 'error');
            }
          }

        }
        elseif (!empty($this->_multiRecordFields) &&
          (!$this->_multiRecord || !in_array($this->_multiRecord, [CRM_Core_Action::DELETE, CRM_Core_Action::UPDATE]))
        ) {
          CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/crm.livePage.js', 1, 'html-header');
          //multi-record listing page
          $multiRecordFieldListing = TRUE;
          $page = new CRM_Profile_Page_MultipleRecordFieldsListing();
          $cs = $this->get('cs');
          $page->set('pageCheckSum', $cs);
          $page->_contactId = $this->_id;
          $page->setProfileID($this->_gid);
          $page->set('action', CRM_Core_Action::BROWSE);
          $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, FALSE);
          // assign vars to templates
          $page->assign('action', $action);
          $page->_pageViewType = 'profileDataView';

          $page->browse();
        }
      }

      // is profile double-opt in?
      if (!empty($this->_fields['group']) &&
        CRM_Core_BAO_UFGroup::isProfileDoubleOptin()
      ) {
        $emailField = FALSE;
        foreach ($this->_fields as $name => $values) {
          if (substr($name, 0, 6) == 'email-') {
            $emailField = TRUE;
          }
        }

        if (!$emailField) {
          $status = ts("Email field should be included in profile if you want to use Group(s) when Profile double-opt in process is enabled.");
          CRM_Core_Session::singleton()->setStatus($status);
        }
      }

      //transferring all the multi-record custom fields in _fields
      if ($this->_multiRecord && !empty($this->_multiRecordFields)) {
        $this->_fields = $this->_multiRecordFields;
        $this->_multiRecordProfile = TRUE;
      }
      elseif ($this->_multiRecord && empty($this->_multiRecordFields)) {
        CRM_Core_Session::setStatus(ts('This feature is not currently available.'), ts('Sorry'), 'error');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm', 'reset=1'));
      }
    }
    $this->assign('multiRecordFieldListing', $multiRecordFieldListing ?? NULL);
    if (!is_array($this->_fields)) {
      CRM_Core_Session::setStatus(ts('This feature is not currently available.'), ts('Sorry'), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm', 'reset=1'));
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   */
  public function setDefaultsValues() {
    $this->_defaults = [];
    if ($this->_multiRecordProfile && ($this->_multiRecord == CRM_Core_Action::DELETE)) {
      return;
    }

    if ($this->_mode != self::MODE_SEARCH) {
      // set default values for country / state to start with
      CRM_Core_BAO_UFGroup::setRegisterDefaults($this->_fields, $this->_defaults);
    }

    if ($this->_id && !$this->_multiRecordProfile) {
      if ($this->_isContactActivityProfile) {
        $contactFields = $activityFields = [];
        foreach ($this->_fields as $fieldName => $field) {
          if (($field['field_type'] ?? NULL) == 'Activity') {
            $activityFields[$fieldName] = $field;
          }
          else {
            $contactFields[$fieldName] = $field;
          }
        }

        CRM_Core_BAO_UFGroup::setProfileDefaults($this->_id, $contactFields, $this->_defaults, TRUE);
        if ($this->_activityId) {
          CRM_Core_BAO_UFGroup::setComponentDefaults($activityFields, $this->_activityId, 'Activity', $this->_defaults, TRUE);
        }
      }
      else {
        CRM_Core_BAO_UFGroup::setProfileDefaults($this->_id, $this->_fields, $this->_defaults, TRUE);
      }
    }

    //set custom field defaults
    if ($this->_multiRecordProfile) {
      foreach ($this->_multiRecordFields as $key => $field) {
        $fieldIds[] = CRM_Core_BAO_CustomField::getKeyID($key);
      }

      $defaultValues = [];
      if ($this->_multiRecord && $this->_multiRecord == CRM_Core_Action::UPDATE) {
        $defaultValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_id, NULL, $fieldIds, TRUE);
        if ($this->_recordExists == TRUE) {
          $defaultValues = $defaultValues[$this->_recordId];
        }
        else {
          $defaultValues = NULL;
        }
      }

      if (!empty($defaultValues)) {
        foreach ($defaultValues as $key => $value) {
          $name = "custom_{$key}";
          $htmlType = $this->_multiRecordFields[$name]['html_type'];
          if ($htmlType != 'File') {
            if (isset($value)) {
              CRM_Core_BAO_CustomField::setProfileDefaults($key,
                $name,
                $this->_defaults,
                $this->_id,
                $this->_mode,
                $value
              );
            }
            else {
              $this->_defaults[$name] = "";
            }
          }

          if ($htmlType == 'File') {
            $entityId = $this->_id;
            if (($field['field_type'] ?? NULL) == 'Activity' &&
              $this->_activityId
            ) {
              $entityId = $this->_activityId;
            }

            $url = '';
            if (isset($value)) {
              $url = CRM_Core_BAO_CustomField::getFileURL($entityId, $key, $value);
            }

            if ($url) {
              $customFiles[$name]['displayURL'] = ts("Attached File") . ": {$url['file_url']}";

              // FIXME: Yikes! Deleting records via GET request??
              $deleteExtra = htmlentities(ts("Are you sure you want to delete attached file?"), ENT_QUOTES);
              $fileId = $url['file_id'];
              $fileHash = CRM_Core_BAO_File::generateFileHash(NULL, $fileId);
              $deleteURL = CRM_Utils_System::url('civicrm/file',
                "reset=1&id={$fileId}&eid=$entityId&fid={$key}&action=delete&fcs={$fileHash}"
              );
              $text = ts("Delete Attached File");
              $customFiles[$field['name']]['deleteURL'] = "<a href=\"{$deleteURL}\" onclick = \"if (confirm( ' $deleteExtra ' )) this.href+='&amp;confirmed=1'; else return false;\">$text</a>";

              // also delete the required rule that we've set on the form element
              $this->removeFileRequiredRules($name);
            }
          }
        }
      }
    }
    else {
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $htmlType = $field['html_type'];
          if ((!isset($this->_defaults[$name]) || $htmlType == 'File') &&
            (($field['field_type'] ?? NULL) != 'Activity')
          ) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID,
              $name,
              $this->_defaults,
              $this->_id,
              $this->_mode
            );
          }

          if ($htmlType == 'File') {
            $entityId = $this->_id;
            if (($field['field_type'] ?? NULL) == 'Activity' && $this->_activityId) {
              $entityId = $this->_activityId;
            }
            $url = CRM_Core_BAO_CustomField::getFileURL($entityId, $customFieldID);

            if ($url) {
              $customFiles[$field['name']]['displayURL'] = ts("Attached File") . ": {$url['file_url']}";

              // FIXME: Yikes! Deleting records via GET request??
              $deleteExtra = htmlentities(ts("Are you sure you want to delete attached file?"), ENT_QUOTES);
              $fileId = $url['file_id'];
              $fileHash = CRM_Core_BAO_File::generateFileHash(NULL, $fileId);
              $deleteURL = CRM_Utils_System::url('civicrm/file',
                "reset=1&id={$fileId}&eid=$entityId&fid={$customFieldID}&action=delete&fcs={$fileHash}"
              );
              $text = ts("Delete Attached File");
              $customFiles[$field['name']]['deleteURL'] = "<a href=\"{$deleteURL}\" onclick = \"if (confirm( ' $deleteExtra ' )) this.href+='&amp;confirmed=1'; else return false;\">$text</a>";

              // also delete the required rule that we've set on the form element
              $this->removeFileRequiredRules($field['name']);
            }
          }
        }
      }
    }
    if (isset($customFiles)) {
      $this->assign('customFiles', $customFiles);
    }

    if ($this->_multiRecordProfile) {
      $this->setDefaults($this->_defaults);
      return;
    }

    if (!empty($this->_defaults['image_URL'])) {
      $this->assign("imageURL", CRM_Utils_File::getImageURL($this->_defaults['image_URL']));
      $this->removeFileRequiredRules('image_URL');
    }

    $this->setDefaults($this->_defaults);
  }

  /**
   * Build the form object.
   *
   */
  public function buildQuickForm(): void {
    $this->add('hidden', 'gid', $this->_gid);
    $this->assign('deleteRecord', $this->isDeleteMode());

    switch ($this->_mode) {
      case self::MODE_CREATE:
      case self::MODE_EDIT:
      case self::MODE_REGISTER:
        CRM_Utils_Hook::buildProfile($this->_ufGroup['name']);
        break;

      case self::MODE_SEARCH:
        CRM_Utils_Hook::searchProfile($this->_ufGroup['name']);
        break;

      default:
    }

    //lets have single status message, CRM-4363
    $return = FALSE;
    $statusMessage = NULL;
    if (($this->_multiRecord & CRM_Core_Action::ADD) && $this->_maxRecordLimit) {
      return;
    }

    if ($this->isDeleteMode()) {
      if (!$this->_recordExists) {
        CRM_Core_Session::setStatus(ts('The record %1 doesnot exists', [1 => $this->_recordId]), ts('Record doesnot exists'), 'alert');
      }
      return;
    }

    CRM_Core_BAO_Address::checkContactSharedAddressFields($this->_fields, $this->_id);

    // we should not allow component and mix profiles in search mode
    if ($this->_mode != self::MODE_REGISTER) {
      //check for mix profile fields (eg:  individual + other contact type)
      if (CRM_Core_BAO_UFField::checkProfileType($this->_gid)) {
        if (($this->_mode & self::MODE_EDIT) && $this->_isContactActivityProfile) {
          $errors = self::validateContactActivityProfile($this->_activityId, $this->_id, $this->_gid);
          if (!empty($errors)) {
            $statusMessage = array_pop($errors);
            $return = TRUE;
          }
        }
        else {
          $statusMessage = ts('Profile search, view and edit are not supported for Profiles which include fields for more than one record type.');
          $return = TRUE;
        }
      }

      $profileType = CRM_Core_BAO_UFField::getProfileType($this->_gid);

      if ($this->_id) {
        $contactTypes = CRM_Contact_BAO_Contact::getContactTypes($this->_id);
        $contactType = $contactTypes[0];

        array_shift($contactTypes);
        $contactSubtypes = $contactTypes;

        $profileSubType = FALSE;
        if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
          $profileSubType = $profileType;
          $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
        }

        if (
          ($profileType != 'Contact' && !$this->_isContactActivityProfile) &&
          (($profileSubType && !empty($contactSubtypes) && (!in_array($profileSubType, $contactSubtypes))) ||
            ($profileType != $contactType))
        ) {
          $return = TRUE;
          if (!$statusMessage) {
            $statusMessage = ts("This profile is configured for contact type '%1'. It cannot be used to edit contacts of other types.",
                [1 => $profileSubType ?: $profileType]);
          }
        }
      }

      if (
      in_array(
        $profileType,
        ["Membership", "Participant", "Contribution"]
      )
      ) {
        $return = TRUE;
        if (!$statusMessage) {
          $statusMessage = ts('Profile is not configured for the selected action.');
        }
      }
    }

    //lets have single status message,
    $this->assign('statusMessage', $statusMessage);
    if ($return) {
      return;
    }

    $this->assign('id', $this->_id);
    $this->assign('mode', $this->_mode);
    $this->assign('isHideFieldSet', ($this->_mode === self::MODE_CREATE || $this->_mode === self::MODE_EDIT));
    $this->assign('action', $this->_action);
    $this->assign('fields', $this->_fields);
    $this->assign('fieldset', '');

    // should we restrict what we display
    $admin = TRUE;
    if ($this->_mode == self::MODE_EDIT) {
      $admin = FALSE;
      // show all fields that are visible:
      // if we are a admin OR the same user OR acl-user with access to the profile
      // or we have checksum access to this contact (i.e. the user without a login) - CRM-5909
      if (
        CRM_Core_Permission::check('cms:administer users') ||
        $this->_id == $this->_currentUserID ||
        $this->_isPermissionedChecksum ||
        in_array(
          $this->_gid,
          CRM_ACL_API::group(
            CRM_Core_Permission::EDIT,
            NULL,
            'civicrm_uf_group',
            CRM_Core_DAO_UFField::buildOptions('uf_group_id')
          )
        )
      ) {
        $admin = TRUE;
      }
    }

    // if false, user is not logged-in.
    $anonUser = FALSE;
    if (!$this->_currentUserID) {
      $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
      $primaryLocationType = $defaultLocationType->id;
      $anonUser = TRUE;
    }
    $this->assign('anonUser', $anonUser);

    $emailPresent = FALSE;

    // add the form elements
    foreach ($this->_fields as $name => $field) {
      // make sure that there is enough permission to expose this field
      if (!$admin && $field['visibility'] == 'User and User Admin Only') {
        unset($this->_fields[$name]);
        continue;
      }

      // since the CMS manages the email field, suppress the email display if in
      // register mode which occur within the CMS form
      if ($this->_mode == self::MODE_REGISTER && substr($name, 0, 5) == 'email') {
        unset($this->_fields[$name]);
        continue;
      }

      [$prefixName, $index] = CRM_Utils_System::explode('-', $name, 2);

      CRM_Core_BAO_UFGroup::buildProfile($this, $field, $this->_mode);

      if ($field['add_to_group_id']) {
        $addToGroupId = $field['add_to_group_id'];
      }

      if (($name == 'email-Primary') || ($name == 'email-' . ($primaryLocationType ?? ""))) {
        $emailPresent = TRUE;
        $this->_mail = $name;
      }
    }

    if ($this->_mode == self::MODE_CREATE) {
      if ($this->_gid) {
        $dao = new CRM_Core_DAO_UFGroup();
        $dao->id = $this->_gid;
        $dao->addSelect();
        $dao->addSelect('is_update_dupe');
        if ($dao->find(TRUE)) {
          if ($dao->is_update_dupe) {
            $this->_isUpdateDupe = $dao->is_update_dupe;
          }
        }
      }
    }

    if ($this->_mode != self::MODE_SEARCH) {
      if (isset($addToGroupId)) {
        $this->_ufGroup['add_to_group_id'] = $addToGroupId;
      }
    }

    //let's do set defaults for the profile
    $this->setDefaultsValues();

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, NULL);
    $this->assign('showCMS', FALSE);
    if ($this->_mode == self::MODE_CREATE || $this->_mode == self::MODE_EDIT) {
      CRM_Core_BAO_CMSUser::buildForm($this, $this->_gid, $emailPresent, $action);
    }

    $this->assign('groupId', $this->_gid);

    // if view mode pls freeze it with the done button.
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }

    // Assign FALSE, here - this is overwritten during form validation
    // if duplicates are found during submit.
    CRM_Core_Smarty::singleton()->assign('showSaveDuplicateButton', FALSE);

    if ($this->isEntityReferenceContactCreateMode()) {
      $this->addElement(
        'xbutton',
        $this->getButtonName('upload', 'duplicate'),
        ts('Save Matching Contact'),
        [
          'type' => 'submit',
          'class' => 'crm-button',
        ]
      );
    }
  }

  /**
   * Validate profile and provided activity Id.
   *
   * @param int $activityId
   * @param int $contactId
   * @param int $gid
   *
   * @return array
   */
  public static function validateContactActivityProfile($activityId, $contactId, $gid) {
    $errors = [];
    if (!$activityId) {
      $errors[] = ts('Profile is using one or more activity fields, and is missing the activity Id (aid) in the URL.');
      return $errors;
    }

    $activityDetails = [];
    $activityParams = ['id' => $activityId];
    CRM_Activity_BAO_Activity::retrieve($activityParams, $activityDetails);

    if (empty($activityDetails)) {
      $errors[] = ts('Invalid Activity Id (aid).');
      return $errors;
    }

    $profileActivityTypes = CRM_Core_BAO_UFGroup::groupTypeValues($gid, 'Activity');

    if ((!empty($profileActivityTypes['Activity']) &&
        !in_array($activityDetails['activity_type_id'], $profileActivityTypes['Activity'])
      ) ||
      (!in_array($contactId, $activityDetails['assignee_contact']) &&
        !in_array($contactId, $activityDetails['target_contact'])
      )
    ) {
      $errors[] = ts('This activity cannot be edited or viewed via this profile.');
    }

    return $errors;
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Profile_Form $form
   *   The form object.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $form) {
    CRM_Utils_Hook::validateProfile($form->_ufGroup['name']);

    // if no values, return
    if (empty($fields)) {
      return TRUE;
    }

    $errors = [];
    $register = NULL;

    // hack we use a -1 in options to indicate that its registration
    // ... and I can't remove that comment because even though it's clear as mud
    // perhaps someone will find it helpful in the absence of ANY OTHER EXPLANATION
    // as to what it means....
    if ($form->_id) {
      // @todo - wonder if it ever occurred to someone that if they didn't document this param
      // it might not be crystal clear why we have it....
      $form->_isUpdateDupe = 1;
    }

    if ($form->_mode == CRM_Profile_Form::MODE_REGISTER) {
      $register = TRUE;
    }

    // don't check for duplicates during registration validation: CRM-375
    if (!$register && !array_key_exists('_qf_Edit_upload_duplicate', $fields)) {
      // fix for CRM-3240
      if (!empty($fields['email-Primary'])) {
        $fields['email'] = $fields['email-Primary'] ?? NULL;
      }

      // fix for CRM-6141
      if (!empty($fields['phone-Primary-1']) && empty($fields['phone-Primary'])) {
        $fields['phone-Primary'] = $fields['phone-Primary-1'];
      }

      if (!$form->_id) {
        self::handleDuplicateChecking($errors, $fields, $form);
      }
    }

    foreach ($fields as $key => $value) {
      [$fieldName, $locTypeId, $phoneTypeId] = CRM_Utils_System::explode('-', $key, 3);
      if ($fieldName == 'state_province' && !empty($fields["country-{$locTypeId}"])) {
        // Validate Country - State list
        $countryId = $fields["country-{$locTypeId}"];
        $stateProvinceId = $value;

        if ($stateProvinceId && $countryId) {
          $stateProvinceDAO = new CRM_Core_DAO_StateProvince();
          $stateProvinceDAO->id = $stateProvinceId;
          $stateProvinceDAO->find(TRUE);

          if ($stateProvinceDAO->country_id != $countryId) {
            // country mismatch hence display error
            $stateProvinces = CRM_Core_PseudoConstant::stateProvince();
            $countries = CRM_Core_PseudoConstant::country();
            $errors[$key] = "State/Province " . $stateProvinces[$stateProvinceId] . " is not part of " . $countries[$countryId] . ". It belongs to " . $countries[$stateProvinceDAO->country_id] . ".";
          }
        }
      }

      if ($fieldName == 'county' && $fields["state_province-{$locTypeId}"]) {
        // Validate County - State list
        $stateProvinceId = $fields["state_province-{$locTypeId}"];
        $countyId = $value;

        if ($countyId && $stateProvinceId) {
          $countyDAO = new CRM_Core_DAO_County();
          $countyDAO->id = $countyId;
          $countyDAO->find(TRUE);

          if ($countyDAO->state_province_id != $stateProvinceId) {
            // state province mismatch hence display error
            $stateProvinces = CRM_Core_PseudoConstant::stateProvince();
            $counties = CRM_Core_PseudoConstant::county();
            $errors[$key] = "County " . $counties[$countyId] . " is not part of " . $stateProvinces[$stateProvinceId] . ". It belongs to " . $stateProvinces[$countyDAO->state_province_id] . ".";
          }
        }
      }
    }
    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      $greetingType = $fields[$greeting] ?? NULL;
      if ($greetingType) {
        $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greeting . '_id', 'Customized');
        if ($customizedValue == $greetingType && empty($fields[$greeting . '_custom'])) {
          $errors[$greeting . '_custom'] = ts('Custom  %1 is a required field if %1 is of type Customized.',
            [1 => ucwords(str_replace('_', ' ', $greeting))]
          );
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the user submitted custom data values.
   *
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    //if the delete record button is clicked
    if ($this->_deleteButtonName) {
      if (!empty($_POST[$this->_deleteButtonName]) && $this->_recordId) {
        $filterParams['id'] = $this->_customGroupId;
        $returnProperties = ['is_multiple', 'table_name'];
        CRM_Core_DAO::commonRetrieve("CRM_Core_DAO_CustomGroup", $filterParams, $returnValues, $returnProperties);
        if (!empty($returnValues['is_multiple'])) {
          $tableName = $returnValues['table_name'] ?? NULL;
          if ($tableName) {
            $sql = "DELETE FROM {$tableName} WHERE id = %1 AND entity_id = %2";
            $sqlParams = [
              1 => [$this->_recordId, 'Integer'],
              2 => [$this->_id, 'Integer'],
            ];
            CRM_Core_DAO::executeQuery($sql, $sqlParams);
            CRM_Core_Session::setStatus(ts('Your record has been deleted.'), ts('Deleted'), 'success');
          }
        }
        return;
      }
    }
    CRM_Utils_Hook::processProfile($this->_ufGroup['name']);
    if (!empty($params['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    $greetingTypes = [
      'addressee' => 'addressee_id',
      'email_greeting' => 'email_greeting_id',
      'postal_greeting' => 'postal_greeting_id',
    ];

    $details = [];
    if ($this->_id) {
      $contactDetails = CRM_Contact_BAO_Contact::getHierContactDetails($this->_id,
        $greetingTypes
      );
      $details = $contactDetails[$this->_id];
    }
    if (!(!empty($details['addressee_id']) || !empty($details['email_greeting_id']) ||
      !empty($details['postal_greeting_id'])
    )
    ) {

      $profileType = CRM_Core_BAO_UFField::getProfileType($this->_gid);
      //Though Profile type is contact we need
      //Individual/Household/Organization for setting Greetings.
      if ($profileType == 'Contact') {
        $profileType = 'Individual';
        //if we editing Household/Organization.
        if ($this->_id) {
          $profileType = CRM_Contact_BAO_Contact::getContactType($this->_id);
        }
      }
      if (CRM_Contact_BAO_ContactType::isaSubType($profileType)) {
        $profileType = CRM_Contact_BAO_ContactType::getBasicType($profileType);
      }

      foreach ($greetingTypes as $key => $value) {
        if (!array_key_exists($key, $params)) {
          $params[$key] = CRM_Contact_BAO_Contact_Utils::defaultGreeting($profileType, $key);
        }
      }
    }

    $transaction = new CRM_Core_Transaction();

    //used to send subscribe mail to the group which user want.
    //if the profile double option in is enabled
    $mailingType = [];

    $result = NULL;
    foreach ($params as $name => $values) {
      if (substr($name, 0, 6) == 'email-') {
        $result['email'] = $values;
      }
    }

    //array of group id, subscribed by contact
    $contactGroup = [];
    if (!empty($params['group']) &&
      CRM_Core_BAO_UFGroup::isProfileDoubleOptin()
    ) {
      $groupSubscribed = [];
      if (!empty($result['email'])) {
        if ($this->_id) {
          $contactGroups = new CRM_Contact_DAO_GroupContact();
          $contactGroups->contact_id = $this->_id;
          $contactGroups->status = 'Added';
          $contactGroups->find();
          $contactGroup = [];
          while ($contactGroups->fetch()) {
            $contactGroup[] = $contactGroups->group_id;
            $groupSubscribed[$contactGroups->group_id] = 1;
          }
        }
        foreach ($params['group'] as $key => $val) {
          if (!$val) {
            unset($params['group'][$key]);
            continue;
          }
          $groupTypes = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
            $key, 'group_type', 'id'
          );
          $groupType = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            substr($groupTypes, 1, -1)
          );
          //filter group of mailing type and unset it from params
          if (in_array(2, $groupType)) {
            //if group is already subscribed , ignore it
            $groupExist = CRM_Utils_Array::key($key, $contactGroup);
            if (!isset($groupExist)) {
              $mailingType[] = $key;
              unset($params['group'][$key]);
            }
          }
        }
      }
    }

    $addToGroupId = $this->_ufGroup['add_to_group_id'] ?? NULL;
    if (!empty($addToGroupId)) {
      //run same check whether group is a mailing list
      $groupTypes = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
        $addToGroupId, 'group_type', 'id'
      );
      $groupType = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($groupTypes, 1, -1)
      );
      //filter group of mailing type and unset it from params
      if (in_array(2, $groupType) && !empty($result['email']) &&
        CRM_Core_BAO_UFGroup::isProfileAddToGroupDoubleOptin()
      ) {
        if (!count($contactGroup)) {
          //array of group id, subscribed by contact
          $contactGroup = [];
          if ($this->_id) {
            $contactGroups = new CRM_Contact_DAO_GroupContact();
            $contactGroups->contact_id = $this->_id;
            $contactGroups->status = 'Added';
            $contactGroups->find();
            $contactGroup = [];
            while ($contactGroups->fetch()) {
              $contactGroup[] = $contactGroups->group_id;
              $groupSubscribed[$contactGroups->group_id] = 1;
            }
          }
        }
        //if group is already subscribed , ignore it
        $groupExist = CRM_Utils_Array::key($addToGroupId, $contactGroup);
        if (!isset($groupExist)) {
          $mailingType[] = $addToGroupId;
          $addToGroupId = NULL;
        }
      }
      else {
        // since we are directly adding contact to group lets unset it from mailing
        if ($key = array_search($addToGroupId, $mailingType)) {
          unset($mailingType[$key]);
        }
      }
    }

    if ($this->_grid) {
      $params['group'] = $groupSubscribed;
    }

    // commenting below code, since we potentially
    // triggered maximum name field formatting cases during CRM-4430.
    // CRM-4343
    // $params['preserveDBName'] = true;

    $profileFields = $this->_fields;
    if (($this->_mode & self::MODE_EDIT) && $this->_activityId && $this->_isContactActivityProfile) {
      $profileFields = $activityParams = [];
      foreach ($this->_fields as $fieldName => $field) {
        if (($field['field_type'] ?? NULL) == 'Activity') {
          if (isset($params[$fieldName])) {
            $activityParams[$fieldName] = $params[$fieldName];
          }
          if (isset($params['activity_date_time'])) {
            $activityParams['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time'], $params['activity_date_time_time']);
          }
          if (!empty($params[$fieldName]) && isset($params["{$fieldName}_id"])) {
            $activityParams[$fieldName] = $params["{$fieldName}_id"];
          }
        }
        else {
          $profileFields[$fieldName] = $field;
        }
      }

      if (!empty($activityParams)) {
        $activityParams['version'] = 3;
        $activityParams['id'] = $this->_activityId;
        $activityParams['skipRecentView'] = TRUE;
        civicrm_api('Activity', 'create', $activityParams);
      }
    }

    if ($this->_multiRecord && $this->_recordId && $this->_multiRecordFields && $this->_recordExists) {
      $params['customRecordValues'][$this->_recordId] = array_keys($this->_multiRecordFields);
    }

    $this->_id = CRM_Contact_BAO_Contact::createProfileContact(
      $params,
      $profileFields,
      $this->_id,
      $addToGroupId,
      $this->_gid,
      $this->_ctype,
      TRUE
    );

    //mailing type group
    if (!empty($mailingType)) {
      // we send in the contactID so we match the same groups and are exact, rather than relying on email
      // CRM-8710
      CRM_Mailing_Event_BAO_MailingEventSubscribe::commonSubscribe($mailingType, $result, $this->_id, 'profile');
    }

    $ufGroups = [];
    if ($this->_gid) {
      $ufGroups[$this->_gid] = 1;
    }
    elseif ($this->_mode == self::MODE_REGISTER) {
      $ufGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup('User Registration');
    }

    foreach ($ufGroups as $gId => $val) {
      if ($notify = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify')) {
        $values = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $this->_id, NULL);
        CRM_Core_BAO_UFGroup::commonSendMail($this->_id, $values);
      }
    }

    //create CMS user (if CMS user option is selected in profile)
    if (!empty($params['cms_create_account']) &&
      ($this->_mode == self::MODE_CREATE || $this->_mode == self::MODE_EDIT)
    ) {
      $params['contactID'] = $this->_id;
      if (!CRM_Core_BAO_CMSUser::create($params, $this->_mail)) {
        CRM_Core_Session::setStatus(ts('Your profile is not saved and Account is not created.'), ts('Profile Error'), 'error');
        CRM_Core_Error::debug_log_message("Rolling back transaction as CMSUser Create failed in Profile_Form for contact " . $params['contactID']);
        $transaction->rollback();
        return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/profile/create',
          'reset=1&gid=' . $this->_gid
        ));
      }
    }

    $transaction->commit();
  }

  /**
   * Check template file exists.
   *
   * @param string|null $suffix
   *
   * @return string|null
   *   Template file path, else null
   */
  public function checkTemplateFileExists($suffix = NULL) {
    if ($this->_gid) {
      $templateFile = "CRM/Profile/Form/{$this->_gid}/{$this->_name}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Form/{$ufGroupName}/{$this->_name}.{$suffix}tpl";
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ?: parent::getTemplateFileName();
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ?: parent::overrideExtraTemplateFileName();
  }

  /**
   * @return int|string
   */
  private function isDeleteMode() {
    return ($this->_multiRecord & CRM_Core_Action::DELETE);
  }

}
