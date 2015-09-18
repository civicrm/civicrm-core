<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Campaign_Page_Petition_Confirm extends CRM_Core_Page {
  /**
   * @return string
   * @throws Exception
   */
  public function run() {
    CRM_Utils_System::addHTMLHead('<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">');

    $contact_id = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject);
    $subscribe_id = CRM_Utils_Request::retrieve('sid', 'Integer', CRM_Core_DAO::$_nullObject);
    $hash = CRM_Utils_Request::retrieve('h', 'String', CRM_Core_DAO::$_nullObject);
    $activity_id = CRM_Utils_Request::retrieve('a', 'String', CRM_Core_DAO::$_nullObject);
    $petition_id = CRM_Utils_Request::retrieve('pid', 'String', CRM_Core_DAO::$_nullObject);
    if (!$petition_id) {
      $petition_id = CRM_Utils_Request::retrieve('p', 'String', CRM_Core_DAO::$_nullObject);
    }

    if (!$contact_id ||
      !$subscribe_id ||
      !$hash
    ) {
      CRM_Core_Error::fatal(ts("Missing input parameters"));
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
    $this->petition = array();
    CRM_Campaign_BAO_Survey::retrieve($pparams, $this->petition);
    $this->assign('is_share', CRM_Utils_Array::value('is_share', $this->petition));
    $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->petition));
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->petition));
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->petition));

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
    $se = CRM_Mailing_Event_BAO_Subscribe::verify($contact_id, $subscribe_id, $hash);

    if (!$se) {
      return FALSE;
    }

    $transaction = new CRM_Core_Transaction();

    $ce = new CRM_Mailing_Event_BAO_Confirm();
    $ce->event_subscribe_id = $se->id;
    $ce->time_stamp = date('YmdHis');
    $ce->save();

    CRM_Contact_BAO_GroupContact::addContactsToGroup(
      array($contact_id),
      $se->group_id,
      'Email',
      'Added',
      $ce->id
    );

    $bao = new CRM_Campaign_BAO_Petition();
    $bao->confirmSignature($activity_id, $contact_id, $petition_id);
  }

}
