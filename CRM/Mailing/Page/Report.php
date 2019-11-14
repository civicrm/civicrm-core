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
 * Page to display / edit the header / footer of a mailing.
 */
class CRM_Mailing_Page_Report extends CRM_Core_Page_Basic {
  public $_mailing_id;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO
   */
  public function getBAOName() {
    return 'CRM_Mailing_BAO_Mailing';
  }

  /**
   * An array of action links.
   *
   * @return null
   */
  public function &links() {
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * @return null
   */
  public function editForm() {
    return NULL;
  }

  /**
   * @return string
   */
  public function editName() {
    return 'CiviMail Report';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/mailing/report';
  }

  /**
   * @param null $mode
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&mid=' . $this->_mailing_id;
  }

  /**
   * @return string
   */
  public function run() {
    $this->_mailing_id = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    //CRM-15979 - check if abtest exist for mailing then redirect accordingly
    $abtest = CRM_Mailing_BAO_MailingAB::getABTest($this->_mailing_id);
    if (!empty($abtest) && !empty($abtest->id)) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/abtest/' . $abtest->id));
    }
    // check that the user has permission to access mailing id
    CRM_Mailing_BAO_Mailing::checkPermission($this->_mailing_id);

    $report = CRM_Mailing_BAO_Mailing::report($this->_mailing_id);

    // get contents of mailing
    CRM_Mailing_BAO_Mailing::getMailingContent($report, $this);

    // assign backurl
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($context == 'activitySelector') {
      $backUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=activity");
      $backUrlTitle = ts('Back to Activities');
    }
    elseif ($context == 'activity') {
      $atype = CRM_Utils_Request::retrieve('atype', 'Positive', $this);
      $aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this);

      $backUrl = CRM_Utils_System::url('civicrm/activity/view',
        "atype={$atype}&action=view&reset=1&id={$aid}&cid={$cid}&context=activity"
      );
      $backUrlTitle = ts('Back to Activity');
    }
    elseif ($context == 'mailing') {
      $backUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=mailing");
      $backUrlTitle = ts('Back to Mailing');
    }
    else {
      $backUrl = CRM_Utils_System::url('civicrm/mailing', 'reset=1');
      $backUrlTitle = ts('Back to CiviMail');
    }
    $this->assign('backUrl', $backUrl);
    $this->assign('backUrlTitle', $backUrlTitle);

    $this->assign('report', $report);
    CRM_Utils_System::setTitle(ts('CiviMail Report: %1',
      [1 => $report['mailing']['name']]
    ));
    $this->assign('public_url', CRM_Mailing_BAO_Mailing::getPublicViewUrl($this->_mailing_id));

    return CRM_Core_Page::run();
  }

}
