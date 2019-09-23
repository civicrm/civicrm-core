<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for Smtp Server.
 */
class CRM_Admin_Form_Setting_Smtp extends CRM_Admin_Form_Setting {
  protected $_testButtonName;
  protected $_settings = [
    'allow_mail_from_logged_in_contact' => CRM_Core_BAO_Setting::DIRECTORY_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $outBoundOption = [
      CRM_Mailing_Config::OUTBOUND_OPTION_MAIL => ts('mail()'),
      CRM_Mailing_Config::OUTBOUND_OPTION_SMTP => ts('SMTP'),
      CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL => ts('Sendmail'),
      CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED => ts('Disable Outbound Email'),
      CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB => ts('Redirect to Database'),
    ];
    $this->addRadio('outBound_option', ts('Select Mailer'), $outBoundOption);

    $props = array();
    $settings = Civi::settings()->getMandatory('mailing_backend') ?? [];
    //Load input as readonly whose values are overridden in civicrm.settings.php.
    foreach ($settings as $setting => $value) {
      if (isset($value)) {
        $props[$setting]['readonly'] = TRUE;
        $setStatus = TRUE;
      }
    }

    CRM_Utils_System::setTitle(ts('Settings - Outbound Mail'));
    $this->add('text', 'sendmail_path', ts('Sendmail Path'));
    $this->add('text', 'sendmail_args', ts('Sendmail Argument'));
    $this->add('text', 'smtpServer', ts('SMTP Server'), CRM_Utils_Array::value('smtpServer', $props));
    $this->add('text', 'smtpPort', ts('SMTP Port'), CRM_Utils_Array::value('smtpPort', $props));
    $this->addYesNo('smtpAuth', ts('Authentication?'), CRM_Utils_Array::value('smtpAuth', $props));
    $this->addElement('text', 'smtpUsername', ts('SMTP Username'), CRM_Utils_Array::value('smtpUsername', $props));
    $this->addElement('password', 'smtpPassword', ts('SMTP Password'), CRM_Utils_Array::value('smtpPassword', $props));

    $this->_testButtonName = $this->getButtonName('refresh', 'test');

    $this->addFormRule(['CRM_Admin_Form_Setting_Smtp', 'formRule']);
    parent::buildQuickForm();
    $buttons = $this->getElement('buttons')->getElements();
    $buttons[] = $this->createElement('submit', $this->_testButtonName, ts('Save & Send Test Email'), ['crm-icon' => 'fa-envelope-o']);
    $this->getElement('buttons')->setElements($buttons);

    if (!empty($setStatus)) {
      CRM_Core_Session::setStatus("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php.", '', 'info', array('expires' => 0));
    }
  }

