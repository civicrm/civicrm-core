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
   * The contact id of the person we are viewing
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * the profile group are are interested in
   *
   * @var int
   * @access protected
   */
  protected $_gid;

  /**
   * The profile types we restrict this page to display
   *
   * @var string
   * @access protected
   */
  protected $_restrict;

  /**
   * Should we bypass permissions
   *
   * @var boolean
   * @access protected
   */
  protected $_skipPermission;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
   */
  protected $_profileIds = array();

  /**
   * Contact profile having activity fields?
   *
   * @var string
   */
  protected $_isContactActivityProfile = FALSE;

  /**
   * Activity Id connected to the profile
   *
   * @var string
   */
  protected $_activityId = NULL;

  protected $_multiRecord = NULL;

  protected $_recordId = NULL;
  
  /*
   * fetch multirecord as well as non-multirecord fields
   */
  protected $_allFields = NULL;

  /**
   * class constructor
   *
   * @param int $id  the contact id
   * @param int $gid the group id
   *
   * @return void
   * @access public
   */
  function __construct($id, $gid, $restrict, $skipPermission = FALSE, $profileIds = NULL) {
    parent::__construct();

    $this->_id = $id;
    $this->_gid = $gid;
    $this->_restrict = $restrict;
    $this->_skipPermission = $skipPermission;

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
    
    $this->_multiRecord = (!is_numeric($multiRecordAction)) ? 
      CRM_Core_Action::resolve($multiRecordAction) : $multiRecordAction;
    if ($this->_multiRecord) {
      $this->set('multiRecord', $this->_multiRecord);
    }
  
    if ($this->_multiRecord & CRM_Core_Action::VIEW) {
      $this->_recordId  = CRM_Utils_Request::retrieve('recordId', 'Positive', $this);
      $this->_allFields = CRM_Utils_Request::retrieve('allFields', 'Integer', $this);
    }
    
    if ($profileIds) {
      $this->_profileIds = $profileIds;
    }
    else {
      $this->_profileIds = array($gid);
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
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    return NULL;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   * @return void
   * @access public
   *
   */
  function run() {
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
          )) {
          CRM_Utils_System::setTitle(ts('Profile View - Permission Denied'));
          return CRM_Core_Session::setStatus(ts('You do not have permission to view this contact record. Contact the site administrator if you need assistance.'), ts('Permission Denied'), 'error');
        }
      }

      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');

      $this->_isPermissionedChecksum = FALSE;
      $permissionType = CRM_Core_Permission::VIEW;
      if ($this->_id != $userID) {
        // do not allow edit for anon users in joomla frontend, CRM-4668, unless u have checksum CRM-5228
        if ($config->userFrameworkFrontend) {
          $this->_isPermissionedChecksum = CRM_Contact_BAO_Contact_Permission::validateOnlyChecksum($this->_id, $this, FALSE);
        }
        else {
          $this->_isPermissionedChecksum = CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->_id, $this, FALSE);
        }
      }
      // CRM-10853
      // Users with create or edit permission should be allowed to view their own profile
      if ($this->_id == $userID || $this->_isPermissionedChecksum) {
        if (!CRM_Core_Permission::check('profile view')) {
          if (CRM_Core_Permission::check('profile create') || CRM_Core_Permission::check('profile edit')) {
            $this->_skipPermission = TRUE;
          }
        }
      }

      // make sure we dont expose all fields based on permission
      $admin = FALSE;
      if ((!$config->userFrameworkFrontend &&
          (CRM_Core_Permission::check('administer users') ||
            CRM_Core_Permission::check('view all contacts') ||
            CRM_Contact_BAO_Contact_Permission::allow($this->_id)
          )
        ) ||
        $this->_id == $userID ||
        $this->_isPermissionedChecksum
      ) {
        $admin = TRUE;
      }

      $values = array();
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
          CRM_Core_Error::fatal(array_pop($errors));
        }
      }

      //reformat fields array
      foreach ($fields as $name => $field) {
        // also eliminate all formatting fields
        if (CRM_Utils_Array::value('field_type', $field) == 'Formatting') {
          unset($fields[$name]);
        }

        // make sure that there is enough permission to expose this field
        if (!$admin && $field['visibility'] == 'User and User Admin Only') {
          unset($fields[$name]);
        }
      }

      if ($this->_isContactActivityProfile) {
        $contactFields = $activityFields = array();

        foreach ($fields as $fieldName => $field) {
          if (CRM_Utils_Array::value('field_type', $field) == 'Activity') {
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
            array(array('activity_id', '=', $this->_activityId, 0, 0))
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
          } else {
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
      $profileFields = array();
      $labels = array();

      foreach ($fields as $name => $field) {
        $labels[$field['title']] = preg_replace('/\s+|\W+/', '_', $name);
      }

      foreach ($values as $title => $value) {
        $profileFields[$labels[$title]] = array(
          'label' => $title,
          'value' => $value,
        );
      }

      $template->assign_by_ref('row', $values);
      $template->assign_by_ref('profileFields', $profileFields);
    }

    $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');

    if (strtolower($name) == 'summary_overlay') {
      $template->assign('overlayProfile', TRUE);
    }

    if (($this->_multiRecord & CRM_Core_Action::VIEW) && $this->_recordId && !$this->_allFields) {
      $fieldDetail = reset($fields);
      $fieldId = CRM_Core_BAO_CustomField::getKeyID($fieldDetail['name']);
      $customGroupDetails = CRM_Core_BAO_CustomGroup::getGroupTitles(array($fieldId));
      $title = $customGroupDetails[$fieldId]['groupTitle'];
    } else {
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

    CRM_Utils_System::setTitle($title);

    // invoke the pagRun hook, CRM-3906
    CRM_Utils_Hook::pageRun($this);

    return trim($template->fetch($this->getTemplateFileName()));
  }

  function checkTemplateFileExists($suffix = '') {
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

  function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }
}

