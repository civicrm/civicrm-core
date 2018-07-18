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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 */

/**
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Profile_Form_Edit extends CRM_Profile_Form {
  protected $_postURL = NULL;
  protected $_cancelURL = NULL;
  protected $_errorURL = NULL;
  protected $_context;
  protected $_blockNo;
  protected $_prefix;
  protected $returnExtra;

  /**
   * Pre processing work done here.
   *
   * @param
   *
   */
  public function preProcess() {
    $this->_mode = CRM_Profile_Form::MODE_CREATE;

    $this->_onPopupClose = CRM_Utils_Request::retrieve('onPopupClose', 'String', $this);
    $this->assign('onPopupClose', $this->_onPopupClose);

    //set the context for the profile
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    //set the block no
    $this->_blockNo = CRM_Utils_Request::retrieve('blockNo', 'String', $this);

    //set the prefix
    $this->_prefix = CRM_Utils_Request::retrieve('prefix', 'String', $this);

    // Fields for the EntityRef widget
    $this->returnExtra = CRM_Utils_Request::retrieve('returnExtra', 'String', $this);

    $this->assign('context', $this->_context);

    if ($this->_blockNo) {
      $this->assign('blockNo', $this->_blockNo);
      $this->assign('prefix', $this->_prefix);
    }

    $this->assign('createCallback', CRM_Utils_Request::retrieve('createCallback', 'String', $this));

    if ($this->get('skipPermission')) {
      $this->_skipPermission = TRUE;
    }

    if ($this->get('edit')) {
      // make sure we have right permission to edit this user
      $userID = CRM_Core_Session::getLoggedInContactID();

      // Set the ID from the query string, otherwise default to the current user
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, $userID);

      if ($id) {
        // this is edit mode.
        $this->_mode = CRM_Profile_Form::MODE_EDIT;

        if ($id != $userID) {
          // do not allow edit for anon users in joomla frontend, CRM-4668, unless u have checksum CRM-5228
          // see also CRM-19079 for modifications to the condition
          $config = CRM_Core_Config::singleton();
          if ($config->userFrameworkFrontend && $config->userSystem->is_joomla) {
            CRM_Contact_BAO_Contact_Permission::validateOnlyChecksum($id, $this);
          }
          else {
            CRM_Contact_BAO_Contact_Permission::validateChecksumContact($id, $this);
          }
          $this->_isPermissionedChecksum = TRUE;
        }
      }

      // CRM-16784: If there is no ID then this can't be an 'edit'
      else {
        CRM_Core_Error::fatal(ts('No user/contact ID was specified, so the Profile cannot be used in edit mode.'));
      }

    }

    parent::preProcess();

    // and also the profile is of type 'Profile'
    $query = "
SELECT module,is_reserved
  FROM civicrm_uf_group
  LEFT JOIN civicrm_uf_join ON uf_group_id = civicrm_uf_group.id
  WHERE civicrm_uf_group.id = %1