  /**
   * Process the form submission.
   *
   * @throws \Exception
   */
  public function postProcess() {
    // flush caches so we reload details for future requests
    // CRM-11967
    CRM_Utils_System::flushCache();

    $formValues = $this->controller->exportValues($this->_name);

    Civi::settings()->set('allow_mail_from_logged_in_contact', (!empty($formValues['allow_mail_from_logged_in_contact'])));
    unset($formValues['allow_mail_from_logged_in_contact']);

    $buttonName = $this->controller->getButtonName();
    // check if test button
    if ($buttonName == $this->_testButtonName) {
      if ($formValues['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED) {
        CRM_Core_Session::setStatus(ts('You have selected "Disable Outbound Email". A test email can not be sent.'), ts("Email Disabled"), "error");
      }
      elseif ($formValues['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB) {
        CRM_Core_Session::setStatus(ts('You have selected "Redirect to Database". A test email can not be sent.'), ts("Email Disabled"), "error");
      }
      else {
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID');
        list($toDisplayName, $toEmail) = CRM_Contact_BAO_Contact::getContactDetails($userID);

        //get the default domain email address.CRM-4250
        list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

        if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
          $fixUrl = CRM_Utils_System::url("civicrm/admin/options/from_email_address", 'action=update&reset=1');
          CRM_Core_Error::statusBounce(ts('The site administrator needs to enter a valid \'FROM Email Address\' in <a href="%1">Administer CiviCRM &raquo; System Settings &raquo; Option Groups &raquo; From Email Address</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]));
        }
        if (!$toEmail) {
          CRM_Core_Error::statusBounce(ts('Cannot send a test email because your user record does not have a valid email address.'));
        }

        if (!trim($toDisplayName)) {
          $toDisplayName = $toEmail;
        }

        $to = '"' . $toDisplayName . '"' . "<$toEmail>";
        $from = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';
        $testMailStatusMsg = ts('Sending test email') . ':<br />'
          . ts('From: %1', [1 => $domainEmailAddress]) . '<br />'
          . ts('To: %1', [1 => $toEmail]) . '<br />';

        $params = [];
        if ($formValues['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
          $subject = "Test for SMTP settings";
          $message = "SMTP settings are correct.";

          $params['host'] = $formValues['smtpServer'];
          $params['port'] = $formValues['smtpPort'];

          if ($formValues['smtpAuth']) {
            $params['username'] = $formValues['smtpUsername'];
            $params['password'] = $formValues['smtpPassword'];
            $params['auth'] = TRUE;
          }
          else {
            $params['auth'] = FALSE;
          }

          // set the localhost value, CRM-3153, CRM-9332
          $params['localhost'] = $_SERVER['SERVER_NAME'];

          // also set the timeout value, lets set it to 30 seconds
          // CRM-7510, CRM-9332
          $params['timeout'] = 30;

          $mailerName = 'smtp';
        }
        elseif ($formValues['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
          $subject = "Test for Sendmail settings";
          $message = "Sendmail settings are correct.";
          $params['sendmail_path'] = $formValues['sendmail_path'];
          $params['sendmail_args'] = $formValues['sendmail_args'];
          $mailerName = 'sendmail';
        }
        elseif ($formValues['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MAIL) {
          $subject = "Test for PHP mail settings";
          $message = "mail settings are correct.";
          $mailerName = 'mail';
        }

        $headers = [
          'From' => $from,
          'To' => $to,
          'Subject' => $subject,
        ];

        $mailer = Mail::factory($mailerName, $params);

        $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
        $result = $mailer->send($toEmail, $headers, $message);
        unset($errorScope);
        if (defined('CIVICRM_MAIL_LOG') && defined('CIVICRM_MAIL_LOG_AND_SEND')) {
          $testMailStatusMsg .= '<br />' . ts('You have defined CIVICRM_MAIL_LOG_AND_SEND - mail will be logged.') . '<br /><br />';
        }
        if (defined('CIVICRM_MAIL_LOG') && !defined('CIVICRM_MAIL_LOG_AND_SEND')) {
          CRM_Core_Session::setStatus($testMailStatusMsg . ts('You have defined CIVICRM_MAIL_LOG - no mail will be sent.  Your %1 settings have not been tested.', [1 => strtoupper($mailerName)]), ts("Mail not sent"), "warning");
        }
        elseif (!is_a($result, 'PEAR_Error')) {
          CRM_Core_Session::setStatus($testMailStatusMsg . ts('Your %1 settings are correct. A test email has been sent to your email address.', [1 => strtoupper($mailerName)]), ts("Mail Sent"), "success");
        }
        else {
          $message = CRM_Utils_Mail::errorMessage($mailer, $result);
          CRM_Core_Session::setStatus($testMailStatusMsg . ts('Oops. Your %1 settings are incorrect. No test mail has been sent.', [1 => strtoupper($mailerName)]) . $message, ts("Mail Not Sent"), "error");
        }
      }
    }

    $mailingBackend = Civi::settings()->get('mailing_backend');

    if (!empty($mailingBackend)) {
      $formValues = array_merge($mailingBackend, $formValues);
    }

    // if password is present, encrypt it
    if (!empty($formValues['smtpPassword'])) {
      $formValues['smtpPassword'] = CRM_Utils_Crypt::encrypt($formValues['smtpPassword']);
    }

    Civi::settings()->set('mailing_backend', $formValues);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    if ($fields['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
      if (empty($fields['smtpServer'])) {
        $errors['smtpServer'] = 'SMTP Server name is a required field.';
      }
      if (empty($fields['smtpPort'])) {
        $errors['smtpPort'] = 'SMTP Port is a required field.';
      }
      if (!empty($fields['smtpAuth'])) {
        if (empty($fields['smtpUsername'])) {
          $errors['smtpUsername'] = 'If your SMTP server requires authentication please provide a valid user name.';
        }
        if (empty($fields['smtpPassword'])) {
          $errors['smtpPassword'] = 'If your SMTP server requires authentication, please provide a password.';
        }
      }
    }
    if ($fields['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
      if (!$fields['sendmail_path']) {
        $errors['sendmail_path'] = 'Sendmail Path is a required field.';
      }
      if (!$fields['sendmail_args']) {
        $errors['sendmail_args'] = 'Sendmail Argument is a required field.';
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = [];

      $mailingBackend = Civi::settings()->get('mailing_backend');
      if (!empty($mailingBackend)) {
        $this->_defaults = $mailingBackend;

        if (!empty($this->_defaults['smtpPassword'])) {
          $this->_defaults['smtpPassword'] = CRM_Utils_Crypt::decrypt($this->_defaults['smtpPassword']);
        }
      }
      else {
        if (!isset($this->_defaults['smtpServer'])) {
          $this->_defaults['smtpServer'] = 'localhost';
          $this->_defaults['smtpPort'] = 25;
          $this->_defaults['smtpAuth'] = 0;
        }

        if (!isset($this->_defaults['sendmail_path'])) {
          $this->_defaults['sendmail_path'] = '/usr/sbin/sendmail';
          $this->_defaults['sendmail_args'] = '-i';
        }
      }
    }
    $this->_defaults['allow_mail_from_logged_in_contact'] = Civi::settings()->get('allow_mail_from_logged_in_contact');
    return $this->_defaults;
  }

}
