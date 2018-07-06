<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This file is used to build the form configuring mass sms details.
 */
class CRM_SMS_Form_Upload extends CRM_Core_Form {
  public $_mailingID;

  public function preProcess() {
    $this->_mailingID = $this->get('mailing_id');
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);

    // Need to differentiate new/reuse mailing, CRM-2873.
    $reuseMailing = FALSE;
    if ($mailingID) {
      $reuseMailing = TRUE;
    }
    else {
      $mailingID = $this->_mailingID;
    }

    $count = $this->get('count');
    $this->assign('count', $count);

    $this->set('skipTextFile', FALSE);

    $defaults = array();

    if ($mailingID) {
      $dao = new CRM_Mailing_DAO_Mailing();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      $dao->storeValues($dao, $defaults);

      // We don't want to retrieve template details once it is
      // set in session.
      $templateId = $this->get('template');
      $this->assign('templateSelected', $templateId ? $templateId : 0);
      if (isset($defaults['msg_template_id']) && !$templateId) {
        $defaults['SMStemplate'] = $defaults['msg_template_id'];
        $messageTemplate = new CRM_Core_DAO_MessageTemplate();
        $messageTemplate->id = $defaults['msg_template_id'];
        $messageTemplate->selectAdd();
        $messageTemplate->selectAdd('msg_text');
        $messageTemplate->find(TRUE);

        $defaults['sms_text_message'] = $messageTemplate->msg_text;
      }

      if (isset($defaults['body_text'])) {
        $defaults['sms_text_message'] = $defaults['body_text'];
        $this->set('textFile', $defaults['body_text']);
        $this->set('skipTextFile', TRUE);
      }
    }

    // Fix for CRM-2873.
    if (!$reuseMailing) {
      $textFilePath = $this->get('textFilePath');
      if ($textFilePath &&
        file_exists($textFilePath)
      ) {
        $defaults['sms_text_message'] = file_get_contents($textFilePath);
        if (strlen($defaults['sms_text_message']) > 0) {
          $this->set('skipTextFile', TRUE);
        }
      }
    }

    $defaults['upload_type'] = 1;

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $options = array();
    $tempVar = FALSE;

    $this->assign('max_sms_length', CRM_SMS_Provider::MAX_SMS_CHAR);

    // this seems so hacky, not sure what we are doing here and why. Need to investigate and fix
    $session->getVars($options,
      "CRM_SMS_Controller_Send_{$this->controller->_key}"
    );

    $attributes = array('onclick' => "showHideUpload();");
    $options = array(ts('Upload Content'), ts('Compose On-screen'));

    $this->addRadio('upload_type', ts('I want to'), $options, $attributes, "&nbsp;&nbsp;");

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->addElement('file', 'textFile', ts('Upload TEXT Message'), 'size=30 maxlength=60');
    $this->setMaxFileSize(1024 * 1024);
    $this->addRule('textFile', ts('File size should be less than 1 MByte'), 'maxfilesize', 1024 * 1024);
    $this->addRule('textFile', ts('File must be in UTF-8 encoding'), 'utf8File');

    $this->addFormRule(array('CRM_SMS_Form_Upload', 'formRule'), $this);

