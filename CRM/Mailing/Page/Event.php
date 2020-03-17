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
 * This implements the profile page for all contacts.
 *
 * It uses a selector object to do the actual display. The fields displayed are controlled by
 * the admin
 */
class CRM_Mailing_Page_Event extends CRM_Core_Page {

  /**
   * All the fields that are listings related.
   *
   * @var array
   */
  protected $_fields;

  /**
   * Run this page (figure out the action needed and perform it).
   */
  public function run() {
    $selector = new CRM_Mailing_Selector_Event(
      CRM_Utils_Request::retrieve('event', 'String', $this),
      CRM_Utils_Request::retrieve('distinct', 'Boolean', $this),
      CRM_Utils_Request::retrieve('mid', 'Positive', $this),
      CRM_Utils_Request::retrieve('jid', 'Positive', $this),
      CRM_Utils_Request::retrieve('uid', 'Positive', $this)
    );

    $mailing_id = CRM_Utils_Request::retrieve('mid', 'Positive', $this);

    // check that the user has permission to access mailing id
    CRM_Mailing_BAO_Mailing::checkPermission($mailing_id);

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    if ($context == 'activitySelector') {
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $backUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=activity");
      $backUrlTitle = ts('Back to Activities');
    }
    elseif ($context == 'mailing') {
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $backUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=mailing");
      $backUrlTitle = ts('Back to Mailing');
    }
    elseif ($context == 'angPage') {
      $angPage = CRM_Utils_Request::retrieve('angPage', 'String', $this);
      if (!preg_match(':^[a-zA-Z0-9\-_/]+$:', $angPage)) {
        throw new CRM_Core_Exception('Malformed return URL');
      }
      $backUrl = CRM_Utils_System::url('civicrm/a/#/' . $angPage);
      $backUrlTitle = ts('Back to Report');
    }
    else {
      $backUrl = CRM_Utils_System::url('civicrm/mailing/report', "reset=1&mid={$mailing_id}");
      $backUrlTitle = ts('Back to Report');
    }

    $this->assign('backUrl', $backUrl);
    $this->assign('backUrlTitle', $backUrlTitle);

    CRM_Utils_System::setTitle($selector->getTitle());
    $this->assign('title', $selector->getTitle());
    $this->assign('mailing_id', $mailing_id);

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $controller = new CRM_Core_Selector_Controller(
      $selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TEMPLATE
    );

    $controller->setEmbedded(TRUE);
    $controller->run();

    return parent::run();
  }

}
