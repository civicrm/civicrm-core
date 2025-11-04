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

namespace Civi\WorkflowMessage;

use Civi\Schema\Traits\MagicGetterSetterTrait;
use Civi\WorkflowMessage\Traits\AddressingTrait;
use Civi\WorkflowMessage\Traits\FinalHelperTrait;
use Civi\WorkflowMessage\Traits\LocalizationTrait;
use Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait;
use Civi\WorkflowMessage\Traits\TemplateTrait;

/**
 * Generic base-class for describing the inputs for a workflow email template.
 *
 * @method $this setContactID(int|null $contactID)
 * @method int|null getContactID()
 * @method $this setContact(array|null $contact)
 * @method array|null getContact()
 * @method $this setUserEnteredText(string $text)
 * @method $this setUserEnteredHTML(string $html)
 *
 * @support template-only
 * GenericWorkflowMessage should aim for "full" support, but it's prudent to keep
 * it flexible for the first few months. Consider updating to "full" after Dec 2021.
 */
class GenericWorkflowMessage implements WorkflowMessageInterface {

  // Implement getFields(), import(), export(), validate() - All methods based on inspecting class properties (`ReflectionClass`).
  // Define stub methods exportExtraTokenContext(), exportExtraTplParams(), etc.
  use ReflectiveWorkflowTrait;

  // Implement __call() - Public and protected properties are automatically given a default getter/setter. These may be overridden/customized.
  use MagicGetterSetterTrait;

  // Implement assertValid(), renderTemplate(), sendTemplate() - Sugary stub methods that delegate to real APIs.
  use FinalHelperTrait;

  // Implement setTo(), setReplyTo(), etc
  use AddressingTrait;

  // Implement setLocale(), etc
  use LocalizationTrait;

  // Implement setTemplate(), etc
  use TemplateTrait;

  /**
   * WorkflowMessage constructor.
   *
   * @param array $imports
   *   List of values to import.
   *   Ex: ['tplParams' => [...tplValues...], 'tokenContext' => [...tokenData...]]
   *   Ex: ['modelProps' => [...classProperties...]]
   */
  public function __construct(array $imports = []) {
    WorkflowMessage::importAll($this, $imports);
  }

  /**
   * The contact receiving this message.
   *
   * @var int|null
   * @scope tokenContext as contactId, tplParams as contactID
   * @fkEntity Contact
   */
  protected $contactID;

  /**
   * @var array|null
   * @scope tokenContext
   */
  protected $contact;

  /**
   * Must provide either 'int $contactId' or 'array $contact'
   *
   * @param array $errors
   * @see ReflectiveWorkflowTrait::validate()
   */
  protected function validateExtra_contact(array &$errors) {
    if (empty($this->contactID) && empty($this->contact['id'])) {
      $errors[] = [
        'severity' => 'error',
        'fields' => ['contactId', 'contact'],
        'name' => 'missingContact',
        'message' => ts('Message template requires one of these fields (%1)', [1 => 'contactId, contact']),
      ];
    }
    if (!empty($this->contactID) && !empty($this->contact)) {
      $errors[] = [
        'severity' => 'warning',
        'fields' => ['contactId', 'contact'],
        'name' => 'missingContact',
        'message' => ts('Passing both (%1) may lead to ambiguous behavior.', [1 => 'contactId, contact']),
      ];
    }
  }

  /**
   * Define tokens to be exported as smarty values.
   *
   * @param array $export
   */
  protected function exportExtraTokenContext(array &$export): void {
    // Tax term is exposed at the generic level as so many templates use it
    // (e.g. Membership, participant, pledge as well as contributions).
    // However, these basically now all implement the ContributionTrait so we
    // can hopefully remove from here (after some checking).
    $export['smartyTokenAlias']['taxTerm'] = 'domain.tax_term';
  }

  /**
   * Additional text to include in the receipt.
   *
   * @var string
   *
   * @scope tplParams as userTextPlain
   */
  protected $userEnteredText;

  /**
   * Additional html to include in the receipt.
   *
   * @var string
   *
   * @scope tplParams as userText
   */
  protected $userEnteredHTML;

  public function getUserEnteredText(): ?string {
    return $this->userEnteredText ?: ($this->userEnteredHTML ? \CRM_Utils_String::htmlToText($this->userEnteredHTML) : NULL);
  }

  public function getUserEnteredHTML(): ?string {
    return \CRM_Utils_String::purifyHTML($this->userEnteredHTML ?: ($this->userEnteredText ? nl2br($this->userEnteredText) : ''));
  }

}
