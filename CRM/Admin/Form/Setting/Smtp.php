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
 * This class generates form components for Smtp Server.
 */
class CRM_Admin_Form_Setting_Smtp extends CRM_Admin_Form_Setting {
  protected $_testButtonName;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $props = [];
    $settings = Civi::settings()->getMandatory('mailing_backend') ?? [];
    //Load input as readonly whose values are overridden in civicrm.settings.php.
    foreach ($settings as $setting => $value) {
      if (isset($value)) {
        $props[$setting]['readonly'] = TRUE;
        $setStatus = TRUE;
      }
    }

    $outBoundOption = [
      CRM_Mailing_Config::OUTBOUND_OPTION_MAIL => ts('mail()'),
      CRM_Mailing_Config::OUTBOUND_OPTION_SMTP => ts('SMTP'),
      CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL => ts('Sendmail'),
      CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED => ts('Disable Outbound Email'),
      CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB => ts('Redirect to Database'),
    ];
    $this->addRadio('outBound_option', ts('Select Mailer'), $outBoundOption, $props['outBound_option'] ?? []);

    $this->setTitle(ts('Settings - Outbound Mail'));
    $this->add('text', 'sendmail_path', ts('Sendmail Path'));
    $this->add('text', 'sendmail_args', ts('Sendmail Argument'));
    $this->add('text', 'smtpServer', ts('SMTP Server'), $props['smtpServer'] ?? NULL);
    $this->add('text', 'smtpPort', ts('SMTP Port'), $props['smtpPort'] ?? NULL);
    $this->addYesNo('smtpAuth', ts('Authentication?'), $props['smtpAuth'] ?? NULL);
    $this->addElement('text', 'smtpUsername', ts('SMTP Username'), $props['smtpUsername'] ?? NULL);
    $this->addElement('password', 'smtpPassword', ts('SMTP Password'), $props['smtpPassword'] ?? NULL);

    $this->_testButtonName = $this->getButtonName('refresh', 'test');

    $this->addFormRule(['CRM_Admin_Form_Setting_Smtp', 'formRule']);
    parent::buildQuickForm();
    $buttons = $this->getElement('buttons')->getElements();
    $buttons[] = $this->createElement(
      'xbutton',
      $this->_testButtonName,
      CRM_Core_Page::crmIcon('fa-envelope-o') . ' ' . ts('Save & Send Test Email'),
      ['type' => 'submit']
    );
    $this->getElement('buttons')->setElements($buttons);

    if (!empty($setStatus)) {
      CRM_Core_Session::setStatus(ts("Some fields are loaded as 'readonly' as they have been set (overridden) in civicrm.settings.php."), '', 'info', ['expires' => 0]);
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
        [$toDisplayName, $toEmail] = CRM_Contact_BAO_Contact::getContactDetails($userID);

        //get the default domain email address.CRM-4250
        [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

        if (!$domainEmailAddress || $domainEmailAddress === 'info@EXAMPLE.ORG') {
          $fixUrl = CRM_Utils_System::url('civicrm/admin/options/site_email_address');
          CRM_Core_Error::statusBounce(ts('The site administrator needs to enter a valid "Site Email Address" in <a href="%1">Administer CiviCRM &raquo; Communications &raquo; Site Email Addresses</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]));
        }
        if (!$toEmail) {
          CRM_Core_Error::statusBounce(ts('Cannot send a test email because your user record does not have a valid email address.'));
        }

        if (!trim($toDisplayName)) {
          $toDisplayName = $toEmail;
        }

        $to = '"' . $toDisplayName . '"' . "<$toEmail>";
        $from = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';

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

        $mailParams = [
          'from' => $from,
          'to' => $to,
          'subject' => $subject,
          'text' => $message,
          'toEmail' => $toEmail,
        ];

        $mailer = CRM_Utils_Mail::_createMailer($mailerName, $params);
        CRM_Utils_Mail::sendTest($mailer, $mailParams);
      }
    }

    $mailingBackend = Civi::settings()->get('mailing_backend');

    if (!empty($mailingBackend)) {
      $formValues = array_merge($mailingBackend, $formValues);
    }

    // if password is present, encrypt it
    if (!empty($formValues['smtpPassword'])) {
      $formValues['smtpPassword'] = \Civi::service('crypto.token')->encrypt($formValues['smtpPassword'], 'CRED');
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
        $errors['smtpServer'] = ts('SMTP Server name is a required field.');
      }
      if (empty($fields['smtpPort'])) {
        $errors['smtpPort'] = ts('SMTP Port is a required field.');
      }
      if (!empty($fields['smtpAuth'])) {
        if (empty($fields['smtpUsername'])) {
          $errors['smtpUsername'] = ts('If your SMTP server requires authentication please provide a valid user name.');
        }
        if (empty($fields['smtpPassword'])) {
          $errors['smtpPassword'] = ts('If your SMTP server requires authentication, please provide a password.');
        }
      }
    }
    if ($fields['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
      if (!$fields['sendmail_path']) {
        $errors['sendmail_path'] = ts('Sendmail Path is a required field.');
      }
      if (!$fields['sendmail_args']) {
        $errors['sendmail_args'] = ts('Sendmail Argument is a required field.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    parent::setDefaultValues();

    $mailingBackend = Civi::settings()->get('mailing_backend');
    if (!empty($mailingBackend)) {
      $this->_defaults += $mailingBackend;

      if (!empty($mailingBackend['smtpPassword'])) {
        try {
          $this->_defaults['smtpPassword'] = \Civi::service('crypto.token')->decrypt($this->_defaults['smtpPassword']);
        }
        catch (Exception $e) {
          Civi::log()->error($e->getMessage());
          CRM_Core_Session::setStatus(ts('Unable to retrieve the encrypted password. Please check your configured encryption keys. The error message is: %1', [1 => $e->getMessage()]), ts("Encryption key error"), "error");
        }
      }
    }
    else {
      if (!isset($mailingBackend['smtpServer'])) {
        $this->_defaults['smtpServer'] = 'localhost';
        $this->_defaults['smtpPort'] = 25;
        $this->_defaults['smtpAuth'] = 0;
      }

      if (!isset($mailingBackend['sendmail_path'])) {
        $this->_defaults['sendmail_path'] = '/usr/sbin/sendmail';
        $this->_defaults['sendmail_args'] = '-i';
      }
    }

    return $this->_defaults;
  }

}
