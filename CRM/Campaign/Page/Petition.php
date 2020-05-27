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
 * Page for displaying Petition Signatures.
 */
class CRM_Campaign_Page_Petition extends CRM_Core_Page {

  public function browse() {

    //get the survey id
    $surveyId = CRM_Utils_Request::retrieve('sid', 'Positive', $this);

    $signatures = CRM_Campaign_BAO_Petition::getPetitionSignature($surveyId);

    $this->assign('signatures', $signatures);
  }

  /**
   * @return string
   */
  public function run() {
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 0
    );
    $this->assign('action', $action);
    $this->browse();

    return parent::run();
  }

}
