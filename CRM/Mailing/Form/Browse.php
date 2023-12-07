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
 * Build the form object for disable mail feature
 */
class CRM_Mailing_Form_Browse extends CRM_Core_Form {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    $this->_mailingId = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    // check for action permissions.
    if (!CRM_Core_Permission::checkActionPermission('CiviMail', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->_mailingId;
    $subject = '';
    if ($mailing->find(TRUE)) {
      $subject = $mailing->subject;
    }
    $this->assign('subject', $subject);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Confirm'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Mailing_BAO_Mailing::deleteRecord(['id' => $this->_mailingId]);
      CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');
    }
    elseif ($this->_action & CRM_Core_Action::DISABLE) {
      CRM_Mailing_BAO_MailingJob::cancel($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been canceled.'), ts('Canceled'), 'success');
    }
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      //set is_archived to 1
      CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingId, 'is_archived', TRUE);
    }
  }

}
