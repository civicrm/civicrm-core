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
class CRM_Mailing_Page_Common extends CRM_Core_Page {
  protected $_type = NULL;

  /**
   * @return string
   * @throws Exception
   */
  function run() {
    $job_id   = CRM_Utils_Request::retrieve('jid', 'Integer', CRM_Core_DAO::$_nullObject);
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer', CRM_Core_DAO::$_nullObject);
    $hash     = CRM_Utils_Request::retrieve('h', 'String', CRM_Core_DAO::$_nullObject);

    if (!$job_id ||
      !$queue_id ||
      !$hash
    ) {
      CRM_Core_Error::fatal(ts("Missing input parameters"));
    }


    // verify that the three numbers above match
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    if (!$q) {
      CRM_Core_Error::fatal(ts("There was an error in your request"));
    }

    $cancel = CRM_Utils_Request::retrieve("_qf_{$this->_type}_cancel", 'String', CRM_Core_DAO::$_nullObject,
      FALSE, NULL, $_REQUEST
    );
    if ($cancel) {
      $config = CRM_Core_Config::singleton();
      CRM_Utils_System::redirect($config->userFrameworkBaseURL);
    }

    $confirm = CRM_Utils_Request::retrieve('confirm', 'Boolean', CRM_Core_DAO::$_nullObject,
      FALSE, NULL, $_REQUEST
    );

    list($displayName, $email) = CRM_Mailing_Event_BAO_Queue::getContactInfo($queue_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);
    $this->assign('confirm', $confirm);

    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash, TRUE);
    $this->assign('groups', $groups);
    $groupExist = NULL;
    foreach ($groups as $key => $value) {
      if ($value) {
        $groupExist = TRUE;
      }
    }
    $this->assign('groupExist', $groupExist);

    if ($confirm) {
      if ($this->_type == 'unsubscribe') {
        $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash);
        if (count($groups)) {
          CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue_id, $groups, FALSE, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
      elseif ($this->_type == 'resubscribe') {
        $groups = CRM_Mailing_Event_BAO_Resubscribe::resub_to_mailing($job_id, $queue_id, $hash);
        if (count($groups)) {
          CRM_Mailing_Event_BAO_Resubscribe::send_resub_response($queue_id, $groups, FALSE, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
      else {
        if (CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_domain($job_id, $queue_id, $hash)) {
          CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue_id, NULL, TRUE, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
    }
    else {
      $confirmURL = CRM_Utils_System::url("civicrm/mailing/{$this->_type}",
        "reset=1&jid={$job_id}&qid={$queue_id}&h={$hash}&confirm=1"
      );
      $this->assign('confirmURL', $confirmURL);
      //push context for further process CRM-4431
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($confirmURL);
    }

    return parent::run();
  }
}

