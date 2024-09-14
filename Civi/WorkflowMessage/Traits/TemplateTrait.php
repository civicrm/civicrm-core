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

namespace Civi\WorkflowMessage\Traits;

use Civi\Api4\Contact;
use Civi\Api4\MessageTemplate;
use CRM_Core_Exception;

/**
 * @method getTemplate(): ?array
 * @method setTemplate(?array $value): $this
 * @method getTemplateId(): ?int
 * @method setTemplateId(?int $value): $this
 * @method getIsTest(): ?bool
 * @method setIsTest(?bool $value): $this
 */
trait TemplateTrait {

  abstract public function getLocale(): ?string;

  abstract public function setLocale(?string $locale);

  /**
   * The content of the message-template.
   *
   * Ex: [
   *   'msg_subject' => 'Hello {contact.first_name}',
   *   'msg_html' => '<p>Greetings and salutations, {contact.display_name}!</p>'
   * ]
   *
   * @var array|null
   * @scope envelope as messageTemplate
   */
  protected $template;

  /**
   * @var int|null
   * @scope envelope as messageTemplateID
   */
  protected $templateId;

  /**
   * @var bool
   * @scope envelope
   */
  protected $isTest;

  public function resolveContent(): array {
    $model = $this;
    $language = $model->getLocale();
    if (empty($language) && !empty($model->getContactID())) {
      $language = Contact::get(FALSE)->addWhere('id', '=', $this->getContactID())->addSelect('preferred_language')->execute()->first()['preferred_language'];
    }
    [$mailContent, $translatedLanguage] = self::loadTemplate((string) $model->getWorkflowName(), $model->getIsTest(), $model->getTemplateId(), $model->getGroupName(), $model->getTemplate(), $language);
    $model->setLocale($translatedLanguage ?? $model->getLocale());
    $model->setRequestedLocale($language);
    return $mailContent;
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
   * @param string|null $language
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   */
  private static function loadTemplate(string $workflowName, bool $isTest, ?int $messageTemplateID = NULL, $groupName = NULL, ?array $messageTemplateOverride = NULL, ?string $language = NULL): array {
    $base = ['msg_subject' => NULL, 'msg_text' => NULL, 'msg_html' => NULL, 'pdf_format_id' => NULL];
    if (!$workflowName && !$messageTemplateID && !$messageTemplateOverride) {
      throw new CRM_Core_Exception(ts("Message template not specified. No option value, ID, or template content."));
    }

    $apiCall = MessageTemplate::get(FALSE)
      ->setLanguage($language)
      ->setTranslationMode('fuzzy')
      ->addSelect('msg_subject', 'msg_text', 'msg_html', 'pdf_format_id', 'id')
      ->addWhere('is_default', '=', 1);

    if ($messageTemplateID) {
      $apiCall->addWhere('id', '=', (int) $messageTemplateID);
      $result = $apiCall->execute();
    }
    elseif ($workflowName) {
      $apiCall->addWhere('workflow_name', '=', $workflowName);
      $result = $apiCall->execute();
    }
    else {
      // Don't bother with query. We know there's nothing.
      $result = new \Civi\Api4\Generic\Result();
    }
    $messageTemplate = array_merge($base, $result->first() ?: [], $messageTemplateOverride ?: []);
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
      'workflow' => $workflowName,
      'isTest' => $isTest,
    ];

    return [$mailContent, $messageTemplate['actual_language'] ?? NULL];
  }

}
