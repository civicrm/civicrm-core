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
 * This class provides the functionality to delete a group of contacts.
 *
 * This class provides functionality for the actual deletion.
 */
class CRM_Contact_Form_Task_Delete extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Cache shared address message so we don't query twice
   *
   * @var array
   */
  protected $_sharedAddressMessage = NULL;

  /**
   * @var string
   */
  protected $_searchKey;

  /**
   * @var bool
   */
  protected $_skipUndelete;

  /**
   * @var bool
   */
  protected $_restore;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );

    $this->_searchKey = CRM_Utils_Request::retrieve('key', 'String', $this);

    // sort out whether it’s a delete-to-trash, delete-into-oblivion or restore (and let the template know)
    $values = $this->controller->exportValues();
    $this->_skipUndelete = (CRM_Core_Permission::check('access deleted contacts') and (CRM_Utils_Request::retrieve('skip_undelete', 'Boolean', $this) or ($values['task'] ?? NULL) == CRM_Contact_Task::DELETE_PERMANENTLY));
    $this->_restore = (CRM_Utils_Request::retrieve('restore', 'Boolean', $this) or ($values['task'] ?? NULL) == CRM_Contact_Task::RESTORE);

    if ($this->_restore && !CRM_Core_Permission::check('access deleted contacts')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this contact.'));
    }
    elseif (!CRM_Core_Permission::check('delete contacts')) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to delete this contact.'));
    }

    $this->assign('trash', Civi::settings()->get('contact_undelete') and !$this->_skipUndelete);
    $this->assign('restore', $this->_restore);

    if ($this->_restore) {
      $this->setTitle(ts('Restore Contact'));
    }

    if ($cid) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($cid, CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to delete this contact. Note: you can delete contacts if you can edit them.'));
      }
      elseif (CRM_Contact_BAO_Contact::checkDomainContact($cid)) {
        CRM_Core_Error::statusBounce(ts('This contact is a special one for the contact information associated with the CiviCRM installation for this domain. No one is allowed to delete it because the information is used for special system purposes.'));
      }
      // Indicates it is not called from search context so 'view selected contacts' link is suppressed.
      $this->assign('isSelectedContacts', FALSE);
      $this->_contactIds = [$cid];
      $this->_single = TRUE;
      $this->assign('totalSelectedContacts', 1);
    }
    else {
      parent::preProcess();
    }

    $this->_sharedAddressMessage = $this->get('sharedAddressMessage');
    if (!$this->_restore && !$this->_sharedAddressMessage) {
      // we check for each contact for shared contact address
      $sharedContactList = [];
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

      $this->_sharedAddressMessage = [
        'count' => $sharedAddressCount,
        'contactList' => $sharedContactList,
      ];

      if ($sharedAddressCount > 0) {
        if (count($this->_contactIds) > 1) {
          // more than one contact deleted
          $message = ts('One of the selected contacts has an address record that is shared with 1 other contact.', [
            'plural' => 'One or more selected contacts have address records which are shared with %count other contacts.',
            'count' => $sharedAddressCount,
          ]);
        }
        else {
          // only one contact deleted
          $message = ts('This contact has an address record which is shared with 1 other contact.', [
            'plural' => 'This contact has an address record which is shared with %count other contacts.',
            'count' => $sharedAddressCount,
          ]);
        }
        CRM_Core_Session::setStatus($message . ' ' . ts('Shared addresses will not be removed or altered but will no longer be shared.'), ts('Shared Addresses Owner'));
      }

      // set in form controller so that queries are not fired again
      $this->set('sharedAddressMessage', $this->_sharedAddressMessage);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $label = $this->_restore ? ts('Restore Contact(s)') : ts('Delete Contact(s)');

    if ($this->_single) {
      // also fix the user context stack in case the user hits cancel
      $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'basic');
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

    $this->addFormRule(['CRM_Contact_Form_Task_Delete', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *   Form object.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    // CRM-12929
    $error = [];
    if ($self->_skipUndelete) {
      CRM_Financial_BAO_FinancialItem::checkContactPresent($self->_contactIds, $error);
    }
    return $error;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    if ($this->_restore) {
      $this->doRestore();
      return;
    }
    $session = CRM_Core_Session::singleton();
    $currentUserId = $session->get('userID');

    // Delete Contacts. Report errors.
    $deleted = 0;
    $not_deleted = [];
    foreach ($this->_contactIds as $cid) {
      $name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name');
      if (CRM_Contact_BAO_Contact::checkDomainContact($cid)) {
        $session->setStatus(ts("'%1' cannot be deleted because the information is used for special system purposes.", [1 => $name]), 'Cannot Delete Domain Contact', 'error');
        continue;
      }
      if ($currentUserId == $cid) {
        $session->setStatus(ts("You are currently logged in as '%1'. You cannot delete yourself.", [1 => $name]), 'Unable To Delete', 'error');
        continue;
      }
      if (CRM_Contact_BAO_Contact::deleteContact($cid, FALSE, $this->_skipUndelete)) {
        $deleted++;
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$cid");
        $not_deleted[$cid] = "<a href='$url'>$name</a>";
      }
    }
    if ($deleted) {
      $title = ts('Deleted');
      if ($this->_skipUndelete) {
        $status = ts('%1 has been permanently deleted.', [
          1 => $name,
          'plural' => '%count contacts permanently deleted.',
          'count' => $deleted,
        ]);
      }
      else {
        $status = ts('%1 has been moved to the trash.', [
          1 => $name,
          'plural' => '%count contacts moved to trash.',
          'count' => $deleted,
        ]);
      }
      $session->setStatus($status, $title, 'success');
    }
    // Alert user of any failures
    if ($not_deleted) {
      $title = ts('Unable to Delete');
      // If the contact has a CMS account, you can't delete them. The deletion
      // call just returns TRUE or FALSE, so we check if they have a CMS account
      // Note: we're not using CRM_Core_BAO_UFMatch::getUFId() because that's cached.
      $ufmatch = new CRM_Core_DAO_UFMatch();
      $ufmatch->contact_id = $cid;
      $ufmatch->domain_id = CRM_Core_Config::domainID();
      if ($ufmatch->find(TRUE)) {
        $status = ts('The contact has a CMS account. You will need to delete it before you can delete this contact.');
      }
      else {
        $status = ts('The contact might be the Membership Organization of a Membership Type. You will need to edit the Membership Type and change the Membership Organization before you can delete this contact.');
      }
      $session->setStatus('<ul><li>' . implode('</li><li>', $not_deleted) . '</li></ul>' . $status, $title, 'error');
    }
    if (isset($this->_sharedAddressMessage) && $this->_sharedAddressMessage['count'] > 0) {
      if (count($this->_sharedAddressMessage['contactList']) == 1) {
        $message = ts('The following contact had been sharing an address with a contact you just deleted. Their address will no longer be shared, but has not been removed or altered.');
      }
      else {
        $message = ts('The following contacts had been sharing addresses with a contact you just deleted. Their addressses will no longer be shared, but have not been removed or altered.');
      }
      $message .= '<ul><li>' . implode('</li><li>', $this->_sharedAddressMessage['contactList']) . '</li></ul>';

      $session->setStatus($message, ts('Shared Addresses Owner Deleted'), 'info', ['expires' => 30000]);

      $this->set('sharedAddressMessage', NULL);
    }

    $this->setRedirection();
  }

  /**
   * Set the url for the contact to be redirected to.
   */
  protected function setRedirection() {

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'basic');
    $urlParams = 'force=1';
    $urlString = "civicrm/contact/search/$context";

    if (CRM_Utils_Rule::qfKey($this->_searchKey)) {
      $urlParams .= "&qfKey=$this->_searchKey";
    }
    elseif ($context === 'search') {
      $urlParams .= "&qfKey={$this->controller->_key}";
      $urlString = 'civicrm/contact/search';
    }
    elseif ($context === 'smog') {
      $urlParams .= "&qfKey={$this->controller->_key}&context=smog";
      $urlString = 'civicrm/group/search';
    }
    else {
      $urlParams = 'reset=1';
      $urlString = 'civicrm/dashboard';
    }
    if ($this->_single && empty($this->_skipUndelete)) {
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->_contactIds[0]}"));
    }
    else {
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url($urlString, $urlParams));
    }
  }

  /**
   * Restore the selected contact/s from the trash.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function doRestore() {
    $name = '';
    if (count($this->_contactIds) === 1) {
      $name = Civi\Api4\Contact::get()->addWhere('id', 'IN', $this->_contactIds)->setSelect(['display_name'])->execute()->first()['display_name'];
    }
    Civi\Api4\Contact::update()->addWhere('id', 'IN', $this->_contactIds)->setValues(['is_deleted' => 0])->execute();
    $title = ts('Restored');
    $status = ts('%1 has been restored from the trash.', [
      1 => $name,
      'plural' => '%count contacts restored from trash.',
      'count' => count($this->_contactIds),
    ]);
    CRM_Core_Session::setStatus($status, $title, 'success');
    $this->setRedirection();
  }

}
