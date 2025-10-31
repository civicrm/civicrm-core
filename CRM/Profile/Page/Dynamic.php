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

use Civi\Api4\Email;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Create a page for displaying CiviCRM Profile Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Profile_Page_Dynamic extends CRM_Core_Page {

  /**
   * The contact id of the person we are viewing.
   *
   * @var int
   */
  protected $_id;

  /**
   * The profile group are are interested in.
   *
   * @var int
   */
  protected $_gid;

  /**
   * The profile types we restrict this page to display.
   *
   * @var string
   */
  protected $_restrict;

  /**
   * Should we bypass permissions.
   *
   * @var bool
   */
  protected $_skipPermission;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
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

  protected $_multiRecord = NULL;

  protected $_recordId = NULL;

  /**
   * Should the primary email be converted into a link, if emailabe.
   *
   * @var bool
   */
  protected $isShowEmailTaskLink = FALSE;

  /**
   *
   * fetch multirecord as well as non-multirecord fields
   * @var int
   */
  protected $_allFields = NULL;

  /**
   * Class constructor.
   *
   * @param int $id
   *   The contact id.
   * @param int $gid
   *   The group id.
   *
   * @param $restrict
   * @param bool $skipPermission
   * @param int[]|null $profileIds
   *
   * @param bool $isShowEmailTaskLink
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($id, $gid, $restrict, $skipPermission = FALSE, $profileIds = NULL, $isShowEmailTaskLink = FALSE) {
    parent::__construct();

    $this->_id = $id;
    $this->_gid = $gid;
    $this->_restrict = $restrict;
    $this->_skipPermission = $skipPermission;
    $this->isShowEmailTaskLink = $isShowEmailTaskLink;

    if (!array_key_exists('multiRecord', $_GET)) {
      $this->set('multiRecord', NULL);
    }
    if (!array_key_exists('recordId', $_GET)) {
      $this->set('recordId', NULL);
    }
    if (!array_key_exists('allFields', $_GET)) {
      $this->set('allFields', NULL);
    }

    //specifies the action being done on a multi record field
    $multiRecordAction = CRM_Utils_Request::retrieve('multiRecord', 'String', $this);

    $this->_multiRecord = (!is_numeric($multiRecordAction)) ? CRM_Core_Action::resolve($multiRecordAction) : $multiRecordAction;
    if ($this->_multiRecord) {
      $this->set('multiRecord', $this->_multiRecord);
    }

    if ($this->_multiRecord & CRM_Core_Action::VIEW) {
      $this->_recordId = CRM_Utils_Request::retrieve('recordId', 'Positive', $this);
      $this->_allFields = CRM_Utils_Request::retrieve('allFields', 'Integer', $this);
    }

    if ($profileIds) {
      $this->_profileIds = $profileIds;
    }
    else {
      $this->_profileIds = [$gid];
    }

    $this->_activityId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE, 0, 'GET');
    if (is_numeric($this->_activityId)) {
      $latestRevisionId = CRM_Activity_BAO_Activity::getLatestActivityId($this->_activityId);
      if ($latestRevisionId) {
        $this->_activityId = $latestRevisionId;
      }
    }
    $this->_isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($this->_gid);
  }

  /**
   * Get the action links for this page.
   *
   * @return array
   */
  public function &actionLinks() {
    return NULL;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   */
  public function run() {
    $template = CRM_Core_Smarty::singleton();
    if ($this->_id && $this->_gid) {

      // first check that id is part of the limit group id, CRM-4822
      $limitListingsGroupsID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
        $this->_gid,
        'limit_listings_group_id'
      );
      $config = CRM_Core_Config::singleton();
      if ($limitListingsGroupsID) {

        if (!CRM_Contact_BAO_GroupContact::isContactInGroup($this->_id,
          $limitListingsGroupsID
        )
        ) {
          CRM_Utils_System::setTitle(ts('Profile View - Permission Denied'));
          return CRM_Core_Session::setStatus(ts('You do not have permission to view this contact record. Contact the site administrator if you need assistance.'), ts('Permission Denied'), 'error');
        }
      }

      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');

      $isPermissionedChecksum = $allowPermission = FALSE;
      $permissionType = CRM_Core_Permission::VIEW;
      if (CRM_Core_Permission::check('cms:administer users') || CRM_Core_Permission::check('view all contacts') || CRM_Contact_BAO_Contact_Permission::allow($this->_id)) {
        $allowPermission = TRUE;
      }
      if ($this->_id != $userID) {
        // do not allow edit for anon users in joomla frontend, CRM-4668, unless u have checksum CRM-5228
        if ($config->userFrameworkFrontend) {
          $isPermissionedChecksum = CRM_Contact_BAO_Contact_Permission::validateOnlyChecksum($this->_id, $this, FALSE);
          if (!$isPermissionedChecksum) {
            $isPermissionedChecksum = $allowPermission;
          }
        }
        else {
          $isPermissionedChecksum = CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->_id, $this, FALSE);
        }
      }
      // CRM-10853
      // Users with create or edit permission should be allowed to view their own profile
      if ($this->_id == $userID || $isPermissionedChecksum) {
        if (!CRM_Core_Permission::check('profile view')) {
          if (CRM_Core_Permission::check('profile create') || CRM_Core_Permission::check('profile edit')) {
            $this->_skipPermission = TRUE;
          }
        }
      }

      // make sure we dont expose all fields based on permission
      $admin = FALSE;
      if ((!$config->userFrameworkFrontend && $allowPermission) ||
        $this->_id == $userID ||
        $isPermissionedChecksum
      ) {
        $admin = TRUE;
      }

      $values = [];
      $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileIds, FALSE, CRM_Core_Action::VIEW,
        NULL, NULL, FALSE, $this->_restrict,
        $this->_skipPermission, NULL,
        $permissionType
      );

      if ($this->_multiRecord & CRM_Core_Action::VIEW && $this->_recordId && !$this->_allFields) {
        CRM_Core_BAO_UFGroup::shiftMultiRecordFields($fields, $multiRecordFields);
        $fields = $multiRecordFields;
      }
      if ($this->_isContactActivityProfile && $this->_gid) {
        $errors = CRM_Profile_Form::validateContactActivityProfile($this->_activityId, $this->_id, $this->_gid);
        if (!empty($errors)) {
          CRM_Core_Error::statusBounce(array_pop($errors));
        }
      }

      //reformat fields array
      foreach ($fields as $name => $field) {
        // also eliminate all formatting fields
        if (($field['field_type'] ?? NULL) == 'Formatting') {
          unset($fields[$name]);
        }

        // make sure that there is enough permission to expose this field
        if (!$admin && $field['visibility'] == 'User and User Admin Only') {
          unset($fields[$name]);
        }
      }

      if ($this->_isContactActivityProfile) {
        $contactFields = $activityFields = [];

        foreach ($fields as $fieldName => $field) {
          if (($field['field_type'] ?? NULL) == 'Activity') {
            $activityFields[$fieldName] = $field;
          }
          else {
            $contactFields[$fieldName] = $field;
          }
        }

        CRM_Core_BAO_UFGroup::getValues($this->_id, $contactFields, $values);
        if ($this->_activityId) {
          CRM_Core_BAO_UFGroup::getValues(
            NULL,
            $activityFields,
            $values,
            TRUE,
            [['activity_id', '=', $this->_activityId, 0, 0]]
          );
        }
      }
      else {
        $customWhereClause = NULL;
        if ($this->_multiRecord & CRM_Core_Action::VIEW && $this->_recordId) {
          if ($this->_allFields) {
            $copyFields = $fields;
            CRM_Core_BAO_UFGroup::shiftMultiRecordFields($copyFields, $multiRecordFields);
            $fieldKey = key($multiRecordFields);
          }
          else {
            $fieldKey = key($fields);
          }
          if ($fieldID = CRM_Core_BAO_CustomField::getKeyID($fieldKey)) {
            $tableColumnGroup = CRM_Core_BAO_CustomField::getTableColumnGroup($fieldID);
            $columnName = "{$tableColumnGroup[0]}.id";
            $customWhereClause = $columnName . ' = ' . $this->_recordId;
          }
        }
        CRM_Core_BAO_UFGroup::getValues($this->_id, $fields, $values, TRUE, NULL, FALSE, $customWhereClause);
      }

      // $profileFields array can be used for customized display of field labels and values in Profile/View.tpl
      $profileFields = [];
      $labels = [];

      foreach ($fields as $name => $field) {
        //CRM-14338
        // Create a unique, non-empty index for each field.
        $index = $field['title'];
        if ($index === '') {
          $index = ' ';
        }
        while (array_key_exists($index, $labels)) {
          $index .= ' ';
        }

        $labels[$index] = preg_replace('/\s+|\W+/', '_', $name);
      }

      if ($this->isShowEmailTaskLink) {
        foreach ($this->getEmailFields($fields) as $fieldName) {
          $values[$fields[$fieldName]['title']] = $this->getLinkedEmail($values[$fields[$fieldName]['title']]);
        }
      }
      foreach ($values as $title => $value) {
        $profileFields[$labels[$title]] = [
          'label' => $title,
          'value' => $value,
        ];
      }

      $template->assign('row', $values);
      $template->assign('profileFields', $profileFields);
    }

    $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
    $this->assign('ufGroupName', $name);
    CRM_Utils_Hook::viewProfile($name);

    $template->assign('overlayProfile', (strtolower($name) === 'summary_overlay'));

    if (($this->_multiRecord & CRM_Core_Action::VIEW) && $this->_recordId && !$this->_allFields) {
      $fieldDetail = reset($fields);
      $fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldDetail['name']);
      $customGroupDetails = CRM_Core_BAO_CustomGroup::getGroupTitles([$fieldId]);
      $multiRecTitle = $customGroupDetails[$fieldId]['groupTitle'];
    }
    else {
      $title = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'title');
    }

    //CRM-4131.
    $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_id, 'display_name');
    if ($displayName) {
      $session = CRM_Core_Session::singleton();
      $config = CRM_Core_Config::singleton();
      if ($session->get('userID') &&
        CRM_Core_Permission::check('access CiviCRM') &&
        CRM_Contact_BAO_Contact_Permission::allow($session->get('userID'), CRM_Core_Permission::VIEW) &&
        !$config->userFrameworkFrontend
      ) {
        $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "action=view&reset=1&cid={$this->_id}", TRUE);
        $this->assign('displayName', $displayName);
        $displayName = "<a href=\"$contactViewUrl\">{$displayName}</a>";
      }
      $title .= ' - ' . $displayName;
    }

    $title = isset($multiRecTitle) ? ts('View %1 Record', [1 => $multiRecTitle]) : $title;
    CRM_Utils_System::setTitle($title);

    // invoke the pagRun hook, CRM-3906
    CRM_Utils_Hook::pageRun($this);

    return trim($template->fetch($this->getHookedTemplateFileName()));
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
      $templateFile = "CRM/Profile/Page/{$this->_gid}/Dynamic.{$suffix}tpl";
      $template = CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Page/{$ufGroupName}/Dynamic.{$suffix}tpl";
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
   * Get the email field as a task link, if not on hold or set to do_not_email.
   *
   * @param string $email
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getLinkedEmail($email): string {
    if (!$email) {
      return '';
    }
    $emailID = Email::get()->setOrderBy(['is_primary' => 'DESC'])->setWhere([['contact_id', '=', $this->_id], ['email', '=', $email], ['on_hold', '=', FALSE], ['contact_id.is_deceased', '=', FALSE], ['contact_id.is_deleted', '=', FALSE], ['contact_id.do_not_email', '=', FALSE]])->execute()->first()['id'];
    if (!$emailID) {
      return $email;
    }
    $emailPopupUrl = CRM_Utils_System::url('civicrm/activity/email/add', [
      'action' => 'add',
      'reset' => '1',
      'email_id' => $emailID,
    ], TRUE);

    return '<a class="crm-popup" href="' . $emailPopupUrl . '">' . $email . '</a>';
  }

  /**
   * Get the email fields from within the fields array.
   *
   * @param array $fields
   */
  protected function getEmailFields(array $fields): array {
    $emailFields = [];
    foreach (array_keys($fields) as $fieldName) {
      if (substr($fieldName, 0, 6) === 'email-'
          && (is_numeric(substr($fieldName, 6)) || substr($fieldName, 6) ===
        'Primary')) {
        $emailFields[] = $fieldName;
      }
    }
    return $emailFields;
  }

}
