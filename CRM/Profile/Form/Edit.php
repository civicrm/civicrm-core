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
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Profile_Form_Edit extends CRM_Profile_Form {
  protected $_postURL = NULL;
  protected $_errorURL = NULL;
  protected $_context;
  protected $_blockNo;
  protected $_prefix;
  protected $returnExtra;

  /**
   * Pre processing work done here.
   *
   */
  public function preProcess() {
    $this->_mode = CRM_Profile_Form::MODE_CREATE;

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
      CRM_Core_Error::deprecatedWarning('code believed to be unreachable');
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
        CRM_Core_Error::statusBounce(ts('No user/contact ID was specified, so the Profile cannot be used in edit mode.'));
      }

    }

    parent::preProcess();

    // and also the profile is of type 'Profile'
    $query = '
SELECT module,is_reserved
  FROM civicrm_uf_group
  LEFT JOIN civicrm_uf_join ON uf_group_id = civicrm_uf_group.id
  WHERE civicrm_uf_group.id = %1
';

    $params = [1 => [$this->_gid, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $isProfile = FALSE;
    while ($dao->fetch()) {
      $isProfile = ($isProfile || ($dao->module === 'Profile'));
    }

    //Check that the user has the "add contacts" Permission
    $canAdd = CRM_Core_Permission::check('add contacts');

    //Remove need for Profile module type when using reserved profiles [CRM-14488]
    if (!$dao->N || (!$isProfile && !($dao->is_reserved && $canAdd))) {
      CRM_Core_Error::statusBounce(ts("The requested Profile (gid=%1) is not configured to be used as a standalone form. Contact the site administrator if you need assistance.",
        [1 => $this->_gid]
      ));
    }
  }

  /**
   * Build the form object.
   *
   */
  public function buildQuickForm(): void {
    if (empty($this->_ufGroup['id'])) {
      CRM_Core_Error::statusBounce(ts('Invalid'));
    }

    // set the title
    if ($this->_multiRecord && $this->_customGroupTitle) {
      $this->setTitle(($this->_multiRecord & CRM_Core_Action::UPDATE) ? 'Edit ' . $this->_customGroupTitle . ' Record' : $this->_customGroupTitle);

    }
    else {
      $this->setTitle(CRM_Core_BAO_UFGroup::getFrontEndTitle($this->_ufGroup['id']));
    }

    $this->assign('recentlyViewed', FALSE);

    $cancelURL = '';
    if ($this->_context !== 'dialog') {
      $this->_postURL = $this->_ufGroup['post_url'];
      $cancelURL = $this->_ufGroup['cancel_url'];

      $gidString = $this->_gid;
      if (!empty($this->_profileIds)) {
        $gidString = implode(',', $this->_profileIds);
      }

      if (!$this->_postURL) {
        if ($this->_context === 'Search') {
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

      if (!$cancelURL) {
        $cancelURL = CRM_Utils_System::url('civicrm/profile',
          "reset=1&gid={$gidString}"
        );
      }

      // we do this gross hack since qf also does entity replacement
      $this->_postURL = str_replace('&amp;', '&', ($this->_postURL ?? ''));
      $cancelURL = str_replace('&amp;', '&', ($cancelURL ?? ''));

      // also retain error URL if set
      $this->_errorURL = $_POST['errorURL'] ?? NULL;
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

    $this->assign('cancelURL', $cancelURL);

    $cancelButtonValue = !empty($this->_ufGroup['cancel_button_text']) ? $this->_ufGroup['cancel_button_text'] : ts('Cancel');
    $this->assign('cancelButtonText', $cancelButtonValue);
    $this->assign('includeCancelButton', $this->_ufGroup['add_cancel_button'] ?? FALSE);

    if (($this->_multiRecord & CRM_Core_Action::DELETE) && $this->_recordExists) {
      $this->_deleteButtonName = $this->getButtonName('upload', 'delete');
      $this->addElement('xbutton', $this->_deleteButtonName, ts('Delete'), [
        'type' => 'submit',
        'value' => 1,
        'class' => 'crm-button',
      ]);

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

    $buttons[] = [
      'type' => $buttonName,
      'name' => !empty($this->_ufGroup['submit_button_text']) ? $this->_ufGroup['submit_button_text'] : ts('Save'),
      'isDefault' => TRUE,
    ];

    $this->addButtons($buttons);

    $this->addFormRule(['CRM_Profile_Form', 'formRule'], $this);
  }

  /**
   * Process the user submitted custom data values.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    parent::postProcess();

    // Send back data for the EntityRef widget
    if ($this->returnExtra) {
      $contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $this->_id,
        'return' => $this->returnExtra,
      ]);
      foreach (explode(',', $this->returnExtra) as $field) {
        $field = trim($field);
        $this->ajaxResponse['extra'][$field] = $contact[$field] ?? NULL;
      }
    }

    // When saving (not deleting) and not in an ajax popup
    if (empty($_POST[$this->_deleteButtonName]) && $this->_context !== 'dialog') {
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
      $url = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => ['msg_text' => $this->_postURL],
        'contactId' => $this->_id,
        'disableSmarty' => TRUE,
      ])['text'];
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
      if (str_contains($errorURL, '?')) {
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
