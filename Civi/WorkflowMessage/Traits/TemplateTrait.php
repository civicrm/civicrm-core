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

}
