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
 * Implements the mailing actions that were previously in
 * CRM_Mailing_Page_Browse then split for AdminUI.
 */
class CRM_Mailing_Form_Action extends CRM_Core_Form {

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
  public $_action;

  /**
   * Whether we are browsing SMS (if not, regular mailings)
   *
   * @var bool
   */
  public $_sms;

  public function preProcess() {
    $this->_mailingId = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    $this->_sms = CRM_Utils_Request::retrieve('sms', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    // @todo Duplicates code with CRM_Mailing_Page_Browse
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

    // This checks the "delete in $module" permissions (copied from old code but not sure if relevant?)
    if (!CRM_Core_Permission::checkActionPermission('CiviMail', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    // check that the user has permission to access mailing id
    CRM_Mailing_BAO_Mailing::checkPermission($this->_mailingId);

    parent::preProcess();
  }

  public function buildQuickForm() {
    $this->add('hidden', 'action', $this->_action);
    $this->add('hidden', 'mid', $this->_mailingId);

    $map_title = [
      CRM_Core_Action::DISABLE => ts('Cancel Mailing'),
      CRM_Core_Action::CLOSE => ts('Pause Mailing'),
      CRM_Core_Action::REOPEN => ts('Resume Mailing'),
      CRM_Core_Action::RENEW => ts('Archive Mailing'),
      CRM_Core_Action::DELETE => ts('Delete Mailing'),
    ];
    $map_message = [
      CRM_Core_Action::DISABLE => ts('Are you sure you want to cancel this mailing?'),
      CRM_Core_Action::CLOSE => ts('Are you sure you want to pause this mailing?'),
      CRM_Core_Action::REOPEN => ts('Are you sure you want to resume this mailing?'),
      CRM_Core_Action::RENEW => ts('Are you sure you want to archive this mailing?'),
      CRM_Core_Action::DELETE => ts('Are you sure you want to delete this mailing?'),
    ];

    if (!empty($map_title[$this->_action])) {
      $label = $map_title[$this->_action];
      $this->assign('message', $map_message[$this->_action]);
      CRM_Utils_System::setTitle($label);
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $label,
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('No'),
          'isDefault' => TRUE,
        ],
      ]);
    }
    else {
      throw new CRM_Core_Exception('unreachable');
    }

    return parent::buildQuickForm();
  }

  public function postProcess() {
    if ($this->_action & CRM_Core_Action::CLOSE) {
      CRM_Mailing_BAO_MailingJob::pause($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been paused. Active message deliveries may continue for a few minutes, but CiviMail will not begin delivery of any more batches.'), ts('Paused'), 'success');
    }
    elseif ($this->_action & CRM_Core_Action::CLOSE) {
      CRM_Mailing_BAO_MailingJob::pause($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been paused. Active message deliveries may continue for a few minutes, but CiviMail will not begin delivery of any more batches.'), ts('Paused'), 'success');
    }
    if ($this->_action & CRM_Core_Action::REOPEN) {
      CRM_Mailing_BAO_MailingJob::resume($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been resumed.'), ts('Resumed'), 'success');
    }
    if ($this->_action & CRM_Core_Action::DISABLE) {
      CRM_Mailing_BAO_MailingJob::cancel($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been canceled.'), ts('Canceled'), 'success');
    }
    elseif ($this->_action & CRM_Core_Action::CLOSE) {
      CRM_Mailing_BAO_MailingJob::pause($this->_mailingId);
      CRM_Core_Session::setStatus(ts('The mailing has been paused. Active message deliveries may continue for a few minutes, but CiviMail will not begin delivery of any more batches.'), ts('Paused'), 'success');
    }
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      // Archive this mailing
      \Civi\Api4\Mailing::update(TRUE)
        ->addValue('is_archived', TRUE)
        ->addWhere('id', '=', $this->_mailingId)
        ->execute();
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      // Used by legacy UI only. AdminUI uses an API4 action
      CRM_Mailing_BAO_Mailing::deleteRecord(['id' => $this->_mailingId]);
      CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');
    }
  }

}
