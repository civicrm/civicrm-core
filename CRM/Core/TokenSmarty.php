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

use Civi\Token\TokenProcessor;

/**
 * `Token-Smarty` is a hybrid template format, combining Civi token notation with
 * Smarty notation, as in `{if {contact.id} > 10}...{/if}`.
 *
 * NOTE: It is arguable about whether the existence of this format is a good thing,
 * but it does exist, and this helper makes it a little easier to work with.
 */
class CRM_Core_TokenSmarty {

  /**
   * Render some template(s), evaluating token expressions and Smarty expressions.
   *
   * This helper simplifies usage of hybrid notation. As a simplification, it may not be optimal for processing
   * large batches (e.g. CiviMail or scheduled-reminders), but it's a little more convenient for 1-by-1 use-cases.
   *
   * @param array $messages
   *   Message templates. Any mix of the following templates ('text', 'html', 'subject', 'msg_text', 'msg_html', 'msg_subject').
   *   Ex: ['subject' => 'Hello {contact.display_name}', 'text' => 'What up?'].
   *   Note: The content-type may be inferred by default. A key like 'html' or 'msg_html' indicates HTML formatting; any other key indicates text formatting.
   * @param array $tokenContext
   *   Ex: ['contactId' => 123, 'activityId' => 456]
   * @param array|null $smartyAssigns
   *   List of data to export via Smarty.
   *   Data is only exported temporarily (long enough to execute this render() method).
   * @return array
   *   Rendered messages. These match the various inputted $messages.
   *   Ex: ['msg_subject' => 'Hello Bob Roberts', 'msg_text' => 'What up?']
   * @internal
   */
  public static function render(array $messages, array $tokenContext = [], array $smartyAssigns = []): array {
    $result = [];
    $tokenContextDefaults = [
      'controller' => __CLASS__,
      'smarty' => TRUE,
    ];
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), array_merge($tokenContextDefaults, $tokenContext));
    $tokenProcessor->addRow([]);
    $useSmarty = !empty($tokenProcessor->context['smarty']);

    // Load templates
    foreach ($messages as $messageId => $messageTpl) {
      $format = preg_match('/html/', $messageId) ? 'text/html' : 'text/plain';
      $tokenProcessor->addMessage($messageId, $messageTpl, $format);
    }

    // Evaluate/render templates
    try {
      if ($useSmarty) {
        CRM_Core_Smarty::singleton()->pushScope($smartyAssigns);
      }
      $tokenProcessor->evaluate();
      foreach ($messages as $messageId => $ign) {
        foreach ($tokenProcessor->getRows() as $row) {
          $result[$messageId] = $row->render($messageId);
        }
      }
    }
    finally {
      if ($useSmarty) {
        CRM_Core_Smarty::singleton()->popScope();
      }
    }

    return $result;
  }

}
