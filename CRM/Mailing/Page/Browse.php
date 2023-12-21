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
 * This implements the profile page for all contacts. It uses a selector
 * object to do the actual display. The fields displayed are controlled by
 * the admin
 */
class CRM_Mailing_Page_Browse extends CRM_Core_Page {

  /**
   * All the fields that are listings related.
   *
   * @var array
   */
  protected $_fields;

  /**
   * The mailing id of the mailing we're operating on
   *
   * @var int
   */
  protected $_mailingId;

  /**
   * The action that we are performing (in CRM_Core_Action terms)
   *
   * @var int
   */
  protected $_action;

  public $_sortByCharacter;

  public $_unscheduled;
  public $_archived;

  /**
   * Scheduled mailing.
   *
   * @var bool
   */
  public $_scheduled;

  public $_sms;

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    Civi::resources()->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->_unscheduled = $archiveLinks = FALSE;
    $this->_mailingId = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    $this->_sms = CRM_Utils_Request::retrieve('sms', 'Positive', $this);

    if ($this->_sms) {
      // if this is an SMS page, check that the user has permission to browse SMS
      if (!CRM_Core_Permission::check('send SMS')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to send SMS'));
      }
    }
    else {
      // If this is not an SMS page, check that the user has an appropriate
      // permission (specific permissions have been copied from
      // CRM/Mailing/xml/Menu/Mailing.xml)
      if (!CRM_Core_Permission::check([['access CiviMail', 'approve mailings', 'create mailings', 'schedule mailings']])) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to view this page.'));
      }
    }

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
    $this->assign('archiveLinks', $archiveLinks ?? FALSE);
  }

  /**
   * Run this page (figure out the action needed and perform it).
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $this->preProcess();

    $newArgs = func_get_args()[0];

    $this->_sortByCharacter
      = CRM_Utils_Request::retrieve('sortByCharacter', 'String', $this);

    // CRM-11920 "all" should set sortByCharacter to null, not empty string
    if (strtolower($this->_sortByCharacter ?: '') === 'all' || !empty($_POST)) {
      $this->_sortByCharacter = NULL;
      $this->set('sortByCharacter', NULL);
    }

    if (($newArgs[3] ?? NULL) === 'unscheduled') {
      $this->_unscheduled = TRUE;
    }
    $this->set('unscheduled', $this->_unscheduled);

    $this->set('archived', $this->isArchived($newArgs));

    if (($newArgs[3] ?? NULL) === 'scheduled') {
      $this->_scheduled = TRUE;
    }
    $this->set('scheduled', $this->_scheduled);

    $createdId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    if ($createdId) {
      $this->set('createdId', $createdId);
    }

    if ($this->_sms) {
      $this->set('sms', $this->_sms);
    }

    $session = CRM_Core_Session::singleton();
    $context = $session->readUserContext();

    if ($this->_action & CRM_Core_Action::DISABLE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {
        CRM_Mailing_BAO_MailingJob::cancel($this->_mailingId);
        CRM_Core_Session::setStatus(ts('The mailing has been canceled.'), ts('Canceled'), 'success');
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
    elseif ($this->_action & CRM_Core_Action::CLOSE) {
      if (!CRM_Core_Permission::checkActionPermission('CiviMail', CRM_Core_Action::CLOSE)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      CRM_Mailing_BAO_MailingJob::pause($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been paused. Active message deliveries may continue for a few minutes, but CiviMail will not begin delivery of any more batches.'), ts('Paused'), 'success');
      CRM_Utils_System::redirect($context);
    }
    elseif ($this->_action & CRM_Core_Action::REOPEN) {
      if (!CRM_Core_Permission::checkActionPermission('CiviMail', CRM_Core_Action::CLOSE)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      CRM_Mailing_BAO_MailingJob::resume($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been resumed.'), ts('Resumed'), 'success');
      CRM_Utils_System::redirect($context);
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {

        // check for action permissions.
        if (!CRM_Core_Permission::checkActionPermission('CiviMail', $this->_action)) {
          CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
        }

        CRM_Mailing_BAO_Mailing::deleteRecord(['id' => $this->_mailingId]);
        CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');
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
      // archive this mailing, CRM-3752.
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean', $this)) {
        // set is_archived to 1
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

    $this->assign('unscheduled', FALSE);
    $this->assign('archived', FALSE);

    $urlParams = 'reset=1';
    $urlString = 'civicrm/mailing/browse';
    if ($this->get('sms')) {
      $urlParams .= '&sms=1';
    }
    if (($newArgs[3] ?? NULL) === 'unscheduled') {
      $urlString .= '/unscheduled';
      $urlParams .= '&scheduled=false';
      $this->assign('unscheduled', TRUE);
    }

    if ($this->isArchived($newArgs)) {
      $urlString .= '/archived';
      $this->assign('archived', TRUE);
    }
    elseif (($newArgs[3] ?? NULL) == 'scheduled') {
      $urlString .= '/scheduled';
      $urlParams .= '&scheduled=true';
    }
    if ($this->get('sms')) {
      CRM_Utils_System::setTitle(ts('Find Mass SMS'));
    }

    $crmRowCount = CRM_Utils_Request::retrieve('crmRowCount', 'Integer');
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer');
    if ($crmRowCount || $crmPID) {
      $urlParams .= '&force=1';
      $urlParams .= $crmRowCount ? '&crmRowCount=' . $crmRowCount : '';
      $urlParams .= $crmPID ? '&crmPID=' . $crmPID : '';
    }

    $crmSID = CRM_Utils_Request::retrieve('crmSID', 'Integer');
    if ($crmSID) {
      $urlParams .= '&crmSID=' . $crmSID;
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url($urlString, $urlParams);
    $session->pushUserContext($url);

    // CRM-6862 -run form controller after
    // selector, since it erase $_POST
    $this->search();

    return parent::run();
  }

  public function search() {
    if ($this->_action & (CRM_Core_Action::ADD |
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
   * @param array $params
   * @param bool $sortBy
   *
   * @return string
   */
  public function whereClause(&$params, $sortBy = TRUE) {
    $clauses = [];
    $title = $this->get('mailing_name');
    if ($title) {
      $clauses[] = 'name LIKE %1';
      if (strpos($title, '%') !== FALSE) {
        $params[1] = [$title, 'String', FALSE];
      }
      else {
        $params[1] = [$title, 'String', TRUE];
      }
    }

    if ($sortBy &&
      $this->_sortByCharacter !== NULL
    ) {
      $clauses[] = "name LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_sortByCharacter)) . "%'";
    }

    $campaignIds = $this->get('campaign_id');
    if (!CRM_Utils_System::isNull($campaignIds)) {
      if (!is_array($campaignIds)) {
        $campaignIds = [$campaignIds];
      }
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', array_values($campaignIds)) . ' ) )';
    }

    return implode(' AND ', $clauses);
  }

  /**
   * Is the search limited to archived mailings.
   *
   * @param array $urlArguments
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function isArchived($urlArguments): bool {
    return in_array('archived', $urlArguments, TRUE) || CRM_Utils_Request::retrieveValue('is_archived', 'Boolean');
  }

}