";

    $params = array(1 => array($this->_gid, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $isProfile = FALSE;
    while ($dao->fetch()) {
      $isProfile = ($isProfile || ($dao->module == "Profile"));
    }

    //Check that the user has the "add contacts" Permission
    $canAdd = CRM_Core_Permission::check("add contacts");

    //Remove need for Profile module type when using reserved profiles [CRM-14488]
    if (!$dao->N || (!$isProfile && !($dao->is_reserved && $canAdd))) {
      CRM_Core_Error::fatal(ts('The requested Profile (gid=%1) is not configured to be used for \'Profile\' edit and view forms in its Settings. Contact the site administrator if you need assistance.',
        array(1 => $this->_gid)
      ));
    }
  }

  /**
   * Build the form object.
   *
   */
  public function buildQuickForm() {
    if (empty($this->_ufGroup['id'])) {
      CRM_Core_Error::fatal();
    }

    // set the title
    if ($this->_multiRecord && $this->_customGroupTitle) {
      $groupTitle = ($this->_multiRecord & CRM_Core_Action::UPDATE) ? 'Edit ' . $this->_customGroupTitle . ' Record' : $this->_customGroupTitle;

    }
    else {
      $groupTitle = $this->_ufGroup['title'];
    }
    CRM_Utils_System::setTitle($groupTitle);
    $this->assign('recentlyViewed', FALSE);

    if ($this->_context != 'dialog') {
      $this->_postURL = $this->_ufGroup['post_URL'];
      $this->_cancelURL = $this->_ufGroup['cancel_URL'];

      $gidString = $this->_gid;
      if (!empty($this->_profileIds)) {
        $gidString = implode(',', $this->_profileIds);
      }

      if (!$this->_postURL) {
        if ($this->_context == 'Search') {
          $this->_postURL = CRM_Utils_System::url('civicrm/contact/search');
        }
        elseif ($this->_id && $this->_gid) {
          $urlParams = "reset=1&id={$this->_id}&gid={$gidString}";
          if ($this->_isContactActivityProfile && $this->_activityId) {
            $urlParams .= "&aid={$this->_activityId}";
          }
          // get checksum if present
          if ($this->get('cs')) {
            $urlParams .= "&cs=" . $this->get('cs');
          }
          $this->_postURL = CRM_Utils_System::url('civicrm/profile/view', $urlParams);
        }
      }

      if (!$this->_cancelURL) {
        $this->_cancelURL = CRM_Utils_System::url('civicrm/profile',
          "reset=1&gid={$gidString}"
        );
      }

      // we do this gross hack since qf also does entity replacement
      $this->_postURL = str_replace('&amp;', '&', $this->_postURL);
      $this->_cancelURL = str_replace('&amp;', '&', $this->_cancelURL);

      // also retain error URL if set
      $this->_errorURL = CRM_Utils_Array::value('errorURL', $_POST);
      if ($this->_errorURL) {
        // we do this gross hack since qf also does entity replacement
        $this->_errorURL = str_replace('&amp;', '&', $this->_errorURL);
        $this->addElement('hidden', 'errorURL', $this->_errorURL);
      }

      // replace the session stack in case user cancels (and we dont go into postProcess)
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($this->_postURL);
    }

    parent::buildQuickForm();

    $this->assign('cancelURL', $this->_cancelURL);

    $cancelButtonValue = !empty($this->_ufGroup['cancel_button_text']) ? $this->_ufGroup['cancel_button_text'] : ts('Cancel');
    $this->assign('cancelButtonText', $cancelButtonValue);
    $this->assign('includeCancelButton', CRM_Utils_Array::value('add_cancel_button', $this->_ufGroup));

    if (($this->_multiRecord & CRM_Core_Action::DELETE) && $this->_recordExists) {
      $this->_deleteButtonName = $this->getButtonName('upload', 'delete');
      $this->addElement('submit', $this->_deleteButtonName, ts('Delete'));

      return;
    }

    //get the value from session, this is set if there is any file
    //upload field
    $uploadNames = $this->get('uploadNames');

    if (!empty($uploadNames)) {
      $buttonName = 'upload';
    }
    else {
      $buttonName = 'next';
    }

    $buttons[] = array(
      'type' => $buttonName,
      'name' => !empty($this->_ufGroup['submit_button_text']) ? $this->_ufGroup['submit_button_text'] : ts('Save'),
      'isDefault' => TRUE,
    );

    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Profile_Form', 'formRule'), $this);
  }

  /**
   * Process the user submitted custom data values.
   *
   */
  public function postProcess() {
    parent::postProcess();

    // Send back data for the EntityRef widget
    if ($this->returnExtra) {
      $contact = civicrm_api3('Contact', 'getsingle', array(
        'id' => $this->_id,
        'return' => $this->returnExtra,
      ));
      foreach (explode(',', $this->returnExtra) as $field) {
        $field = trim($field);
        $this->ajaxResponse['extra'][$field] = CRM_Utils_Array::value($field, $contact);
      }
    }

    // When saving (not deleting) and not in an ajax popup
    if (empty($_POST[$this->_deleteButtonName]) && $this->_context != 'dialog') {
      CRM_Core_Session::setStatus(ts('Your information has been saved.'), ts('Thank you.'), 'success');
    }

    $session = CRM_Core_Session::singleton();
    // only replace user context if we do not have a postURL
    if (!$this->_postURL) {
      $gidString = $this->_gid;
      if (!empty($this->_profileIds)) {
        $gidString = implode(',', $this->_profileIds);
      }

      $urlParams = "reset=1&id={$this->_id}&gid={$gidString}";
      if ($this->_isContactActivityProfile && $this->_activityId) {
        $urlParams .= "&aid={$this->_activityId}";
      }
      // Get checksum if present
      if ($this->get('cs')) {
        $urlParams .= "&cs=" . $this->get('cs');
      }
      // Generate one if needed
      elseif (!CRM_Contact_BAO_Contact_Permission::allow($this->_id)) {
        $urlParams .= "&cs=" . CRM_Contact_BAO_Contact_Utils::generateChecksum($this->_id);
      }
      $url = CRM_Utils_System::url('civicrm/profile/view', $urlParams);
    }
    else {
      // Replace tokens from post URL
      $contactParams = array(
        'contact_id' => $this->_id,
        'version' => 3,
      );

      $contact = civicrm_api('contact', 'get', $contactParams);
      $contact = reset($contact['values']);

      $dummyMail = new CRM_Mailing_BAO_Mailing();
      $dummyMail->body_text = $this->_postURL;
      $tokens = $dummyMail->getTokens();

      $url = CRM_Utils_Token::replaceContactTokens($this->_postURL, $contact, FALSE, CRM_Utils_Array::value('text', $tokens));
    }

    $session->replaceUserContext($url);
  }

  /**
   * Intercept QF validation and do our own redirection.
   *
   * We use this to send control back to the user for a user formatted page
   * This allows the user to maintain the same state and display the error messages
   * in their own theme along with any modifications
   *
   * This is a first version and will be tweaked over a period of time
   *
   *
   * @return bool
   *   true if no error found
   */
  public function validate() {
    $errors = parent::validate();

    if (!$errors && !empty($_POST['errorURL'])) {
      $message = NULL;
      foreach ($this->_errors as $name => $mess) {
        $message .= $mess;
        $message .= '<p>';
      }

      CRM_Utils_System::setUFMessage($message);

      $message = urlencode($message);

      $errorURL = $_POST['errorURL'];
      if (strpos($errorURL, '?') !== FALSE) {
        $errorURL .= '&';
      }
      else {
        $errorURL .= '?';
      }
      $errorURL .= "gid={$this->_gid}&msg=$message";
      CRM_Utils_System::redirect($errorURL);
    }

    return $errors;
  }

}
