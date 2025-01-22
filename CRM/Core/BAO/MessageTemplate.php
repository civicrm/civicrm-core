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
use Civi\WorkflowMessage\WorkflowMessage;

require_once 'Mail/mime.php';

/**
 * Class CRM_Core_BAO_MessageTemplate.
 */
class CRM_Core_BAO_MessageTemplate extends CRM_Core_DAO_MessageTemplate implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
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
   * @throws \CRM_Core_Exception
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
    CRM_Utils_Hook::pre($hook, 'MessageTemplate', $params['id'] ?? NULL, $params);

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
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  public static function del($messageTemplatesID) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $messageTemplatesID]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      // Set mailing msg template col to NULL
      $query = "UPDATE civicrm_mailing
                    SET msg_template_id = NULL
                    WHERE msg_template_id = %1";
      $params = [1 => [$event->id, 'Integer']];
      CRM_Core_DAO::executeQuery($query, $params);
    }
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

    $messageTemplates = MessageTemplate::get(FALSE)
      ->addSelect('id', 'msg_title')
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('is_sms', '=', $isSMS);

    if (!$all) {
      $messageTemplates->addWhere('workflow_name', 'IS NULL');
    }

    $msgTpls = array_column((array) $messageTemplates->execute(), 'msg_title', 'id');

    asort($msgTpls);
    return $msgTpls;
  }

  /**
   * Get the appropriate pdf format for the given template.
   *
   * @param string $workflow
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getPDFFormatForTemplate(string $workflow): array {
    $pdfFormatID = MessageTemplate::get(FALSE)
      ->addWhere('workflow_name', '=', $workflow)
      ->addSelect('pdf_format_id')
      ->execute()->first()['pdf_format_id'] ?? 0;
    // Get by ID will fall back to retrieving the default values if
    // it does not find the appropriate ones - hence passing in 0 works.
    return CRM_Core_BAO_PdfFormat::getById($pdfFormatID);
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

    // Use write record to trigger hook invocations.
    self::writeRecord([
      'msg_subject' => $orig->msg_subject,
      'msg_text' => $orig->msg_text,
      'msg_html' => $orig->msg_html,
      'pdf_format_id' => is_null($orig->pdf_format_id) ? 'null' : $orig->pdf_format_id,
      'id' => $id,
    ]);
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
   * @throws \CRM_Core_Exception
   * @see sendTemplate()
   */
  protected static function renderTemplateRaw($params) {
    $modelDefaults = [
      // instance of WorkflowMessageInterface, containing a list of data to provide to the message-template
      'model' => NULL,

      // Symbolic name of the workflow step. Matches the value in civicrm_msg_template.workflow_name.
      // This field is allowed as an input. However, the default mechanics go through the 'model'.
      // 'workflow' => NULL,

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

    self::synchronizeLegacyParameters($params);
    $params = array_merge($modelDefaults, $viewDefaults, $envelopeDefaults, $params);

    self::synchronizeLegacyParameters($params);
    // Allow WorkflowMessage to run any filters/mappings/cleanups.
    /** @var \Civi\WorkflowMessage\GenericWorkflowMessage $model */
    $model = $params['model'] ?? WorkflowMessage::create($params['workflow'] ?? 'UNKNOWN');
    WorkflowMessage::importAll($model, $params);
    $mailContent = $model->resolveContent();
    $params = WorkflowMessage::exportAll($model);
    unset($params['model']);
    // Subsequent hooks use $params. Retaining the $params['model'] might be nice - but don't do it unless you figure out how to ensure data-consistency (eg $params['tplParams'] <=> $params['model']).
    // If you want to expose the model via hook, consider interjecting a new Hook::alterWorkflowMessage($model) between `importAll()` and `exportAll()`.

    self::synchronizeLegacyParameters($params);
    CRM_Utils_Hook::alterMailParams($params, 'messageTemplate');
    CRM_Utils_Hook::alterMailContent($mailContent);
    if (!empty($params['subject'])) {
      CRM_Core_Error::deprecatedWarning('CRM_Core_BAO_MessageTemplate: $params[subject] is deprecated. Use $params[messageTemplate][msg_subject] instead.');
      $mailContent['subject'] = $params['subject'];
    }

    self::synchronizeLegacyParameters($params);
    $rendered = CRM_Core_TokenSmarty::render(CRM_Utils_Array::subset($mailContent, ['text', 'html', 'subject']), $params['tokenContext'], $params['tplParams']);
    if (isset($rendered['subject'])) {
      $rendered['subject'] = trim(preg_replace('/[\r\n]+/', ' ', $rendered['subject']));
    }
    $nullSet = ['subject' => NULL, 'text' => NULL, 'html' => NULL];
    $mailContent = array_merge($nullSet, $mailContent, $rendered);
    return [$mailContent, $params];
  }

  /**
   * Some params have been deprecated/renamed. Synchronize old<=>new params.
   *
   * We periodically resync after exchanging data with other parties.
   *
   * @param array $params
   */
  private static function synchronizeLegacyParameters(&$params) {
    // 'valueName' is deprecated, docs were updated some time back
    // and people have been notified. Having it here means the
    // hooks will still see it until we remove.
    CRM_Utils_Array::pathSync($params, ['workflow'], ['valueName']);
    CRM_Utils_Array::pathSync($params, ['tokenContext', 'contactId'], ['contactId']);
    CRM_Utils_Array::pathSync($params, ['tokenContext', 'smarty'], ['disableSmarty'], function ($v, bool $isCanon) {
      return !$v;
    });

    CRM_Utils_Array::pathSync($params, ['tokenContext', 'locale'], ['language']);

    // Core#644 - handle Email ID passed as "From".
    if (isset($params['from'])) {
      $params['from'] = \CRM_Utils_Mail::formatFromAddress($params['from']);
    }
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
   */
  public static function sendTemplate(array $params): array {
    // Handle isEmailPdf here as the unit test on that function deems it 'non-conforming'.
    $isAttachPDFInvoice = !empty($params['isEmailPdf']) && !empty($params['contributionId']);
    unset($params['isEmailPdf']);
    [$mailContent, $params] = self::renderTemplateRaw($params);

    // create the params array
    $params['subject'] = $mailContent['subject'];
    $params['text'] = $mailContent['text'];
    $params['html'] = $mailContent['html'];

    // send the template, honouring the target user’s preferences (if any)
    $sent = FALSE;
    if (!empty($params['toEmail'])) {

      $config = CRM_Core_Config::singleton();
      if ($isAttachPDFInvoice) {
        // FIXME: $params['contributionId'] is not modeled in the parameter list. When is it supplied? Should probably move to tokenContext.contributionId.
        $pdfHtml = CRM_Contribute_BAO_ContributionPage::addInvoicePdfToEmail($params['contributionId'], $params['contactId']);
        if (empty($params['attachments'])) {
          $params['attachments'] = [];
        }
        $params['attachments'][] = CRM_Utils_Mail::appendPDF('Invoice.pdf', $pdfHtml, $mailContent['format']);
      }

      if ($config->doNotAttachPDFReceipt &&
        $params['PDFFilename'] &&
        $params['html']
      ) {
        if (empty($params['attachments'])) {
          $params['attachments'] = [];
        }
        $params['attachments'][] = CRM_Utils_Mail::appendPDF($params['PDFFilename'], $params['html'], $mailContent['format']);
        // This specifically allows the invoice code to attach an invoice & have
        // a different message body. It will be removed & replaced with something
        // saner so avoid trying to leverage this. There are no universe usages outside
        // the core invoice task as of Dec 2023
        if (isset($params['tplParams']['email_comment'])) {
          if ($params['workflow'] !== 'contribution_invoice_receipt') {
            CRM_Core_Error::deprecatedWarning('unsupported parameter email_comment used');
          }
          $params['html'] = $params['tplParams']['email_comment'];
          $params['text'] = strip_tags($params['tplParams']['email_comment']);
        }
      }

      $sent = CRM_Utils_Mail::send($params);
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
   * Mark these fields as translatable.
   *
   * @todo move this definition to the metadata.
   *
   * @see CRM_Utils_Hook::translateFields
   */
  public static function hook_civicrm_translateFields(&$fields) {
    $fields['civicrm_msg_template']['msg_subject'] = TRUE;
    $fields['civicrm_msg_template']['msg_text'] = TRUE;
    $fields['civicrm_msg_template']['msg_html'] = TRUE;
  }

}
