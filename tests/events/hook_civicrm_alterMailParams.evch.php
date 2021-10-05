<?php

use Civi\Test\EventCheck;
use Civi\Test\HookInterface;

return new class() extends EventCheck implements HookInterface {

  private $paramSpecs = [

    // ## Envelope: Common

    'toName' => ['type' => 'string|NULL'],
    'toEmail' => ['type' => 'string|NULL'],
    'cc' => ['type' => 'string|NULL'],
    'bcc' => ['type' => 'string|NULL'],
    'headers' => ['type' => 'array'],
    'attachments' => ['type' => 'array|NULL'],
    'isTest' => ['type' => 'bool|int'],

    // ## Envelope: singleEmail/messageTemplate

    'from' => ['type' => 'string|NULL', 'for' => ['messageTemplate', 'singleEmail']],
    'replyTo' => ['type' => 'string|NULL', 'for' => ['messageTemplate', 'singleEmail']],
    'returnPath' => ['type' => 'string|NULL', 'for' => ['messageTemplate', 'singleEmail']],
    'isEmailPdf' => ['type' => 'bool', 'for' => 'messageTemplate'],
    'PDFFilename' => ['type' => 'string|NULL', 'for' => 'messageTemplate'],
    'autoSubmitted' => ['type' => 'bool', 'for' => 'messageTemplate'],
    'Message-ID' => ['type' => 'string', 'for' => ['messageTemplate', 'singleEmail']],
    'messageId' => ['type' => 'string', 'for' => ['messageTemplate', 'singleEmail']],

    // ## Envelope: CiviMail/Flexmailer

    'Reply-To' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'Return-Path' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'From' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'Subject' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'List-Unsubscribe' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'X-CiviMail-Bounce' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer']],
    'Precedence' => ['type' => 'string|NULL', 'for' => ['civimail', 'flexmailer'], 'regex' => '/(bulk|first-class|list)/'],
    'job_id' => ['type' => 'int|NULL', 'for' => ['civimail', 'flexmailer']],

    // ## Content

    'subject' => ['for' => ['messageTemplate', 'singleEmail'], 'type' => 'string'],
    'text' => ['type' => 'string|NULL'],
    'html' => ['type' => 'string|NULL'],

    // ## Model: messageTemplate

    'tokenContext' => ['type' => 'array', 'for' => 'messageTemplate'],
    'tplParams' => ['type' => 'array', 'for' => 'messageTemplate'],
    'contactId' => ['type' => 'int|NULL', 'for' => 'messageTemplate' /* deprecated in favor of tokenContext[contactId] */],
    'workflow' => [
      'regex' => '/^([a-zA-Z_]+)$/',
      'type' => 'string',
      'for' => 'messageTemplate',
    ],
    'valueName' => [
      'regex' => '/^([a-zA-Z_]+)$/',
      'type' => 'string',
      'for' => 'messageTemplate',
    ],
    'groupName' => [
      // This field is generally deprecated. Historically, this was tied to various option-groups (`msg_*`),
      // but it also seems to have been used with a few long-form English names.
      'regex' => '/^(msg_[a-zA-Z_]+|Scheduled Reminder Sender|Activity Email Sender|Report Email Sender|Mailing Event Welcome|CRM_Core_Config_MailerTest)$/',
      'type' => 'string',
      'for' => ['messageTemplate', 'singleEmail'],
    ],

    // The model is not passed into this hook because it would create ambiguity when you alter properties.
    // If you want to expose it via hook, add another hook.
    'model' => ['for' => 'messageTemplate', 'type' => 'NULL'],
    'modelProps' => ['for' => 'messageTemplate', 'type' => 'NULL'],

    // ## Model: Adhoc/incomplete/needs attention

    'contributionId' => ['type' => 'int', 'for' => 'messageTemplate'],
    'petitionId' => ['type' => 'int', 'for' => 'messageTemplate'],
    'petitionTitle' => ['type' => 'string', 'for' => 'messageTemplate'],
    'table' => ['type' => 'string', 'for' => 'messageTemplate', 'regex' => '/civicrm_msg_template/'],
    'entity' => ['type' => 'string|NULL', 'for' => 'singleEmail'],
    'entity_id' => ['type' => 'int|NULL', 'for' => 'singleEmail'],

    // ## View: messageTemplate

    'messageTemplateID' => ['type' => 'int|NULL', 'for' => 'messageTemplate'],
    'messageTemplate' => ['type' => 'array|NULL', 'for' => 'messageTemplate'],
    'disableSmarty' => ['type' => 'bool|int', 'for' => 'messageTemplate'],
  ];

  public function isSupported($test): bool {
    // MailTest does intentionally breaky things to provoke+ensure decent error-handling.
    //So we will not enforce generic rules on it.
    return !($test instanceof CRM_Utils_MailTest);
  }

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::alterMailParams()
   */
  public function hook_civicrm_alterMailParams(&$params, $context = NULL): void {
    $msg = 'Non-conforming hook_civicrm_alterMailParams(..., $context)';
    $dump = print_r($params, 1);

    $this->assertRegExp('/^(messageTemplate|civimail|singleEmail|flexmailer)$/',
      $context, "$msg: Unrecognized context ($context)\n$dump");

    $contexts = [$context];
    if ($context === 'singleEmail' && array_key_exists('tokenContext', $params)) {
      // Don't look now, but `sendTemplate()` fires this hook twice for the message! Once with $context=messageTemplate; again with $context=singleEmail.
      $contexts[] = 'messageTemplate';
    }

    $paramSpecs = array_filter($this->paramSpecs, function ($f) use ($contexts) {
      return !isset($f['for']) || array_intersect((array) $f['for'], $contexts);
    });

    $unknownKeys = array_diff(array_keys($params), array_keys($paramSpecs));
    if ($unknownKeys !== []) {
      echo '';
    }
    $this->assertEquals([], $unknownKeys, "$msg: Unrecognized keys: " . implode(', ', $unknownKeys) . "\n$dump");

    foreach ($params as $key => $value) {
      if (isset($paramSpecs[$key]['type'])) {
        $this->assertType($paramSpecs[$key]['type'], $value, "$msg: Bad data-type found in param ($key)\n$dump");
      }
      if (isset($paramSpecs[$key]['regex']) && $value !== NULL) {
        $this->assertRegExp($paramSpecs[$key]['regex'], $value, "Parameter [$key => $value] should match regex ({$paramSpecs[$key]['regex']})");
      }
    }

    if ($context === 'messageTemplate') {
      $this->assertNotEmpty($params['workflow'], "$msg: Message templates must always specify a symbolic name of the step/task\n$dump");
      if (isset($params['valueName'])) {
        // This doesn't require that valueName be supplied - but if it is supplied, it must match the workflow name.
        $this->assertEquals($params['workflow'], $params['valueName'], "$msg: If given, workflow and valueName must match\n$dump");
      }
      $this->assertEquals($params['contactId'] ?? NULL, $params['tokenContext']['contactId'] ?? NULL, "$msg: contactId moved to tokenContext, but legacy value should be equivalent\n$dump");

      // This assertion is surprising -- yet true. We should perhaps check if it was true in past releases...
      $this->assertTrue(empty($params['text']) && empty($params['html']) && empty($params['subject']), "$msg: Content is not given if context==messageTemplate\n$dump");
    }

    if ($context !== 'messageTemplate') {
      $this->assertTrue(!empty($params['text']) || !empty($params['html']) || !empty($params['subject']), "$msg: Must provide at least one of: text, html, subject\n$dump");
    }

    if (isset($params['groupName']) && $params['groupName'] === 'Scheduled Reminder Sender') {
      $this->assertNotEmpty($params['entity'], "$msg: Scheduled reminders should have entity\n$dump");
      $this->assertNotEmpty($params['entity_id'], "$msg: Scheduled reminders should have entity_id\n$dump");
    }
  }

};
