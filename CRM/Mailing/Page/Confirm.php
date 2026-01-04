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
class CRM_Mailing_Page_Confirm extends CRM_Core_Page {

  /**
   * @return string
   * @throws Exception
   */
  public function run() {
    CRM_Utils_System::setNoRobotsFlag();

    $contact_id = CRM_Utils_Request::retrieve('cid', 'Integer');
    $subscribe_id = CRM_Utils_Request::retrieve('sid', 'Integer');
    $hash = CRM_Utils_Request::retrieve('h', 'String');

    if (!$contact_id ||
      !$subscribe_id ||
      !$hash
    ) {
      throw new CRM_Core_Exception(ts("Missing input parameters"));
    }

    $result = CRM_Mailing_Event_BAO_MailingEventConfirm::confirm($contact_id, $subscribe_id, $hash);
    if ($result === FALSE) {
      $this->assign('success', $result);
    }
    else {
      $this->assign('success', TRUE);
      $this->assign('group', $result);
    }

    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contact_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);

    return parent::run();
  }

}
