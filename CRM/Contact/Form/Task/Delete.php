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

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * deletion.
 */
class CRM_Contact_Form_Task_Delete extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * cache shared address message so we don't query twice
   */
  protected $_sharedAddressMessage = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );

    $this->_searchKey = CRM_Utils_Request::retrieve('key', 'String', $this);

    // sort out whether it’s a delete-to-trash, delete-into-oblivion or restore (and let the template know)
    $values              = $this->controller->exportValues();
    $this->_skipUndelete = (CRM_Core_Permission::check('access deleted contacts') and (CRM_Utils_Request::retrieve('skip_undelete', 'Boolean', $this) or CRM_Utils_Array::value('task', $values) == CRM_Contact_Task::DELETE_PERMANENTLY));
    $this->_restore      = (CRM_Utils_Request::retrieve('restore', 'Boolean', $this) or CRM_Utils_Array::value('task', $values) == CRM_Contact_Task::RESTORE);

    if ($this->_restore && !CRM_Core_Permission::check('access deleted contacts')) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this contact.'));
    }
    elseif (!CRM_Core_Permission::check('delete contacts')) {
      CRM_Core_Error::fatal(ts('You do not have permission to delete this contact.'));
    }

    $this->assign('trash', CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'contact_undelete', NULL) and !$this->_skipUndelete);
    $this->assign('restore', $this->_restore);

    if ($this->_restore) {
      CRM_Utils_System::setTitle(ts('Restore Contact'));
    }

    if ($cid) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::fatal(ts('You do not have permission to delete this contact. Note: you can delete contacts if you can edit them.'));
      } elseif (CRM_Contact_BAO_Contact::checkDomainContact($cid)) {
        CRM_Core_Error::fatal(ts('This contact is a special one for the contact information associated with the CiviCRM installation for this domain. No one is allowed to delete it because the information is used for special system purposes.'));
      }

      $this->_contactIds = array($cid);
      $this->_single = TRUE;
      $this->assign('totalSelectedContacts', 1);
    }
    else {
      parent::preProcess();
    }

    $this->_sharedAddressMessage = $this->get('sharedAddressMessage');
    if (!$this->_restore && !$this->_sharedAddressMessage) {
      // we check for each contact for shared contact address
      $sharedContactList = array();
      $sharedAddressCount = 0;
      foreach ($this->_contactIds as $contactId) {
        // check if a contact that is being deleted has any shared addresses
        $sharedAddressMessage = CRM_Core_BAO_Address::setSharedAddressDeleteStatus(NULL, $contactId, TRUE);

        if ($sharedAddressMessage['count'] > 0) {
          $sharedAddressCount += $sharedAddressMessage['count'];
          $sharedContactList = array_merge($sharedContactList,
            $sharedAddressMessage['contactList']
          );
        }
      }

      $this->_sharedAddressMessage = array(
        'count' => $sharedAddressCount,
        'contactList' => $sharedContactList,
      );

      if ($sharedAddressCount > 0) {
        if (count($this->_contactIds) > 1) {
          // more than one contact deleted
          $message = ts('One of the selected contacts has an address record that is shared with 1 other contact.', array('plural' => 'One or more selected contacts have address records which are shared with %count other contacts.', 'count' => $sharedAddressCount));
        }
        else {
          // only one contact deleted
          $message = ts('This contact has an address record which is shared with 1 other contact.', array('plural' => 'This contact has an address record which is shared with %count other contacts.', 'count' => $sharedAddressCount));
        }
        CRM_Core_Session::setStatus($message . ' ' . ts('Shared addresses will not be removed or altered but will no longer be shared.'), ts('Shared Addesses Owner'));
      }

      // set in form controller so that queries are not fired again
      $this->set('sharedAddressMessage', $this->_sharedAddressMessage);
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $label = $this->_restore ? ts('Restore Contact(s)') : ts('Delete Contact(s)');

    if ($this->_single) {
      // also fix the user context stack in case the user hits cancel
      $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'basic');
      if ($context == 'search' && CRM_Utils_Rule::qfKey($this->_searchKey)) {
        $urlParams = "&context=$context&key=$this->_searchKey";
      }
      else {
        $urlParams = '';
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $this->_contactIds[0] . $urlParams
        ));
      $this->addDefaultButtons($label, 'done', 'cancel');
    }
    else {
      $this->addDefaultButtons($label, 'done');
    }

    $this->addFormRule(array('CRM_Contact_Form_Task_Delete', 'formRule'), $this);
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param object $self form object
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    // CRM-12929
    $error = array();
    if ($self->_skipUndelete) {
      CRM_Financial_BAO_FinancialItem::checkContactPresent($self->_contactIds, $error);
    }
    return $error;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $currentUserId = $session->get('userID');

    $context   = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'basic');
    $urlParams = 'force=1';
    $urlString = "civicrm/contact/search/$context";

    if (CRM_Utils_Rule::qfKey($this->_searchKey)) {
      $urlParams .= "&qfKey=$this->_searchKey";
    }
    elseif ($context == 'search') {
      $urlParams .= "&qfKey={$this->controller->_key}";
      $urlString = 'civicrm/contact/search';
    }
    elseif ($context == 'smog') {
      $urlParams .= "&qfKey={$this->controller->_key}&context=smog";
      $urlString = 'civicrm/group/search';
    }
    else {
      $urlParams = "reset=1";
      $urlString = 'civicrm/dashboard';
    }

    // Delete/Restore Contacts. Report errors.
    $deleted = 0;
    $not_deleted = array();
    foreach ($this->_contactIds as $cid) {
      $name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name');
      if (CRM_Contact_BAO_Contact::checkDomainContact($cid)) {
        $session->setStatus(ts("'%1' cannot be deleted because the information is used for special system purposes.", array(1 => $name)), 'Cannot Delete Domain Contact', 'error');
        continue;
      }
      if ($currentUserId == $cid && !$this->_restore) {
        $session->setStatus(ts("You are currently logged in as '%1'. You cannot delete yourself.", array(1 => $name)), 'Unable To Delete', 'error');
        continue;
      }
      if (CRM_Contact_BAO_Contact::deleteContact($cid, $this->_restore, $this->_skipUndelete)) {
        $deleted++;
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$cid");
        $not_deleted[$cid] = "<a href='$url'>$name</a>";
      }
    }
    if ($deleted) {
      $title = ts('Deleted');
      if ($this->_restore) {
        $title = ts('Restored');
        $status = ts('%1 has been restored from the trash.', array(1 => $name, 'plural' => '%count contacts restored from trash.', 'count' => $deleted));
      }
      elseif ($this->_skipUndelete) {
        $status = ts('%1 has been permanently deleted.', array(1 => $name, 'plural' => '%count contacts permanently deleted.', 'count' => $deleted));
      }
      else {
        $status = ts('%1 has been moved to the trash.', array(1 => $name, 'plural' => '%count contacts moved to trash.', 'count' => $deleted));
      }
      $session->setStatus($status, $title, 'success');
    }
    // Alert user of any failures
    if ($not_deleted) {
      $status = ts('The contact might be the Membership Organization of a Membership Type. You will need to edit the Membership Type and change the Membership Organization before you can delete this contact.');
      $title = ts('Unable to Delete');
      $session->setStatus('<ul><li>' . implode('</li><li>', $not_deleted) . '</li></ul>' . $status, $title, 'error');
    }

    if (isset($this->_sharedAddressMessage) && $this->_sharedAddressMessage['count'] > 0 && !$this->_restore) {
      if (count($this->_sharedAddressMessage['contactList']) == 1) {
        $message = ts('The following contact had been sharing an address with a contact you just deleted. Their address will no longer be shared, but has not been removed or altered.');
      }
      else {
        $message = ts('The following contacts had been sharing addresses with a contact you just deleted. Their addressses will no longer be shared, but have not been removed or altered.');
      }
      $message .= '<ul><li>' . implode('</li><li>', $this->_sharedAddressMessage['contactList']) . '</li></ul>';

      $session->setStatus($message, ts('Shared Addesses Owner Deleted'), 'info', array('expires' => 0));

      $this->set('sharedAddressMessage', NULL);
    }

    if ($this->_single && empty($this->_skipUndelete)) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_contactIds[0]}"));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url($urlString, $urlParams));
    }
  }
  //end of function
}

