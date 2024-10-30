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
class CRM_Mailing_Form_ForwardMailing extends CRM_Core_Form {

  public function preProcess() {
    $job_id = CRM_Utils_Request::retrieve('jid', 'Positive',
      $this, NULL
    );
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Positive',
      $this, NULL
    );
    $hash = CRM_Utils_Request::retrieve('h', 'String',
      $this, NULL
    );

    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queue_id, $hash);

    if ($q == NULL) {

      // ERROR.
      throw new CRM_Core_Exception(ts('Invalid form parameters.'));
      CRM_Core_Error::statusBounce(ts('Invalid form parameters.'));
    }
    $mailing = &$q->getMailing();

    if ($hash) {
      $emailId = CRM_Core_DAO::getfieldValue('CRM_Mailing_Event_DAO_MailingEventQueue', $hash, 'email_id', 'hash');
      $this->_fromEmail = $fromEmail = CRM_Core_DAO::getfieldValue('CRM_Core_DAO_Email', $emailId, 'email');
      $this->assign('fromEmail', $fromEmail);
    }

    // Show the subject instead of the name here, since it's being
    // displayed to external contacts/users.

    $this->setTitle(ts('Forward Mailing: %1', [1 => $mailing->subject]));

    $this->set('queue_id', $queue_id);
    $this->set('job_id', $job_id);
    $this->set('hash', $hash);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    for ($i = 0; $i < 5; $i++) {
      $this->add('text', "email_$i", ts('Email %1', [1 => $i + 1]));
      $this->addRule("email_$i", ts('Email is not valid.'), 'email');
    }

    //insert message Text by selecting "Select Template option"
    $this->add('textarea', 'forward_comment', ts('Comment'), ['cols' => '80', 'rows' => '8']);
    $this->add('wysiwyg', 'html_comment',
      ts('HTML Message'),
      ['cols' => '80', 'rows' => '8']
    );

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Forward'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * Form submission of new/edit contact is processed.
   */
  public function postProcess() {
    $queue_id = $this->get('queue_id');
    $job_id = $this->get('job_id');
    $hash = $this->get('hash');
    $timeStamp = date('YmdHis');

    $formValues = $this->controller->exportValues($this->_name);
    $params = [];
    $params['body_text'] = $formValues['forward_comment'];
    $html_comment = $formValues['html_comment'];
    $params['body_html'] = str_replace('%7B', '{', str_replace('%7D', '}', $html_comment));

    $emails = [];
    for ($i = 0; $i < 5; $i++) {
      $email = $this->controller->exportValue($this->_name, "email_$i");
      if (!empty($email)) {
        $emails[] = $email;
      }
    }

    $forwarded = NULL;
    foreach ($emails as $email) {
      $params = [
        'version' => 3,
        'job_id' => $job_id,
        'event_queue_id' => $queue_id,
        'hash' => $hash,
        'email' => $email,
        'time_stamp' => $timeStamp,
        'fromEmail' => $this->_fromEmail,
        'params' => $params,
      ];
      $result = civicrm_api('Mailing', 'event_forward', $params);
      if (!civicrm_error($result)) {
        $forwarded++;
      }
    }

    $status = ts('Mailing is not forwarded to the given email address.', [
      'count' => count($emails),
      'plural' => 'Mailing is not forwarded to the given email addresses.',
    ]);
    if ($forwarded) {
      $status = ts('Mailing is forwarded successfully to %count email address.', [
        'count' => $forwarded,
        'plural' => 'Mailing is forwarded successfully to %count email addresses.',
      ]);
    }

    CRM_Utils_System::setUFMessage($status);

    // always redirect to front page of url
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $session->pushUserContext($config->userFrameworkBaseURL);
  }

}
