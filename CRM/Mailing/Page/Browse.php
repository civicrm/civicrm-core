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
class CRM_Mailing_Page_Browse extends CRM_Core_Page {

  /**
   * all the fields that are listings related
   *
   * @var array
   * @access protected
   */
  protected $_fields;

  /**
   * the mailing id of the mailing we're operating on
   *
   * @int
   * @access protected
   */
  protected $_mailingId;

  /**
   * the action that we are performing (in CRM_Core_Action terms)
   *
   * @int
   * @access protected
   */
  protected $_action;

  public $_sortByCharacter;

  public $_unscheduled;
  public $_archived;

  /**
   * scheduled mailing
   *
   * @boolean
   * @access public
   */
  public $_scheduled;

  public $_sms;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    $this->_unscheduled = $this->_archived = $archiveLinks = FALSE;
    $this->_mailingId   = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    $this->_sms         = CRM_Utils_Request::retrieve('sms', 'Positive', $this);
    $this->assign('sms', $this->_sms);
    // check that the user has permission to access mailing id
    CRM_Mailing_BAO_Mailing::checkPermission($this->_mailingId);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->assign('action', $this->_action);

    $showLinks = TRUE;
    if (CRM_Mailing_Info::workflowEnabled()) {
      if (CRM_Core_Permission::check('create mailings')) {
        $archiveLinks = TRUE;
      }
      if (!CRM_Core_Permission::check('access CiviMail') &&
        !CRM_Core_Permission::check('create mailings')
      ) {
        $showLinks = FALSE;
      }
    }
    $this->assign('showLinks', $showLinks);
    if (CRM_Core_Permission::check('access CiviMail')) {
      $archiveLinks = TRUE;
    }
    if ($archiveLinks == TRUE) {
      $this->assign('archiveLinks', $archiveLinks);
    }
  }

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $this->preProcess();

    $newArgs = func_get_args();
    // since we want only first function argument
    $newArgs = $newArgs[0];
    if (isset($_GET['runJobs']) || CRM_Utils_Array::value('2', $newArgs) == 'queue') {
      $config = CRM_Core_Config::singleton();
      CRM_Mailing_BAO_MailingJob::runJobs_pre($config->mailerJobSize);
      CRM_Mailing_BAO_MailingJob::runJobs();
      CRM_Mailing_BAO_MailingJob::runJobs_post();
    }

    $this->_sortByCharacter =
      CRM_Utils_Request::retrieve('sortByCharacter', 'String', $this);


    // CRM-11920 all should set sortByCharacter to null, not empty string
    if (strtolower($this->_sortByCharacter) == 'all' || !empty($_POST)) {
      $this->_sortByCharacter = null;
      $this->set('sortByCharacter', null);
    }

    if (CRM_Utils_Array::value(3, $newArgs) == 'unscheduled') {
      $this->_unscheduled = TRUE;
    }
    $this->set('unscheduled', $this->_unscheduled);

    if (CRM_Utils_Array::value(3, $newArgs) == 'archived') {
      $this->_archived = TRUE;
    }
    $this->set('archived', $this->_archived);

    if (CRM_Utils_Array::value(3, $newArgs) == 'scheduled') {
      $this->_scheduled = TRUE;
    }
    $this->set('scheduled', $this->_scheduled);

    $this->_createdId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    if ($this->_createdId) {
      $this->set('createdId', $this->_createdId);
    }

    if ($this->_sms) {
      $this->set('sms', $this->_sms);
    }

    $session = CRM_Core_Session::singleton();
    $context = $session->readUserContext();

    if ($this->_action & CRM_Core_Action::DISABLE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {
        CRM_Mailing_BAO_MailingJob::cancel($this->_mailingId);
        CRM_Utils_System::redirect($context);
      }
      else {
        $controller = new CRM_Core_Controller_Simple('CRM_Mailing_Form_Browse',
          ts('Cancel Mailing'),
          $this->_action
        );
        $controller->setEmbedded(TRUE);
        $controller->run();
      }
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {

        // check for action permissions.
        if (!CRM_Core_Permission::checkActionPermission('CiviMail', $this->_action)) {
          CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
        }

        CRM_Mailing_BAO_Mailing::del($this->_mailingId);
        CRM_Utils_System::redirect($context);
      }
      else {
        $controller = new CRM_Core_Controller_Simple('CRM_Mailing_Form_Browse',
          ts('Delete Mailing'),
          $this->_action
        );
        $controller->setEmbedded(TRUE);
        $controller->run();
      }
    }
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      //archive this mailing, CRM-3752.
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {
        //set is_archived to 1
        CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingId, 'is_archived', TRUE);
        CRM_Utils_System::redirect($context);
      }
      else {
        $controller = new CRM_Core_Controller_Simple('CRM_Mailing_Form_Browse',
          ts('Archive Mailing'),
          $this->_action
        );
        $controller->setEmbedded(TRUE);
        $controller->run();
      }
    }

    $selector = new CRM_Mailing_Selector_Browse();
    $selector->setParent($this);

    $controller = new CRM_Core_Selector_Controller(
      $selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->get(CRM_Utils_Sort::SORT_ID) . $this->get(CRM_Utils_Sort::SORT_DIRECTION),
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TEMPLATE
    );


    $controller->setEmbedded(TRUE);
    $controller->run();

    //hack to display results as per search
    $rows = $controller->getRows($controller);

    $this->assign('rows', $rows);

    $urlParams = 'reset=1';
    $urlString = 'civicrm/mailing/browse';
    if ($this->get('sms')) {
      $urlParams .= '&sms=1';
    }
    if (CRM_Utils_Array::value(3, $newArgs) == 'unscheduled') {
      $urlString .= '/unscheduled';
      $urlParams .= '&scheduled=false';
      $this->assign('unscheduled', TRUE);
    }
    elseif (CRM_Utils_Array::value(3, $newArgs) == 'archived') {
      $urlString .= '/archived';
      $this->assign('archived', TRUE);
    }
    elseif (CRM_Utils_Array::value(3, $newArgs) == 'scheduled') {
      $urlString .= '/scheduled';
      $urlParams .= '&scheduled=true';
    }
    if ($this->get('sms')) {
      CRM_Utils_System::setTitle(ts('Find Mass SMS'));
    }

    $crmRowCount = CRM_Utils_Request::retrieve('crmRowCount', 'Integer', CRM_Core_DAO::$_nullObject);
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);
    if ($crmRowCount || $crmPID) {
      $urlParams .= '&force=1';
      $urlParams .= $crmRowCount ? '&crmRowCount=' . $crmRowCount : '';
      $urlParams .= $crmPID ? '&crmPID=' . $crmPID : '';
    }

    $crmSID = CRM_Utils_Request::retrieve('crmSID', 'Integer', CRM_Core_DAO::$_nullObject);
    if ($crmSID) {
      $urlParams .= '&crmSID=' . $crmSID;
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url($urlString, $urlParams);
    $session->pushUserContext($url);

    //CRM-6862 -run form cotroller after
    //selector, since it erase $_POST
    $this->search();

    return parent::run();
  }

  function search() {
    if ($this->_action &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Mailing_Form_Search',
      ts('Search Mailings'),
      CRM_Core_Action::ADD
    );
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  /**
   * @param $params
   * @param bool $sortBy
   *
   * @return string
   */
  function whereClause(&$params, $sortBy = TRUE) {
    $values = array();

    $clauses = array();
    $title = $this->get('mailing_name');
    //echo " name=$title  ";
    if ($title) {
      $clauses[] = 'name LIKE %1';
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    if ($sortBy &&
      $this->_sortByCharacter !== NULL
    ) {
      $clauses[] = "name LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_sortByCharacter)) . "%'";
    }

    $campainIds = $this->get('campaign_id');
    if (!CRM_Utils_System::isNull($campainIds)) {
      if (!is_array($campainIds)) {
        $campaignIds = array($campaignIds);
      }
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', array_values($campainIds)) . ' ) )';
    }

    return implode(' AND ', $clauses);
  }
}

