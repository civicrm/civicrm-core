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
use Civi\Api4\CaseContact;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class assigns the current case to another client.
 */
class CRM_Case_Form_DeleteClient extends CRM_Core_Form {

  /**
   * case ID
   * @var int
   */
  protected $id;

  /**
   * Client ID
   * @var int
   */
  protected $cid;

  /**
   * Return ContactId
   * @var int
   */
  protected $returnContactId;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->returnContactId  = CRM_Utils_Request::retrieve('rcid', 'Positive', $this, TRUE);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    //get current client name.
    $this->assign('currentClientName', CRM_Contact_BAO_Contact::displayName($this->cid));
    $this->assign('id', $this->id);

    //set the context.
    $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$this->cid}&selectedChild=case");
    if ($context == 'search') {
      $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
      //validate the qfKey
      $urlParams = 'force=1';
      if (CRM_Utils_Rule::qfKey($qfKey)) {
        $urlParams .= "&qfKey=$qfKey";
      }
      $url = CRM_Utils_System::url('civicrm/case/search', $urlParams);
    }
    elseif ($context == 'dashboard') {
      $url = CRM_Utils_System::url('civicrm/case', 'reset=1');
    }
    elseif (in_array($context, ['dashlet', 'dashletFullscreen'])) {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
    $caseContacts = CaseContact::get()->addWhere('case_id', '=', $this->id)->execute();
    if (count($caseContacts) === 1) {
      CRM_Core_Error::statusBounce(ts('Cannot Remove Client from case as is the only client on the case'), $url);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('hidden', 'id', $this->id);
    $this->add('hidden', 'contact_id', $this->cid);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Remove Client from Case'),
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    // This form may change the url structure so should not submit via ajax
    $this->preventAjaxSubmit();
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    civicrm_api3('CaseContact', 'get', [
      'case_id' => $params['id'],
      'contact_id' => $params['contact_id'],
      'api.case_contact.delete' => ['id' => "\$value.id"],
    ]);
    civicrm_api3('Activity', 'create', [
      'activity_type_id' => 'Case Client Removed',
      'subject' => ts('Case Client Removed'),
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'case_id' => $params['id'],
      'target_contact_id' => $params['contact_id'],
    ]);

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$this->returnContactId}&id={$params['id']}&show=1"
    );
    CRM_Utils_System::redirect($url);

  }

}
