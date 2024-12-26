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
class CRM_Campaign_Page_Petition_ThankYou extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $petition_id = CRM_Utils_Request::retrieve('pid', 'Positive', $this);
    $params['id'] = $petition_id;
    $petition = [];
    CRM_Campaign_BAO_Survey::retrieve($params, $petition);
    $this->assign('petitionTitle', $petition['title']);
    $this->assign('thankyou_title', $petition['thankyou_title'] ?? NULL);
    $this->assign('thankyou_text', $petition['thankyou_text'] ?? NULL);
    $this->assign('survey_id', $petition_id);
    $this->assign('status_id', $id);
    $this->assign('is_share', $petition['is_share'] ?? NULL);
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $petition));

    // send thank you or email verification emails
    /*
     * sendEmailMode
     * 1 = connected user via login/pwd - thank you
     *      or dedupe contact matched who doesn't have a tag CIVICRM_TAG_UNCONFIRMED - thank you
     *      login using fb connect - thank you + click to add msg to fb wall
     * 2 = send a confirmation request email
     */

    return parent::run();
  }

}
