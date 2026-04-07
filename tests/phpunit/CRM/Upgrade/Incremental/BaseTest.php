<?php

use Civi\Api4\ActionSchedule;
use Civi\Api4\MessageTemplate;

/**
 * @group headless
 */
class CRM_Upgrade_Incremental_BaseTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_saved_search', 'civicrm_action_schedule']);
    $this->revertTemplateToReservedTemplate();
    parent::tearDown();
  }

  /**
   * Test message upgrade process.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMessageTemplateUpgrade(): void {
    $templates = $this->callAPISuccess('MessageTemplate', 'get', ['workflow_name' => 'membership_online_receipt'])['values'];
    foreach ($templates as $template) {
      $this->callAPISuccess('MessageTemplate', 'create', ['msg_html' => 'great what a cool member you are', 'id' => $template['id']]);
      $msg_html = $this->callAPISuccessGetValue('MessageTemplate', ['id' => $template['id'], 'return' => 'msg_html']);
      $this->assertEquals('great what a cool member you are', $msg_html);
    }
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.69.alpha1');
    $messageTemplateObject->updateTemplates();

    foreach ($templates as $template) {
      $msg_html = MessageTemplate::get()->addWhere('id', '=', $template['id'])->execute()->first()['msg_html'];
      $this->assertStringContainsString('{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}', $msg_html);
    }
  }

  /**
   * Test that a string replacement in a message template can be done.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMessageTemplateStringReplace(): void {
    MessageTemplate::update()->setValues(['msg_html' => '{$display_name}'])->addWhere(
      'workflow_name', '=', 'contribution_invoice_receipt'
    )->execute();
    $upgrader = new CRM_Upgrade_Incremental_MessageTemplates('5.61.0');
    $check = new CRM_Utils_Check_Component_Tokens();
    $message = $check->checkTokens()[0];
    $this->assertEquals('<p>You are using tokens that have been removed or deprecated.</p><ul><li>Please review your contribution_invoice_receipt message template and remove references to the token {$display_name} as it has been replaced by {contact.display_name}</li></ul></p>', $message->getMessage());
    $upgrader->replaceTokenInTemplate('contribution_invoice_receipt', '$display_name', 'contact.display_name');
    $templates = MessageTemplate::get()->addSelect('msg_html')
      ->addWhere(
        'workflow_name', '=', 'contribution_invoice_receipt'
      )->execute();
    foreach ($templates as $template) {
      $this->assertEquals('{contact.display_name}', $template['msg_html']);
    }
    $messages = $check->checkTokens();
    $this->assertEmpty($messages);
    $this->revertTemplateToReservedTemplate('contribution_invoice_receipt');
  }

  /**
   * Test that a $this->string replacement in a message template can be done.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActionScheduleStringReplace(): void {
    ActionSchedule::create(FALSE)->setValues([
      'title' => 'schedule',
      'absolute_date' => '2021-01-01',
      'start_action_date' => '2021-01-01',
      'mapping_id' => 1,
      'entity_value' => 1,
      'body_text' => 'blah {contribution.status}',
      'body_html' => 'blah {contribution.status}',
      'subject' => 'blah {contribution.status}',
    ])->execute();

    $upgrader = new CRM_Upgrade_Incremental_MessageTemplates('5.61.0');
    $upgrader->replaceTokenInActionSchedule('contribution.status', 'contribution.contribution_status_id:label');
    $templates = ActionSchedule::get()->addSelect('body_html', 'subject', 'body_text')->execute();
    foreach ($templates as $template) {
      $this->assertEquals('blah {contribution.contribution_status_id:label}', $template['body_html']);
      $this->assertEquals('blah {contribution.contribution_status_id:label}', $template['body_text']);
      $this->assertEquals('blah {contribution.contribution_status_id:label}', $template['subject']);
    }
  }

  /**
   * Test message upgrade process only edits the default if the template is customised.
   */
  public function testMessageTemplateUpgradeAlreadyCustomised(): void {
    $templates = $this->callAPISuccess('MessageTemplate', 'get', ['workflow_name' => 'membership_online_receipt'])['values'];
    foreach ($templates as $template) {
      if ($template['is_reserved']) {
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_html' => 'great what a cool member you are', 'id' => $template['id']]);
      }
      else {
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_html' => 'great what a silly sausage you are', 'id' => $template['id']]);
      }
    }
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.69.alpha1');
    $messageTemplateObject->updateTemplates();

    foreach ($templates as $template) {
      $msg_html = MessageTemplate::get()->addWhere('id', '=', $template['id'])->execute()->first()['msg_html'];
      if ($template['is_reserved']) {
        $this->assertStringContainsString('{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}', $msg_html);
      }
      else {
        $this->assertEquals('great what a silly sausage you are', $msg_html);
      }
    }
  }

  /**
   * Test function for messages on upgrade.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMessageTemplateGetUpgradeMessages(): void {
    MessageTemplate::update(FALSE)
      ->addValue('msg_text', 'Edited text')
      ->addWhere('workflow_name', '=', 'event_online_receipt')
      ->addWhere('is_default', '=', TRUE)
      ->execute();
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.74.alpha1');
    $messages = $messageTemplateObject->getUpgradeMessages('5.74');
    $this->assertEquals([
      'Events - Registration Confirmation and Receipt (on-line)' => 'Minor space issue in string',
    ], $messages);
  }

}
