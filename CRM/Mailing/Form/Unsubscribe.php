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
class CRM_Mailing_Form_Unsubscribe extends CRM_Core_Form {

  function preProcess() {

    $this->_type = 'unsubscribe';

    $this->_job_id = $job_id = CRM_Utils_Request::retrieve('jid', 'Integer', $this);
    $this->_queue_id = $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer', $this);
    $this->_hash = $hash = CRM_Utils_Request::retrieve('h', 'String', $this);

    if (!$job_id ||
      !$queue_id ||
      !$hash
    ) {
      CRM_Core_Error::fatal(ts("Missing Parameters"));
    }

    // verify that the three numbers above match
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    if (!$q) {
      CRM_Core_Error::fatal(ts("There was an error in your request"));
    }

    list($displayName, $email) = CRM_Mailing_Event_BAO_Queue::getContactInfo($queue_id);
    $this->assign('display_name', $displayName);
    $emailMasked = CRM_Utils_String::maskEmail($email);
    $this->assign('email_masked', $emailMasked);
    $this->assign('email', $email);
    $this->_email = $email;

    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash, TRUE);
    $this->assign('groups', $groups);
    $groupExist = NULL;
    foreach ($groups as $key => $value) {
      if ($value) {
        $groupExist = TRUE;
      }
    }
    if (!$groupExist) {
      $statusMsg = ts('Email: %1 has been successfully unsubscribed from this Mailing List/Group.',
        array(1 => $email)
      );
      CRM_Core_Session::setStatus( $statusMsg, '', 'fail' );
    }
    $this->assign('groupExist', $groupExist);

  }

  function buildQuickForm() {
    CRM_Utils_System::addHTMLHead('<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">');
    CRM_Utils_System::setTitle(ts('Please Confirm Your Unsubscribe from this Mailing/Group'));

    $this->add('text', 'email_confirm', ts('Verify email address to unsubscribe:'));
    $this->addRule('email_confirm', ts('Email address is required to unsubscribe.'), 'required');

    $buttons = array(
      array(
        'type' => 'next',
        'name' => 'Unsubscribe',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);
  }

  function postProcess() {

    $values = $this->exportValues();

    // check if EmailTyped matches Email address
    $result = CRM_Utils_String::compareStr($this->_email, $values['email_confirm'], TRUE);


    $job_id = $this->_job_id;
    $queue_id = $this->_queue_id;
    $hash = $this->_hash;

    $confirmURL = CRM_Utils_System::url("civicrm/mailing/{$this->_type}","reset=1&jid={$job_id}&qid={$queue_id}&h={$hash}&confirm=1");
    $this->assign('confirmURL', $confirmURL);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($confirmURL);

    if ($result == TRUE) {
      // Email address verified

      $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash);
      if (count($groups)) {
        CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue_id, $groups, FALSE, $job_id);
      }

      $statusMsg = ts('Email: %1 has been successfully unsubscribed from this Mailing List/Group.',
        array(1 => $values['email_confirm'])
      );

      CRM_Core_Session::setStatus( $statusMsg, '', 'success' );
    }
    else if ($result == FALSE) {
      // Email address not verified

      $statusMsg = ts('The email address: %1 you have entered does not match the email associated with this unsubscribe request.',
        array(1 => $values['email_confirm'])
      );

    CRM_Core_Session::setStatus( $statusMsg, '', 'fail' );

    }

  }
}


