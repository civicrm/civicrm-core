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
 * Define a series of high-level, non-extensible helpers for WorkflowMessages,
 * such as `renderTemplate()` or `sendTemplate()`.
 *
 * These helpers are convenient because it should be common to take a WorkflowMessage
 * instance and pass it to a template. However, WorkflowMessage is the data-model
 * for content of a message -- templating is outside the purview of WorkflowMessage.
 * Consequently, there should not be any substantial templating logic here. Instead,
 * these helpers MUST ONLY delegate out to a canonical renderer.
 */
trait FinalHelperTrait {

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::export()
   */
  abstract public function export(?string $format = NULL): ?array;

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::validate()
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::validate()
   */
  abstract public function validate(): array;

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::assertValid()
   */
  final public function assertValid($strict = FALSE) {
    $validations = $this->validate();
    if (!$strict) {
      $validations = array_filter($validations, function ($validation) {
        return $validation['severity'] === 'error';
      });
    }
    if (!empty($validations)) {
      throw new \CRM_Core_Exception(sprintf("Found %d validation error(s) in %s.", count($validations), get_class($this)));
    }
    return $this;
  }

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::renderTemplate()
   */
  final public function renderTemplate(array $params = []): array {
    $params['model'] = $this;
    return \CRM_Core_BAO_MessageTemplate::renderTemplate($params);
  }

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::sendTemplate()
   */
  final public function sendTemplate(array $params = []): array {
    return \CRM_Core_BAO_MessageTemplate::sendTemplate($params + ['model' => $this]);
  }

  ///**
  // * Get the list of available tokens.
  // *
  // * @return array
  // *   Ex: ['contact.first_name' => ['entity' => 'contact', 'field' => 'first_name', 'label' => ts('Last Name')]]
  // *   Array(string $dottedName => array('entity'=>string, 'field'=>string, 'label'=>string)).
  // */
  //final public function getTokens(): array {
  //  $tp = new TokenProcessor(\Civi::dispatcher(), [
  //    'controller' => static::CLASS,
  //  ]);
  //  $tp->addRow($this->export('tokenContext'));
  //  return $tp->getTokens();
  //}

}