    $buttons = array(
      array(
        'type' => 'back',
        'name' => ts('Previous'),
      ),
      array(
        'type' => 'upload',
        'name' => ts('Next'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);
  }

  public function postProcess() {
    $params = $ids = array();
    $uploadParams = array('from_name');

    $formValues = $this->controller->exportValues($this->_name);

    foreach ($uploadParams as $key) {
      if (!empty($formValues[$key])) {
        $params[$key] = $formValues[$key];
        $this->set($key, $formValues[$key]);
      }
    }

    if (!$formValues['upload_type']) {
      $contents = NULL;
      if (isset($formValues['textFile']) &&
        !empty($formValues['textFile'])
      ) {
        $contents = file_get_contents($formValues['textFile']['name']);
        $this->set($key, $formValues['textFile']['name']);
      }
      if ($contents) {
        $params['body_text'] = $contents;
      }
      else {
        $params['body_text'] = 'NULL';
      }
    }
    else {
      $text_message = $formValues['sms_text_message'];
      $params['body_text'] = $text_message;
      $this->set('textFile', $params['body_text']);
      $this->set('text_message', $params['body_text']);
    }

    $params['name'] = $this->get('name');

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');
    $composeFields = array(
      'SMStemplate',
      'SMSsaveTemplate',
      'SMSupdateTemplate',
      'SMSsaveTemplateName',
    );
    $msgTemplate = NULL;
    // Mail template is composed.
    if ($formValues['upload_type']) {
      $composeParams = array();
      foreach ($composeFields as $key) {
        if (!empty($formValues[$key])) {
          $composeParams[$key] = $formValues[$key];
          $this->set($key, $formValues[$key]);
        }
      }

      if (!empty($composeParams['SMSupdateTemplate'])) {
        $templateParams = array(
          'msg_text' => $text_message,
          'is_active' => TRUE,
          'is_sms' => TRUE,
        );

        $templateParams['id'] = $formValues['SMStemplate'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if (!empty($composeParams['SMSsaveTemplate'])) {
        $templateParams = array(
          'msg_text' => $text_message,
          'is_active' => TRUE,
          'is_sms' => TRUE,
        );

        $templateParams['msg_title'] = $composeParams['SMSsaveTemplateName'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if (isset($msgTemplate->id)) {
        $params['msg_template_id'] = $msgTemplate->id;
      }
      else {
        $params['msg_template_id'] = CRM_Utils_Array::value('SMStemplate', $formValues);
      }
      $this->set('template', $params['msg_template_id']);
    }

    $ids['mailing_id'] = $this->_mailingID;

    // Build SMS in mailing table.
    CRM_Mailing_BAO_Mailing::create($params, $ids);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param $files
   * @param $self
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    if (!empty($_POST['_qf_Import_refresh'])) {
      return TRUE;
    }
    $errors = array();
    $template = CRM_Core_Smarty::singleton();

    $domain = CRM_Core_BAO_Domain::getDomain();

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $self->_mailingID;
    $mailing->find(TRUE);

    $session = CRM_Core_Session::singleton();
    $values = array(
      'contact_id' => $session->get('userID'),
      'version' => 3,
    );
    require_once 'api/api.php';
    $contact = civicrm_api('contact', 'get', $values);

    // CRM-4524.
    $contact = reset($contact['values']);

    $verp = array_flip(array('optOut', 'reply', 'unsubscribe', 'resubscribe', 'owner'));
    foreach ($verp as $key => $value) {
      $verp[$key]++;
    }

    $urls = array_flip(array('forward', 'optOutUrl', 'unsubscribeUrl', 'resubscribeUrl'));
    foreach ($urls as $key => $value) {
      $urls[$key]++;
    }

    $skipTextFile = $self->get('skipTextFile');

    if (!$params['upload_type']) {
      if ((!isset($files['textFile']) || !file_exists($files['textFile']['tmp_name']))) {
        if (!($skipTextFile)) {
          $errors['textFile'] = ts('Please provide a Text');
        }
      }
    }
    else {
      if (empty($params['sms_text_message'])) {
        $errors['sms_text_message'] = ts('Please provide a Text');
      }
      else {
        if (!empty($params['text_message'])) {
          $messageCheck = CRM_Utils_Array::value('text_message', $params);
          if ($messageCheck && (strlen($messageCheck) > CRM_SMS_Provider::MAX_SMS_CHAR)) {
            $errors['text_message'] = ts("You can configure the SMS message body up to %1 characters", array(1 => CRM_SMS_Provider::MAX_SMS_CHAR));
          }
        }
      }
      if (!empty($params['SMSsaveTemplate']) && empty($params['SMSsaveTemplateName'])) {
        $errors['SMSsaveTemplateName'] = ts('Please provide a Template Name.');
      }
    }

    if (($params['upload_type'] || file_exists(CRM_Utils_Array::value('tmp_name', $files['textFile']))) ||
      (!$params['upload_type'] && $params['text_message'])
    ) {

      if (!$params['upload_type']) {
        $str = file_get_contents($files['textFile']['tmp_name']);
        $name = $files['textFile']['name'];
      }
      else {
        $str = $params['sms_text_message'];
        $name = 'text message';
      }

      $dataErrors = array();

      // Do a full token replacement on a dummy verp, the current
      // contact and domain, and the first organization.

      // here we make a dummy mailing object so that we
      // can retrieve the tokens that we need to replace
      // so that we do get an invalid token error
      // this is qute hacky and I hope that there might
      // be a suggestion from someone on how to
      // make it a bit more elegant

      $dummy_mail = new CRM_Mailing_BAO_Mailing();
      $mess = "body_text";
      $dummy_mail->$mess = $str;
      $tokens = $dummy_mail->getTokens();

      $str = CRM_Utils_Token::replaceSubscribeInviteTokens($str);
      $str = CRM_Utils_Token::replaceDomainTokens($str, $domain, NULL, $tokens['text']);
      $str = CRM_Utils_Token::replaceMailingTokens($str, $mailing, NULL, $tokens['text']);
      $str = CRM_Utils_Token::replaceOrgTokens($str, $org);
      $str = CRM_Utils_Token::replaceActionTokens($str, $verp, $urls, NULL, $tokens['text']);
      $str = CRM_Utils_Token::replaceContactTokens($str, $contact, NULL, $tokens['text']);

      $unmatched = CRM_Utils_Token::unmatchedTokens($str);
      $contentCheck = CRM_Utils_String::htmlToText($str);

      if (!empty($unmatched) && 0) {
        foreach ($unmatched as $token) {
          $dataErrors[] = '<li>' . ts('Invalid token code') . ' {' . $token . '}</li>';
        }
      }
      if (strlen($contentCheck) > CRM_SMS_Provider::MAX_SMS_CHAR) {
        $dataErrors[] = '<li>' . ts('The body of the SMS cannot exceed %1 characters.', array(1 => CRM_SMS_Provider::MAX_SMS_CHAR)) . '</li>';
      }
      if (!empty($dataErrors)) {
        $errors['textFile'] = ts('The following errors were detected in %1:', array(
          1 => $name,
        )) . ' <ul>' . implode('', $dataErrors) . '</ul>';
      }
    }

    $templateName = CRM_Core_BAO_MessageTemplate::getMessageTemplates();
    if (!empty($params['SMSsaveTemplate']) && in_array(CRM_Utils_Array::value('SMSsaveTemplateName', $params), $templateName)
    ) {
      $errors['SMSsaveTemplate'] = ts('Duplicate Template Name.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Display Name of the form.
   *
   *
   * @return string
   */
  public function getTitle() {
    return ts('SMS Content');
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    return $tokens;
  }

}
