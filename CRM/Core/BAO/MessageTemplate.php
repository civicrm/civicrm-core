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

use Civi\Api4\MessageTemplate;

require_once 'Mail/mime.php';

/**
 * Class CRM_Core_BAO_MessageTemplate.
 */
class CRM_Core_BAO_MessageTemplate extends CRM_Core_DAO_MessageTemplate {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_MessageTemplate
   */
  public static function retrieve(&$params, &$defaults) {
    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->copyValues($params);
    if ($messageTemplates->find(TRUE)) {
      CRM_Core_DAO::storeValues($messageTemplates, $defaults);
      return $messageTemplates;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_MessageTemplate', $id, 'is_active', $is_active);
  }

  /**
   * Add the Message Templates.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function add(&$params) {
    // System Workflow Templates have a specific wodkflow_id in them but normal user end message templates don't
    // If we have an id check to see if we are update, and need to check if original is a system workflow or not.
    $systemWorkflowPermissionDeniedMessage = 'Editing or creating system workflow messages requires edit system workflow message templates permission or the edit message templates permission';
    $userWorkflowPermissionDeniedMessage = 'Editing or creating user driven workflow messages requires edit user-driven message templates or the edit message templates permission';
    if (!empty($params['check_permissions'])) {
      if (!CRM_Core_Permission::check('edit message templates')) {
        if (!empty($params['id'])) {
          $details = civicrm_api3('MessageTemplate', 'getSingle', ['id' => $params['id']]);
          if (!empty($details['workflow_id']) || !empty($details['workflow_name'])) {
            if (!CRM_Core_Permission::check('edit system workflow message templates')) {
              throw new \Civi\API\Exception\UnauthorizedException(ts('%1', [1 => $systemWorkflowPermissionDeniedMessage]));
            }
          }
          elseif (!CRM_Core_Permission::check('edit user-driven message templates')) {
            throw new \Civi\API\Exception\UnauthorizedException(ts('%1', [1 => $userWorkflowPermissionDeniedMessage]));
          }
        }
        else {
          if (!empty($params['workflow_id']) || !empty($params['workflow_name'])) {
            if (!CRM_Core_Permission::check('edit system workflow message templates')) {
              throw new \Civi\API\Exception\UnauthorizedException(ts('%1', [1 => $systemWorkflowPermissionDeniedMessage]));
            }
          }
          elseif (!CRM_Core_Permission::check('edit user-driven message templates')) {
            throw new \Civi\API\Exception\UnauthorizedException(ts('%1', [1 => $userWorkflowPermissionDeniedMessage]));
          }
        }
      }
    }
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MessageTemplate', CRM_Utils_Array::value('id', $params), $params);

    if (!empty($params['file_id']) && is_array($params['file_id']) && count($params['file_id'])) {
      $fileParams = $params['file_id'];
      unset($params['file_id']);
    }

    // The workflow_id and workflow_name should be sync'd. But what mix of inputs do we have to work with?
    switch ((empty($params['workflow_id']) ? '' : 'id') . (empty($params['workflow_name']) ? '' : 'name')) {
      case 'id':
        $params['workflow_name'] = array_search($params['workflow_id'], self::getWorkflowNameIdMap());
        break;

      case 'name':
        $params['workflow_id'] = self::getWorkflowNameIdMap()[$params['workflow_name']] ?? NULL;
        break;

      case 'idname':
        $map = self::getWorkflowNameIdMap();
        if ($map[$params['workflow_name']] != $params['workflow_id']) {
          throw new CRM_Core_Exception("The workflow_id and workflow_name are mismatched. Note: You only need to submit one or the other.");
        }
        break;

      case '':
        // OK, don't care.
        break;

      default:
        throw new \RuntimeException("Bad code");
    }

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->copyValues($params);
    $messageTemplates->save();

    if (!empty($fileParams)) {
      $params['file_id'] = $fileParams;
      CRM_Core_BAO_File::filePostProcess(
        $params['file_id']['location'],
        NULL,
        'civicrm_msg_template',
        $messageTemplates->id,
        NULL,
        TRUE,
        $params['file_id'],
        'file_id',
        $params['file_id']['type']
      );
    }

    CRM_Utils_Hook::post($hook, 'MessageTemplate', $messageTemplates->id, $messageTemplates);
    return $messageTemplates;
  }

  /**
   * Delete the Message Templates.
   *
   * @param int $messageTemplatesID
   *
   * @throws \CRM_Core_Exception
   */
  public static function del($messageTemplatesID) {
    // make sure messageTemplatesID is an integer
    if (!CRM_Utils_Rule::positiveInteger($messageTemplatesID)) {
      throw new CRM_Core_Exception(ts('Invalid Message template'));
    }

    // Set mailing msg template col to NULL
    $query = "UPDATE civicrm_mailing
                  SET msg_template_id = NULL
                  WHERE msg_template_id = %1";

    $params = [1 => [$messageTemplatesID, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $params);

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->id = $messageTemplatesID;
    $messageTemplates->delete();
    CRM_Core_Session::setStatus(ts('Selected message template has been deleted.'), ts('Deleted'), 'success');
  }

  /**
   * Get the Message Templates.
   *
   *
   * @param bool $all
   *
   * @param bool $isSMS
   *
   * @return array
   */
  public static function getMessageTemplates($all = TRUE, $isSMS = FALSE) {
    $msgTpls = [];

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
   * @param int $contactId
   * @param $email
   * @param int $messageTemplateID
   * @param $from
   *
   * @return bool|NULL
   * @throws \CRM_Core_Exception
   */
  public static function sendReminder($contactId, $email, $messageTemplateID, $from) {

    $messageTemplates = new CRM_Core_DAO_MessageTemplate();
    $messageTemplates->id = $messageTemplateID;

    $domain = CRM_Core_BAO_Domain::getDomain();
    $result = NULL;
    $hookTokens = [];

    if ($messageTemplates->find(TRUE)) {
      $body_text = $messageTemplates->msg_text;
      $body_html = $messageTemplates->msg_html;
      $body_subject = $messageTemplates->msg_subject;
      if (!$body_text) {
        $body_text = CRM_Utils_String::htmlToText($body_html);
      }

      $params = [['contact_id', '=', $contactId, 0, 0]];
      [$contact] = CRM_Contact_BAO_Query::apiQuery($params);

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
      $returnProperties = ["preferred_mail_format" => 1];
      if (isset($tokens['contact'])) {
        foreach ($tokens['contact'] as $key => $value) {
          $returnProperties[$value] = 1;
        }
      }
      list($details) = CRM_Utils_Token::getTokenDetails([$contactId],
        $returnProperties,
        NULL, NULL, FALSE,
        $tokens,
        'CRM_Core_BAO_MessageTemplate');
      $contact = reset($details);

      // call token hook
      $hookTokens = [];
      CRM_Utils_Hook::tokens($hookTokens);
      $categories = array_keys($hookTokens);

      // do replacements in text and html body
      $type = ['html', 'text'];
      foreach ($type as $key => $value) {
        $bodyType = "body_{$value}";
        if ($$bodyType) {
          CRM_Utils_Token::replaceGreetingTokens($$bodyType, NULL, $contact['contact_id']);
          $$bodyType = CRM_Utils_Token::replaceDomainTokens($$bodyType, $domain, TRUE, $tokens, TRUE);
          $$bodyType = CRM_Utils_Token::replaceContactTokens($$bodyType, $contact, FALSE, $tokens, FALSE, TRUE);
          $$bodyType = CRM_Utils_Token::replaceComponentTokens($$bodyType, $contact, $tokens, TRUE);
          $$bodyType = CRM_Utils_Token::replaceHookTokens($$bodyType, $contact, $categories, TRUE);
        }
      }
      $html = $body_html;
      $text = $body_text;

      $smarty = CRM_Core_Smarty::singleton();
      foreach ([
        'text',
        'html',
      ] as $elem) {
        $$elem = $smarty->fetch("string:{$$elem}");
      }

      // do replacements in message subject
      $messageSubject = CRM_Utils_Token::replaceContactTokens($body_subject, $contact, FALSE, $tokens);
      $messageSubject = CRM_Utils_Token::replaceDomainTokens($messageSubject, $domain, TRUE, $tokens);
      $messageSubject = CRM_Utils_Token::replaceComponentTokens($messageSubject, $contact, $tokens, TRUE);
      $messageSubject = CRM_Utils_Token::replaceHookTokens($messageSubject, $contact, $categories, TRUE);

      $messageSubject = $smarty->fetch("string:{$messageSubject}");

      // set up the parameters for CRM_Utils_Mail::send
      $mailParams = [
        'groupName' => 'Scheduled Reminder Sender',
        'from' => $from,
        'toName' => $contact['display_name'],
        'toEmail' => $email,
        'subject' => $messageSubject,
      ];
      if (!$html || $contact['preferred_mail_format'] == 'Text' ||
        $contact['preferred_mail_format'] == 'Both'
      ) {
        // render the &amp; entities in text mode, so that the links work
        $mailParams['text'] = str_replace('&amp;', '&', $text);
      }
      if ($html && ($contact['preferred_mail_format'] == 'HTML' ||
          $contact['preferred_mail_format'] == 'Both'
        )
      ) {
        $mailParams['html'] = $html;
      }

      $result = CRM_Utils_Mail::send($mailParams);
    }

    return $result;
  }

  /**
   * Revert a message template to its default subject+text+HTML state.
   *
   * @param int $id id of the template
   *
   * @throws \CRM_Core_Exception
   */
  public static function revert($id) {
    $diverted = new CRM_Core_BAO_MessageTemplate();
    $diverted->id = (int) $id;
    $diverted->find(1);

    if ($diverted->N != 1) {
      throw new CRM_Core_Exception(ts('Did not find a message template with id of %1.', [1 => $id]));
    }

    $orig = new CRM_Core_BAO_MessageTemplate();
    $orig->workflow_id = $diverted->workflow_id;
    $orig->is_reserved = 1;
    $orig->find(1);

    if ($orig->N != 1) {
      throw new CRM_Core_Exception(ts('Message template with id of %1 does not have a default to revert to.', [1 => $id]));
    }

    $diverted->msg_subject = $orig->msg_subject;
    $diverted->msg_text = $orig->msg_text;
    $diverted->msg_html = $orig->msg_html;
    $diverted->pdf_format_id = is_null($orig->pdf_format_id) ? 'null' : $orig->pdf_format_id;
    $diverted->save();
  }

  /**
   * Send an email from the specified template based on an array of params.
   *
   * @param array $params
   *   A string-keyed array of function params, see function body for details.
   *
   * @return array
   *   Array of four parameters: a boolean whether the email was sent, and the subject, text and HTML templates
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public static function sendTemplate($params) {
    $defaults = [
      // option value name of the template
      'valueName' => NULL,
      // ID of the template
      'messageTemplateID' => NULL,
      // contact id if contact tokens are to be replaced
      'contactId' => NULL,
      // additional template params (other than the ones already set in the template singleton)
      'tplParams' => [],
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
      // Disable Smarty?
      'disableSmarty' => FALSE,
    ];
    $params = array_merge($defaults, $params);

    // Core#644 - handle Email ID passed as "From".
    if (isset($params['from'])) {
      $params['from'] = CRM_Utils_Mail::formatFromAddress($params['from']);
    }

    CRM_Utils_Hook::alterMailParams($params, 'messageTemplate');

    if (!$params['valueName'] && !$params['messageTemplateID']) {
      throw new CRM_Core_Exception(ts("Message template's option value or ID missing."));
    }

    $apiCall = MessageTemplate::get(FALSE)
      ->addSelect('msg_subject', 'msg_text', 'msg_html', 'pdf_format_id', 'id')
      ->addWhere('is_default', '=', 1);

    if ($params['messageTemplateID']) {
      $apiCall->addWhere('id', '=', (int) $params['messageTemplateID']);
    }
    else {
      $apiCall->addWhere('workflow_name', '=', $params['valueName']);
    }
    $messageTemplate = $apiCall->execute()->first();

    if (empty($messageTemplate['id'])) {
      if ($params['messageTemplateID']) {
        throw new CRM_Core_Exception(ts('No such message template: id=%1.', [1 => $params['messageTemplateID']]));
      }
      throw new CRM_Core_Exception(ts('No message template with workflow name %2.', [2 => $params['valueName']]));
    }

    $mailContent = [
      'subject' => $messageTemplate['msg_subject'],
      'text' => $messageTemplate['msg_text'],
      'html' => $messageTemplate['msg_html'],
      'format' => $messageTemplate['pdf_format_id'],
      // Group name is a deprecated parameter. At some point it will not be passed out.
      // https://github.com/civicrm/civicrm-core/pull/17180
      'groupName' => $params['groupName'] ?? NULL,
      'valueName' => $params['valueName'],
      'messageTemplateID' => $params['messageTemplateID'],
    ];

    CRM_Utils_Hook::alterMailContent($mailContent);

    // add the test banner (if requested)
    if ($params['isTest']) {
      $query = "SELECT msg_subject subject, msg_text text, msg_html html
                      FROM civicrm_msg_template mt
                      WHERE workflow_name = 'test_preview' AND mt.is_default = 1";
      $testDao = CRM_Core_DAO::executeQuery($query);
      $testDao->fetch();

      $mailContent['subject'] = $testDao->subject . $mailContent['subject'];
      $mailContent['text'] = $testDao->text . $mailContent['text'];
      $mailContent['html'] = preg_replace('/<body(.*)$/im', "<body\\1\n{$testDao->html}", $mailContent['html']);
    }

    // Overwrite subject from form field
    if (!empty($params['subject'])) {
      $mailContent['subject'] = $params['subject'];
    }

    // replace tokens in the three elements (in subject as if it was the text body)
    $domain = CRM_Core_BAO_Domain::getDomain();
    $hookTokens = [];
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->subject = $mailContent['subject'];
    $mailing->body_text = $mailContent['text'];
    $mailing->body_html = $mailContent['html'];
    $tokens = $mailing->getTokens();
    CRM_Utils_Hook::tokens($hookTokens);
    $categories = array_keys($hookTokens);

    $contactID = $params['contactId'] ?? NULL;

    if ($contactID) {
      $contactParams = ['contact_id' => $contactID];
      $returnProperties = [];

      if (isset($tokens['subject']['contact'])) {
        foreach ($tokens['subject']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }

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

      // @todo CRM-17253 don't resolve contact details if there are no tokens
      // effectively comment out this next (performance-expensive) line
      // but unfortunately testing is a bit think on the ground to that needs to
      // be added.
      list($contact) = CRM_Utils_Token::getTokenDetails($contactParams,
        $returnProperties,
        FALSE, FALSE, NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        // we should consider adding valueName here
        'CRM_Core_BAO_MessageTemplate'
      );
      $contact = $contact[$contactID];
    }

    // When using Smarty we need to pass the $escapeSmarty parameter.
    $escapeSmarty = !$params['disableSmarty'];

    $mailContent['subject'] = CRM_Utils_Token::replaceDomainTokens($mailContent['subject'], $domain, FALSE, $tokens['subject'], $escapeSmarty);
    $mailContent['text'] = CRM_Utils_Token::replaceDomainTokens($mailContent['text'], $domain, FALSE, $tokens['text'], $escapeSmarty);
    $mailContent['html'] = CRM_Utils_Token::replaceDomainTokens($mailContent['html'], $domain, TRUE, $tokens['html'], $escapeSmarty);

    if ($contactID) {
      $mailContent['subject'] = CRM_Utils_Token::replaceContactTokens($mailContent['subject'], $contact, FALSE, $tokens['subject'], FALSE, $escapeSmarty);
      $mailContent['text'] = CRM_Utils_Token::replaceContactTokens($mailContent['text'], $contact, FALSE, $tokens['text'], FALSE, $escapeSmarty);
      $mailContent['html'] = CRM_Utils_Token::replaceContactTokens($mailContent['html'], $contact, FALSE, $tokens['html'], FALSE, $escapeSmarty);

      $contactArray = [$contactID => $contact];
      CRM_Utils_Hook::tokenValues($contactArray,
        [$contactID],
        NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        // we should consider adding valueName here
        'CRM_Core_BAO_MessageTemplate'
      );
      $contact = $contactArray[$contactID];

      $mailContent['subject'] = CRM_Utils_Token::replaceHookTokens($mailContent['subject'], $contact, $categories, TRUE);
      $mailContent['text'] = CRM_Utils_Token::replaceHookTokens($mailContent['text'], $contact, $categories, TRUE);
      $mailContent['html'] = CRM_Utils_Token::replaceHookTokens($mailContent['html'], $contact, $categories, TRUE);
    }

    // Normally Smarty is run, but it can be disabled using the disableSmarty
    // parameter, which may be useful for non-core uses of MessageTemplate.send
    // In particular it helps with the mosaicomsgtpl extension.
    if (!$params['disableSmarty']) {
      // strip whitespace from ends and turn into a single line
      $mailContent['subject'] = "{strip}{$mailContent['subject']}{/strip}";

      // parse the three elements with Smarty
      $smarty = CRM_Core_Smarty::singleton();
      foreach ($params['tplParams'] as $name => $value) {
        $smarty->assign($name, $value);
      }
      foreach ([
        'subject',
        'text',
        'html',
      ] as $elem) {
        $mailContent[$elem] = $smarty->fetch("string:{$mailContent[$elem]}");
      }
    }
    else {
      // Since we're not relying on Smarty for this function, we DIY.
      // strip whitespace from ends and turn into a single line
      $mailContent['subject'] = trim(preg_replace('/[\r\n]+/', ' ', $mailContent['subject']));
    }

    // send the template, honouring the target user’s preferences (if any)
    $sent = FALSE;

    // create the params array
    $params['subject'] = $mailContent['subject'];
    $params['text'] = $mailContent['text'];
    $params['html'] = $mailContent['html'];

    if ($params['toEmail']) {
      $contactParams = [['email', 'LIKE', $params['toEmail'], 0, 1]];
      [$contact] = CRM_Contact_BAO_Query::apiQuery($contactParams);

      $prefs = array_pop($contact);

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] === 'HTML') {
        $params['text'] = NULL;
      }

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] === 'Text') {
        $params['html'] = NULL;
      }

      $config = CRM_Core_Config::singleton();
      if (isset($params['isEmailPdf']) && $params['isEmailPdf'] == 1) {
        $pdfHtml = CRM_Contribute_BAO_ContributionPage::addInvoicePdfToEmail($params['contributionId'], $params['contactId']);
        if (empty($params['attachments'])) {
          $params['attachments'] = [];
        }
        $params['attachments'][] = CRM_Utils_Mail::appendPDF('Invoice.pdf', $pdfHtml, $mailContent['format']);
      }
      $pdf_filename = '';
      if ($config->doNotAttachPDFReceipt &&
        $params['PDFFilename'] &&
        $params['html']
      ) {
        if (empty($params['attachments'])) {
          $params['attachments'] = [];
        }
        $params['attachments'][] = CRM_Utils_Mail::appendPDF($params['PDFFilename'], $params['html'], $mailContent['format']);
        if (isset($params['tplParams']['email_comment'])) {
          $params['html'] = $params['tplParams']['email_comment'];
          $params['text'] = strip_tags($params['tplParams']['email_comment']);
        }
      }

      $sent = CRM_Utils_Mail::send($params);

      if ($pdf_filename) {
        unlink($pdf_filename);
      }
    }

    return [$sent, $mailContent['subject'], $mailContent['text'], $mailContent['html']];
  }

  /**
   * Create a map between workflow_name and workflow_id.
   *
   * @return array
   *   Array(string $workflowName => int $workflowId)
   */
  protected static function getWorkflowNameIdMap() {
    // There's probably some more clever way to do this, but this seems simple.
    return CRM_Core_DAO::executeQuery('SELECT cov.name as name, cov.id as id FROM civicrm_option_group cog INNER JOIN civicrm_option_value cov on cov.option_group_id=cog.id WHERE cog.name LIKE %1', [
      1 => ['msg_tpl_workflow_%', 'String'],
    ])->fetchMap('name', 'id');
  }

}
