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

use Civi\Api4\Email;
use Civi\Api4\MessageTemplate;
use Civi\WorkflowMessage\WorkflowMessage;

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
    $empty = function ($key) use (&$params) {
      return empty($params[$key]) || $params[$key] === 'null';
    };
    switch (($empty('workflow_id') ? '' : 'id') . ($empty('workflow_name') ? '' : 'name')) {
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
    $orig->workflow_name = $diverted->workflow_name;
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
   * Render a message template.
   *
   * This method is very similar to `sendTemplate()` - accepting most of the same arguments
   * and emitting similar hooks. However, it specifically precludes the possibility of
   * sending a message. It only renders.
   *
   * @param $params
   *  Mixed render parameters. See sendTemplate() for more details.
   * @return array
   *   Rendered message, consistent of 'subject', 'text', 'html'
   *   Ex: ['subject' => 'Hello Bob', 'text' => 'It\'s been so long since we sent you an automated notification!']
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @see sendTemplate()
   */
  public static function renderTemplate($params) {
    [$mailContent, $params] = self::renderTemplateRaw($params);
    return CRM_Utils_Array::subset($mailContent, ['subject', 'text', 'html']);
  }

  /**
   * Render a message template.
   *
   * @param array $params
   *   Mixed render parameters. See sendTemplate() for more details.
   * @return array
   *   Tuple of [$mailContent, $updatedParams].
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @see sendTemplate()
   */
  protected static function renderTemplateRaw($params) {
    $modelDefaults = [
      // instance of WorkflowMessageInterface, containing a list of data to provide to the message-template
      'model' => NULL,
      // Symbolic name of the workflow step. Matches the option-value-name of the template.
      'valueName' => NULL,
      // additional template params (other than the ones already set in the template singleton)
      'tplParams' => [],
      // additional token params (passed to the TokenProcessor)
      // INTERNAL: 'tokenContext' is currently only intended for use within civicrm-core only. For downstream usage, future updates will provide comparable public APIs.
      'tokenContext' => [],
      // properties to import directly to the model object
      'modelProps' => NULL,
      // contact id if contact tokens are to be replaced; alias for tokenContext.contactId
      'contactId' => NULL,
    ];
    $viewDefaults = [
      // ID of the specific template to load
      'messageTemplateID' => NULL,
      // content of the message template
      // Ex: ['msg_subject' => 'Hello {contact.display_name}', 'msg_html' => '...', 'msg_text' => '...']
      // INTERNAL: 'messageTemplate' is currently only intended for use within civicrm-core only. For downstream usage, future updates will provide comparable public APIs.
      'messageTemplate' => NULL,
      // whether this is a test email (and hence should include the test banner)
      'isTest' => FALSE,
      // Disable Smarty?
      'disableSmarty' => FALSE,
    ];
    $envelopeDefaults = [
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
      // filename of optional PDF version to add as attachment (do not include path)
      'PDFFilename' => NULL,
    ];

    // Some params have been deprecated/renamed. Synchronize old<=>new params. We periodically resync after exchanging data with other parties.
    $sync = function () use (&$params, $modelDefaults, $viewDefaults) {
      CRM_Utils_Array::pathSync($params, ['workflow'], ['valueName']);
      CRM_Utils_Array::pathSync($params, ['tokenContext', 'contactId'], ['contactId']);
      CRM_Utils_Array::pathSync($params, ['tokenContext', 'smarty'], ['disableSmarty'], function ($v, bool $isCanon) {
        return !$v;
      });

      // Core#644 - handle Email ID passed as "From".
      if (isset($params['from'])) {
        $params['from'] = \CRM_Utils_Mail::formatFromAddress($params['from']);
      }
    };
    $sync();

    // Allow WorkflowMessage to run any filters/mappings/cleanups.
    $model = $params['model'] ?? WorkflowMessage::create($params['workflow'] ?? 'UNKNOWN');
    $params = WorkflowMessage::exportAll(WorkflowMessage::importAll($model, $params));
    unset($params['model']);
    // Subsequent hooks use $params. Retaining the $params['model'] might be nice - but don't do it unless you figure out how to ensure data-consistency (eg $params['tplParams'] <=> $params['model']).
    // If you want to expose the model via hook, consider interjecting a new Hook::alterWorkflowMessage($model) between `importAll()` and `exportAll()`.

    $sync();
    $params = array_merge($modelDefaults, $viewDefaults, $envelopeDefaults, $params);

    CRM_Utils_Hook::alterMailParams($params, 'messageTemplate');
    $mailContent = self::loadTemplate((string) $params['valueName'], $params['isTest'], $params['messageTemplateID'] ?? NULL, $params['groupName'] ?? '', $params['messageTemplate'], $params['subject'] ?? NULL);

    $sync();
    $rendered = CRM_Core_TokenSmarty::render(CRM_Utils_Array::subset($mailContent, ['text', 'html', 'subject']), $params['tokenContext'], $params['tplParams']);
    if (isset($rendered['subject'])) {
      $rendered['subject'] = trim(preg_replace('/[\r\n]+/', ' ', $rendered['subject']));
    }
    $nullSet = ['subject' => NULL, 'text' => NULL, 'html' => NULL];
    $mailContent = array_merge($nullSet, $mailContent, $rendered);
    return [$mailContent, $params];
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
    [$mailContent, $params] = self::renderTemplateRaw($params);

    // create the params array
    $params['subject'] = $mailContent['subject'];
    $params['text'] = $mailContent['text'];
    $params['html'] = $mailContent['html'];

    // send the template, honouring the target user’s preferences (if any)
    $sent = FALSE;
    if (!empty($params['toEmail'])) {
      // @todo - consider whether we really should be loading
      // this based on 'the first email in the db that matches'.
      // when we likely have the contact id. OTOH people probably barely
      // use preferredMailFormat these days - the good fight against html
      // emails was lost a decade ago...
      $preferredMailFormatArray = Email::get(FALSE)->addWhere('email', '=', $params['toEmail'])->addSelect('contact_id.preferred_mail_format')->execute()->first();
      $preferredMailFormat = $preferredMailFormatArray['contact_id.preferred_mail_format'] ?? 'Both';

      if ($preferredMailFormat === 'HTML') {
        $params['text'] = NULL;
      }
      if ($preferredMailFormat === 'Text') {
        $params['html'] = NULL;
      }

      $config = CRM_Core_Config::singleton();
      if (isset($params['isEmailPdf']) && $params['isEmailPdf'] == 1) {
        // FIXME: $params['contributionId'] is not modeled in the parameter list. When is it supplied? Should probably move to tokenContext.contributionId.
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

  /**
   * Load the specified template.
   *
   * @param string $workflowName
   * @param bool $isTest
   * @param int|null $messageTemplateID
   * @param string $groupName
   * @param array|null $messageTemplateOverride
   *   Optionally, record with msg_subject, msg_text, msg_html.
   *   If omitted, the record will be loaded from workflowName/messageTemplateID.
   * @param string|null $subjectOverride
   *   This option is the older, wonkier version of $messageTemplate['msg_subject']...
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected static function loadTemplate(string $workflowName, bool $isTest, int $messageTemplateID = NULL, $groupName = NULL, ?array $messageTemplateOverride = NULL, ?string $subjectOverride = NULL): array {
    $base = ['msg_subject' => NULL, 'msg_text' => NULL, 'msg_html' => NULL, 'pdf_format_id' => NULL];
    if (!$workflowName && !$messageTemplateID) {
      throw new CRM_Core_Exception(ts("Message template's option value or ID missing."));
    }

    $apiCall = MessageTemplate::get(FALSE)
      ->addSelect('msg_subject', 'msg_text', 'msg_html', 'pdf_format_id', 'id')
      ->addWhere('is_default', '=', 1);

    if ($messageTemplateID) {
      $apiCall->addWhere('id', '=', (int) $messageTemplateID);
    }
    else {
      $apiCall->addWhere('workflow_name', '=', $workflowName);
    }
    $messageTemplate = array_merge($base, $apiCall->execute()->first() ?: [], $messageTemplateOverride ?: []);
    if (empty($messageTemplate['id']) && empty($messageTemplateOverride)) {
      if ($messageTemplateID) {
        throw new CRM_Core_Exception(ts('No such message template: id=%1.', [1 => $messageTemplateID]));
      }
      throw new CRM_Core_Exception(ts('No message template with workflow name %1.', [1 => $workflowName]));
    }

    $mailContent = [
      'subject' => $messageTemplate['msg_subject'],
      'text' => $messageTemplate['msg_text'],
      'html' => $messageTemplate['msg_html'],
      'format' => $messageTemplate['pdf_format_id'],
      // Workflow name is the field in the message templates table that denotes the
      // workflow the template is used for. This is intended to eventually
      // replace the non-standard option value/group implementation - see
      // https://github.com/civicrm/civicrm-core/pull/17227 and the longer
      // discussion on https://github.com/civicrm/civicrm-core/pull/17180
      'workflow_name' => $workflowName,
      // Note messageTemplateID is the id but when present we also know it was specifically requested.
      'messageTemplateID' => $messageTemplateID,
      // Group name & valueName are deprecated parameters. At some point it will not be passed out.
      // https://github.com/civicrm/civicrm-core/pull/17180
      'groupName' => $groupName,
      'valueName' => $workflowName,
    ];

    CRM_Utils_Hook::alterMailContent($mailContent);

    // add the test banner (if requested)
    if ($isTest) {
      $testText = MessageTemplate::get(FALSE)
        ->setSelect(['msg_subject', 'msg_text', 'msg_html'])
        ->addWhere('workflow_name', '=', 'test_preview')
        ->addWhere('is_default', '=', TRUE)
        ->execute()->first();

      $mailContent['subject'] = $testText['msg_subject'] . $mailContent['subject'];
      $mailContent['text'] = $testText['msg_text'] . $mailContent['text'];
      $mailContent['html'] = preg_replace('/<body(.*)$/im', "<body\\1\n{$testText['msg_html']}", $mailContent['html']);
    }

    if (!empty($subjectOverride)) {
      CRM_Core_Error::deprecatedWarning('CRM_Core_BAO_MessageTemplate: $params[subject] is deprecated. Use $params[messageTemplate][msg_subject] instead.');
      $mailContent['subject'] = $subjectOverride;
    }

    return $mailContent;
  }

}
