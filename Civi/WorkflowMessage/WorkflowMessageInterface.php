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

interface WorkflowMessageInterface {

  /**
   * @return string
   */
  public function getWorkflowName(): ?string;

  /**
   * @return \Civi\WorkflowMessage\FieldSpec[]
   *   A list of field-specs that are used in the given format, keyed by their name in that format.
   *   If the implementation does not understand a specific format, return NULL.
   */
  public function getFields(): array;

  /**
   * @param string|null $format
   *   Ex: 'tplParams', 'tokenContext', 'modelProps', 'envelope'
   * @return array|null
   *   A list of field-values that are used in the given format, keyed by their name in that format.
   *   If the implementation does not understand a specific format, return NULL.
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::export()
   */
  public function export(?string $format = NULL): ?array;

  /**
   * Import values from some scope.
   *
   * Ex: $message->import('tplParams', ['sm_art_stuff' => 123]);
   *
   * @param string $format
   *   Ex: 'tplParams', 'tokenContext', 'modelProps', 'envelope'
   * @param array $values
   *
   * @return $this
   * @see \Civi\WorkflowMessage\Traits\ReflectiveWorkflowTrait::import()
   */
  public function import(string $format, array $values);

  /**
   * Determine if the data for this workflow message is complete/well-formed.
   *
   * @return array
   *   A list of errors and warnings. Each record defines
   *   - severity: string, 'error' or 'warning'
   *   - fields: string[], list of fields implicated in the error
   *   - name: string, symbolic name of the error/warning
   *   - message: string, printable message describing the problem
   */
  public function validate(): array;

  // These additional methods are sugar-coating - they're part of the interface to
  // make it easier to work with, but implementers should not differentiate themselves
  // using this methods. Instead, use FinalHelperTrait as a thin implementation.

  /**
   * Assert that the current message data is valid/sufficient.
   *
   * TIP: Do not implement directly. Use FinalHelperTrait.
   *
   * @param bool $strict
   *   If TRUE, then warnings will raise exceptions.
   *   If FALSE, then only errors will raise exceptions.
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function assertValid($strict = FALSE);

  /**
   * Get the locale in use, if set.
   *
   * @return string|null
   */
  public function getLocale(): ?string;

  /**
   * Render a message template.
   *
   * TIP: Do not implement directly. Use FinalHelperTrait.
   *
   * @param array $params
   *   Options for loading the message template.
   *   If none given, the default for this workflow will be loaded.
   *   Ex: ['messageTemplate' => ['msg_subject' => 'Hello {contact.first_name}']]
   *   Ex: ['messageTemplateID' => 123]
   * @return array
   *   Rendered message, consistent of 'subject', 'text', 'html'
   *   Ex: ['subject' => 'Hello Bob', 'text' => 'It\'s been so long since we sent you an automated notification!']
   * @see \CRM_Core_BAO_MessageTemplate::renderTemplate()
   */
  public function renderTemplate(array $params = []): array;

  /**
   * Send an email using a message template.
   *
   * TIP: Do not implement directly. Use FinalHelperTrait.
   *
   * @param array $params
   *   List of extra parameters to pass to `sendTemplate()`. Ex:
   *   - from
   *   - toName
   *   - toEmail
   *   - cc
   *   - bcc
   *   - replyTo
   *   - isTest
   *
   * @return array
   *   Array of four parameters: a boolean whether the email was sent, and the subject, text and HTML templates
   * @see \CRM_Core_BAO_MessageTemplate::sendTemplate()
   */
  public function sendTemplate(array $params = []): array;

}
