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
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contact_Form_Task_Email extends CRM_Contact_Form_Task {

  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // @todo - more of the handling in this function should be move to the trait. Notably the title part is
    //  not set on other forms that share the trait.
    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'String', $this, FALSE);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $cid = CRM_Utils_Request::retrieve('cid', 'String', $this, FALSE);

    // Allow request to specify email id rather than contact id
    $toEmailId = CRM_Utils_Request::retrieve('email_id', 'String', $this);
    if ($toEmailId) {
      $toEmail = civicrm_api('email', 'getsingle', ['version' => 3, 'id' => $toEmailId]);
      if (!empty($toEmail['email']) && !empty($toEmail['contact_id'])) {
        $this->_toEmail = $toEmail;
      }
      if (!$cid) {
        $cid = $toEmail['contact_id'];
        $this->set('cid', $cid);
      }
    }

    if ($cid) {
      $cid = explode(',', $cid);
      $displayName = [];

      foreach ($cid as $val) {
        $displayName[] = CRM_Contact_BAO_Contact::displayName($val);
      }

      $this->setTitle(implode(',', $displayName) . ' - ' . ts('Email'));
    }
    else {
      $this->setTitle(ts('New Email'));
    }
    if ($this->_context === 'search') {
      $this->_single = TRUE;
    }
    if ($cid || $this->_context === 'standalone') {
      // When search context is false the parent pre-process is not set. That avoids it changing the
      // redirect url & attempting to set the search params of the form. It may have only
      // historical significance.
      $this->setIsSearchContext(FALSE);
    }
    $this->traitPreProcess();
  }

  /**
   * Stub function  as EmailTrait calls this.
   *
   * @todo move some code from preProcess into here.
   */
  public function setContactIDs() {}

}
