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
class CRM_Campaign_Page_Petition_Confirm extends CRM_Core_Page {

  /**
   * @return string
   * @throws Exception
   */
  public function run() {
    CRM_Utils_System::setNoRobotsFlag();

    $contact_id = CRM_Utils_Request::retrieve('cid', 'Integer');
    $subscribe_id = CRM_Utils_Request::retrieve('sid', 'Integer');
    $hash = CRM_Utils_Request::retrieve('h', 'String');
    $activity_id = CRM_Utils_Request::retrieve('a', 'String');
    $petition_id = CRM_Utils_Request::retrieve('pid', 'String');
    if (!$petition_id) {
      $petition_id = CRM_Utils_Request::retrieve('p', 'String');
    }

    if (!$contact_id ||
      !$subscribe_id ||
      !$hash
    ) {
      CRM_Core_Error::statusBounce(ts("Missing input parameters"));
    }

    $result = $this->confirm($contact_id, $subscribe_id, $hash, $activity_id, $petition_id);
    if ($result === FALSE) {
      $this->assign('success', $result);
    }
    else {
      $this->assign('success', TRUE);
      // $this->assign( 'group'  , $result );
    }

    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contact_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);
    $this->assign('petition_id', $petition_id);

    $this->assign('survey_id', $petition_id);

    $pparams['id'] = $petition_id;
    $this->petition = [];
    CRM_Campaign_BAO_Survey::retrieve($pparams, $this->petition);
    $this->assign('is_share', $this->petition['is_share'] ?? FALSE);
    $this->assign('thankyou_title', $this->petition['thankyou_title'] ?? '');
    $this->assign('thankyou_text', $this->petition['thankyou_text'] ?? '');
    if (!empty($this->petition['thankyou_title'])) {
      CRM_Utils_System::setTitle($this->petition['thankyou_title']);
    }

    // send thank you email
    $params['contactId'] = $contact_id;
    $params['email-Primary'] = $email;
    $params['sid'] = $petition_id;
    $params['activityId'] = $activity_id;
    CRM_Campaign_BAO_Petition::sendEmail($params, CRM_Campaign_Form_Petition_Signature::EMAIL_THANK);

    return parent::run();
  }

  /**
   * Confirm email verification.
   *
   * @param int $contact_id
   *   The id of the contact.
   * @param int $subscribe_id
   *   The id of the subscription event.
   * @param string $hash
   *   The hash.
   *
   * @param int $activity_id
   * @param int $petition_id
   *
   * @return bool
   *   True on success
   */
  public static function confirm($contact_id, $subscribe_id, $hash, $activity_id, $petition_id) {
    $se = CRM_Mailing_Event_BAO_MailingEventSubscribe::verify($contact_id, $subscribe_id, $hash);

    if (!$se) {
      return FALSE;
    }

    $transaction = new CRM_Core_Transaction();

    $ce = new CRM_Mailing_Event_BAO_MailingEventConfirm();
    $ce->event_subscribe_id = $se->id;
    $ce->time_stamp = date('YmdHis');
    $ce->save();

    CRM_Contact_BAO_GroupContact::addContactsToGroup(
      [$contact_id],
      $se->group_id,
      'Email',
      'Added',
      $ce->id
    );

    $bao = new CRM_Campaign_BAO_Petition();
    $bao->confirmSignature($activity_id, $contact_id, $petition_id);
  }

}
