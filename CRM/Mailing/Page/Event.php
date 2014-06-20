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

/**
 * This implements the profile page for all contacts. It uses a selector
 * object to do the actual dispay. The fields displayd are controlled by
 * the admin
 */
class CRM_Mailing_Page_Event extends CRM_Core_Page {

  /**
   * all the fields that are listings related
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $selector = &new CRM_Mailing_Selector_Event(
      CRM_Utils_Request::retrieve('event', 'String', $this),
      CRM_Utils_Request::retrieve('distinct', 'Boolean', $this),
      CRM_Utils_Request::retrieve('mid', 'Positive', $this),
      CRM_Utils_Request::retrieve('jid', 'Positive', $this),
      CRM_Utils_Request::retrieve('uid', 'Positive', $this)
    );

    $mailing_id = CRM_Utils_Request::retrieve('mid', 'Positive', $this);

    //assign backurl
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);

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

