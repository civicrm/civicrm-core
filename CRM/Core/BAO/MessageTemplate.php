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

require_once 'Mail/mime.php';

/**
 * Class CRM_Core_BAO_MessageTemplate
 */
class CRM_Core_BAO_MessageTemplate extends CRM_Core_DAO_MessageTemplate {

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_MessageTemplate object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->copyValues($params);
    if ($messageTemplates->find(TRUE)) {
      CRM_Core_DAO::storeValues($messageTemplates, $defaults);
      return $messageTemplates;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_MessageTemplate', $id, 'is_active', $is_active);
  }

  /**
   * function to add the Message Templates
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MessageTemplate', CRM_Utils_Array::value('id', $params), $params);

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->copyValues($params);
    $messageTemplates->save();

    CRM_Utils_Hook::post($hook, 'MessageTemplate', $messageTemplates->id, $messageTemplates);
    return $messageTemplates;
  }

  /**
   * function to delete the Message Templates
   *
   * @access public
   * @static
   *
   * @param $messageTemplatesID
   *
   * @return object
   */
  static function del($messageTemplatesID) {
    // make sure messageTemplatesID is an integer
    if (!CRM_Utils_Rule::positiveInteger($messageTemplatesID)) {
      CRM_Core_Error::fatal(ts('Invalid Message template'));
    }

    // Set mailing msg template col to NULL
    $query = "UPDATE civicrm_mailing
                  SET msg_template_id = NULL
                  WHERE msg_template_id = %1";

    $params = array(1 => array($messageTemplatesID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->id = $messageTemplatesID;
    $messageTemplates->delete();
    CRM_Core_Session::setStatus(ts('Selected message template has been deleted.'), ts('Deleted'), 'success');
  }

  /**
   * function to get the Message Templates
   *
   * @access public
   * @static
   *
   * @param bool $all
   *
   * @return object
   */
  static function getMessageTemplates($all = TRUE, $isSMS = FALSE) {
    $msgTpls = array();

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->is_active = 1;
    $messageTemplates->is_sms = $isSMS;

    if (!$all) {
      $messageTemplates->workflow_id = 'NULL';
    }
    $messageTemplates->find();
    while ($messageTemplates->fetch()) {
      $msgTpls[$messageTemplates->id] = $messageTemplates->msg_title;
    }
    asort($msgTpls);
    return $msgTpls;
  }

  /**
   * @param $contactId
   * @param $email
   * @param $messageTemplateID
   * @param $from
   *
   * @return bool|null
   */
  static function sendReminder($contactId, $email, $messageTemplateID, $from) {

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->id = $messageTemplateID;

    $domain     = CRM_Core_BAO_Domain::getDomain();
    $result     = NULL;
    $hookTokens = array();

    if ($messageTemplates->find(TRUE)) {
      $body_text    = $messageTemplates->msg_text;
      $body_html    = $messageTemplates->msg_html;
      $body_subject = $messageTemplates->msg_subject;
      if (!$body_text) {
        $body_text = CRM_Utils_String::htmlToText($body_html);
      }

      $params = array(array('contact_id', '=', $contactId, 0, 0));
      list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($params);

      //CRM-4524
      $contact = reset($contact);

      if (!$contact || is_a($contact, 'CRM_Core_Error')) {
        return NULL;
      }

      //CRM-5734

      // get tokens to be replaced
      $tokens = array_merge(CRM_Utils_Token::getTokens($body_text),
                            CRM_Utils_Token::getTokens($body_html),
                            CRM_Utils_Token::getTokens($body_subject));

      // get replacement text for these tokens
      $returnProperties = array("preferred_mail_format" => 1);
      if (isset($tokens['contact'])) {
        foreach ($tokens['contact'] as $key => $value) {
          $returnProperties[$value] = 1;
        }
      }
      list($details) = CRM_Utils_Token::getTokenDetails(array($contactId),
                                                        $returnProperties,
                                                        null, null, false,
                                                        $tokens,
                                                        'CRM_Core_BAO_MessageTemplate');
      $contact = reset( $details );

      // call token hook
      $hookTokens = array();
      CRM_Utils_Hook::tokens($hookTokens);
      $categories = array_keys($hookTokens);

      // do replacements in text and html body
      $type = array('html', 'text');
      foreach ($type as $key => $value) {
        $bodyType = "body_{$value}";
        if ($$bodyType) {
          CRM_Utils_Token::replaceGreetingTokens($$bodyType, NULL, $contact['contact_id']);
          $$bodyType = CRM_Utils_Token::replaceDomainTokens($$bodyType, $domain, true, $tokens, true);
          $$bodyType = CRM_Utils_Token::replaceContactTokens($$bodyType, $contact, false, $tokens, false, true);
          $$bodyType = CRM_Utils_Token::replaceComponentTokens($$bodyType, $contact, $tokens, true);
          $$bodyType = CRM_Utils_Token::replaceHookTokens($$bodyType, $contact , $categories, true);
        }
      }
      $html = $body_html;
      $text = $body_text;

      $smarty = CRM_Core_Smarty::singleton();
      foreach (array(
        'text', 'html') as $elem) {
        $$elem = $smarty->fetch("string:{$$elem}");
      }

      // do replacements in message subject
      $messageSubject = CRM_Utils_Token::replaceContactTokens($body_subject, $contact, false, $tokens);
      $messageSubject = CRM_Utils_Token::replaceDomainTokens($messageSubject, $domain, true, $tokens);
      $messageSubject = CRM_Utils_Token::replaceComponentTokens($messageSubject, $contact, $tokens, true);
      $messageSubject = CRM_Utils_Token::replaceHookTokens($messageSubject, $contact, $categories, true);

      $messageSubject = $smarty->fetch("string:{$messageSubject}");

      // set up the parameters for CRM_Utils_Mail::send
      $mailParams = array(
        'groupName' => 'Scheduled Reminder Sender',
        'from' => $from,
        'toName' => $contact['display_name'],
        'toEmail' => $email,
        'subject' => $messageSubject,
      );
      if (!$html || $contact['preferred_mail_format'] == 'Text' ||
        $contact['preferred_mail_format'] == 'Both'
      ) {
        // render the &amp; entities in text mode, so that the links work
        $mailParams['text'] = str_replace('&amp;', '&', $text);
      }
      if ($html && ($contact['preferred_mail_format'] == 'HTML' ||
          $contact['preferred_mail_format'] == 'Both'
        )) {
        $mailParams['html'] = $html;
      }

      $result = CRM_Utils_Mail::send($mailParams);
    }

    $messageTemplates->free();

    return $result;
  }

  /**
   * Revert a message template to its default subject+text+HTML state
   *
   * @param integer id  id of the template
   *
   * @return void
   */
  static function revert($id) {
    $diverted = new self;
    $diverted->id = (int) $id;
    $diverted->find(1);

    if ($diverted->N != 1) {
      CRM_Core_Error::fatal(ts('Did not find a message template with id of %1.', array(1 => $id)));
    }

    $orig              = new self;
    $orig->workflow_id = $diverted->workflow_id;
    $orig->is_reserved = 1;
    $orig->find(1);

    if ($orig->N != 1) {
      CRM_Core_Error::fatal(ts('Message template with id of %1 does not have a default to revert to.', array(1 => $id)));
    }

    $diverted->msg_subject = $orig->msg_subject;
    $diverted->msg_text = $orig->msg_text;
    $diverted->msg_html = $orig->msg_html;
    $diverted->pdf_format_id = is_null($orig->pdf_format_id) ? 'null' : $orig->pdf_format_id;
    $diverted->save();
  }

  /**
   * Send an email from the specified template based on an array of params
   *
   * @param array $params  a string-keyed array of function params, see function body for details
   *
   * @return array  of four parameters: a boolean whether the email was sent, and the subject, text and HTML templates
   */
  static function sendTemplate($params) {
    $defaults = array(
      // option group name of the template
      'groupName' => NULL,
      // option value name of the template
      'valueName' => NULL,
      // ID of the template
      'messageTemplateID' => NULL,
      // contact id if contact tokens are to be replaced
      'contactId' => NULL,
      // additional template params (other than the ones already set in the template singleton)
      'tplParams' => array(),
      // the From: header
      'from' => NULL,
      // the recipient’s name
      'toName' => NULL,
      // the recipient’s email - mail is sent only if set
      'toEmail' => NULL,
      // the Cc: header
      'cc' => NULL,
      // the Bcc: header
      'bcc' => NULL,
      // the Reply-To: header
      'replyTo' => NULL,
      // email attachments
      'attachments' => NULL,
      // whether this is a test email (and hence should include the test banner)
      'isTest' => FALSE,
      // filename of optional PDF version to add as attachment (do not include path)
      'PDFFilename' => NULL,
    );
    $params = array_merge($defaults, $params);

    if ((!$params['groupName'] ||
        !$params['valueName']
      ) &&
      !$params['messageTemplateID']
    ) {
      CRM_Core_Error::fatal(ts("Message template's option group and/or option value or ID missing."));
    }

    if ($params['messageTemplateID']) {
      // fetch the three elements from the db based on id
      $query = 'SELECT msg_subject subject, msg_text text, msg_html html, pdf_format_id format
                      FROM civicrm_msg_template mt
                      WHERE mt.id = %1 AND mt.is_default = 1';
      $sqlParams = array(1 => array($params['messageTemplateID'], 'String'));
    }
    else {
      // fetch the three elements from the db based on option_group and option_value names
      $query = 'SELECT msg_subject subject, msg_text text, msg_html html, pdf_format_id format
                      FROM civicrm_msg_template mt
                      JOIN civicrm_option_value ov ON workflow_id = ov.id
                      JOIN civicrm_option_group og ON ov.option_group_id = og.id
                      WHERE og.name = %1 AND ov.name = %2 AND mt.is_default = 1';
      $sqlParams = array(1 => array($params['groupName'], 'String'), 2 => array($params['valueName'], 'String'));
    }
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    $dao->fetch();

    if (!$dao->N) {
      if ($params['messageTemplateID']) {
        CRM_Core_Error::fatal(ts('No such message template: id=%1.', array(1 => $params['messageTemplateID'])));
      }
      else {
        CRM_Core_Error::fatal(ts('No such message template: option group %1, option value %2.', array(1 => $params['groupName'], 2 => $params['valueName'])));
      }
    }

    $subject = $dao->subject;
    $text    = $dao->text;
    $html    = $dao->html;
    $format  = $dao->format;
    $dao->free();

    // add the test banner (if requested)
    if ($params['isTest']) {
      $query = "SELECT msg_subject subject, msg_text text, msg_html html
                      FROM civicrm_msg_template mt
                      JOIN civicrm_option_value ov ON workflow_id = ov.id
                      JOIN civicrm_option_group og ON ov.option_group_id = og.id
                      WHERE og.name = 'msg_tpl_workflow_meta' AND ov.name = 'test_preview' AND mt.is_default = 1";
      $testDao = CRM_Core_DAO::executeQuery($query);
      $testDao->fetch();

      $subject = $testDao->subject . $subject;
      $text    = $testDao->text . $text;
      $html    = preg_replace('/<body(.*)$/im', "<body\\1\n{$testDao->html}", $html);
      $testDao->free();
    }

    // replace tokens in the three elements (in subject as if it was the text body)
    $domain             = CRM_Core_BAO_Domain::getDomain();
    $hookTokens         = array();
    $mailing            = new CRM_Mailing_BAO_Mailing;
    $mailing->body_text = $text;
    $mailing->body_html = $html;
    $tokens             = $mailing->getTokens();
    CRM_Utils_Hook::tokens($hookTokens);
    $categories = array_keys($hookTokens);

    $contactID = CRM_Utils_Array::value('contactId', $params);

    if ($contactID) {
      $contactParams = array('contact_id' => $contactID);
      $returnProperties = array();

      if (isset($tokens['text']['contact'])) {
        foreach ($tokens['text']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }

      if (isset($tokens['html']['contact'])) {
        foreach ($tokens['html']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }
      list($contact) = CRM_Utils_Token::getTokenDetails($contactParams,
        $returnProperties,
        FALSE, FALSE, NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        // we should consider adding groupName and valueName here
        'CRM_Core_BAO_MessageTemplate'
      );
      $contact = $contact[$contactID];
    }

    $subject = CRM_Utils_Token::replaceDomainTokens($subject, $domain, FALSE, $tokens['text'], TRUE);
    $text    = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text'], TRUE);
    $html    = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html'], TRUE);

    if ($contactID) {
      $subject = CRM_Utils_Token::replaceContactTokens($subject, $contact, FALSE, $tokens['text'], FALSE, TRUE);
      $text    = CRM_Utils_Token::replaceContactTokens($text, $contact, FALSE, $tokens['text'], FALSE, TRUE);
      $html    = CRM_Utils_Token::replaceContactTokens($html, $contact, FALSE, $tokens['html'], FALSE, TRUE);


      $contactArray = array($contactID => $contact);
      CRM_Utils_Hook::tokenValues($contactArray,
        array($contactID),
        NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        // we should consider adding groupName and valueName here
        'CRM_Core_BAO_MessageTemplate'
      );
      $contact = $contactArray[$contactID];

      $subject = CRM_Utils_Token::replaceHookTokens($subject, $contact, $categories, TRUE);
      $text    = CRM_Utils_Token::replaceHookTokens($text, $contact, $categories, TRUE);
      $html    = CRM_Utils_Token::replaceHookTokens($html, $contact, $categories, TRUE);
    }

    // strip whitespace from ends and turn into a single line
    $subject = "{strip}$subject{/strip}";

    // parse the three elements with Smarty


    $smarty = CRM_Core_Smarty::singleton();
    foreach ($params['tplParams'] as $name => $value) {
      $smarty->assign($name, $value);
    }
    foreach (array(
      'subject', 'text', 'html') as $elem) {
      $$elem = $smarty->fetch("string:{$$elem}");
    }

    // send the template, honouring the target user’s preferences (if any)
    $sent = FALSE;

    // create the params array
    $params['subject'] = $subject;
    $params['text']    = $text;
    $params['html']    = $html;

    if ($params['toEmail']) {
      $contactParams = array(array('email', 'LIKE', $params['toEmail'], 0, 1));
      list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($contactParams);

      $prefs = array_pop($contact);

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] == 'HTML') {
        $params['text'] = NULL;
      }

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] == 'Text') {
        $params['html'] = NULL;
      }

      $config = CRM_Core_Config::singleton();
      $pdf_filename = '';
      if ($config->doNotAttachPDFReceipt &&
        $params['PDFFilename'] &&
        $params['html']
      ) {
        if (empty($params['attachments'])) {
          $params['attachments'] = array();
        }
        $params['attachments'][] = CRM_Utils_Mail::appendPDF($params['PDFFilename'], $params['html'], $format);
      }

      $sent = CRM_Utils_Mail::send($params);

      if ($pdf_filename) {
        unlink($pdf_filename);
      }
    }

    return array($sent, $subject, $text, $html);
  }
}
