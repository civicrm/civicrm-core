<?php

/**
 * Class CRM_UF_Page_ProfileEditorTest
 * @group headless
 */
class CRM_Upgrade_Incremental_BaseTest extends CiviUnitTestCase {

  public function tearDown() {
    $this->quickCleanup(['civicrm_saved_search']);
  }

  /**
   * Test message upgrade process.
   */
  public function testMessageTemplateUpgrade() {
    $workFlowID = civicrm_api3('OptionValue', 'getvalue', ['return' => 'id', 'name' => 'membership_online_receipt', 'options' => ['limit' => 1, 'sort' => 'id DESC']]);

    $templates = $this->callAPISuccess('MessageTemplate', 'get', ['workflow_id' => $workFlowID])['values'];
    foreach ($templates as $template) {
      $originalText = $template['msg_text'];
      $this->callAPISuccess('MessageTemplate', 'create', ['msg_text' => 'great what a cool member you are', 'id' => $template['id']]);
      $msg_text = $this->callAPISuccessGetValue('MessageTemplate', ['id' => $template['id'], 'return' => 'msg_text']);
      $this->assertEquals('great what a cool member you are', $msg_text);
    }
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.4.alpha1');
    $messageTemplateObject->updateTemplates();

    foreach ($templates as $template) {
      $msg_text = $this->callAPISuccessGetValue('MessageTemplate', ['id' => $template['id'], 'return' => 'msg_text']);
      $this->assertContains('{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}', $msg_text);
      if ($msg_text !== $originalText) {
        // Reset value for future tests.
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_text' => $originalText, 'id' => $template['id']]);
      }
    }
  }

  /**
   * Test message upgrade process only edits the default if the template is customised.
   */
  public function testMessageTemplateUpgradeAlreadyCustomised() {
    $workFlowID = civicrm_api3('OptionValue', 'getvalue', ['return' => 'id', 'name' => 'membership_online_receipt', 'options' => ['limit' => 1, 'sort' => 'id DESC']]);

    $templates = $this->callAPISuccess('MessageTemplate', 'get', ['workflow_id' => $workFlowID])['values'];
    foreach ($templates as $template) {
      if ($template['is_reserved']) {
        $originalText = $template['msg_text'];
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_text' => 'great what a cool member you are', 'id' => $template['id']]);
      }
      else {
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_text' => 'great what a silly sausage you are', 'id' => $template['id']]);
      }
    }
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.4.alpha1');
    $messageTemplateObject->updateTemplates();

    foreach ($templates as $template) {
      $msg_text = $this->callAPISuccessGetValue('MessageTemplate', ['id' => $template['id'], 'return' => 'msg_text']);
      if ($template['is_reserved']) {
        $this->assertContains('{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}', $msg_text);
      }
      else {
        $this->assertEquals('great what a silly sausage you are', $msg_text);
      }

      if ($msg_text !== $originalText) {
        // Reset value for future tests.
        $this->callAPISuccess('MessageTemplate', 'create', ['msg_text' => $originalText, 'id' => $template['id']]);
      }
    }
  }

  /**
   * Test function for messages on upgrade.
   */
  public function testMessageTemplateGetUpgradeMessages() {
    $messageTemplateObject = new CRM_Upgrade_Incremental_MessageTemplates('5.4.alpha1');
    $messages = $messageTemplateObject->getUpgradeMessages();
    $this->assertEquals([
      'Memberships - Receipt (on-line)' => 'Use email greeting at top where available',
      'Contributions - Receipt (on-line)' => 'Use email greeting at top where available',
      'Events - Registration Confirmation and Receipt (on-line)' => 'Use email greeting at top where available',
    ], $messages);
  }

  /**
   * Test converting a datepicker field.
   */
  public function testSmartGroupDatePickerConversion() {
    $this->callAPISuccess('SavedSearch', 'create', [
       'form_values' => [
         ['grant_application_received_date_high', '=', '01/20/2019'],
         ['grant_due_date_low', '=', '01/22/2019'],
       ]
    ]);
    $smartGroupConversionObject = new CRM_Upgrade_Incremental_SmartGroups();
    $smartGroupConversionObject->updateGroups([
      'datepickerConversion' => [
        'grant_application_received_date',
        'grant_decision_date',
        'grant_money_transfer_date',
        'grant_due_date'
      ]
    ]);
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals('grant_application_received_date_high', $savedSearch['form_values'][0][0]);
    $this->assertEquals('2019-01-20 23:59:59', $savedSearch['form_values'][0][2]);
    $this->assertEquals('grant_due_date_low', $savedSearch['form_values'][1][0]);
    $this->assertEquals('2019-01-22 00:00:00', $savedSearch['form_values'][1][2]);
    $hasRelative = FALSE;
    foreach ($savedSearch['form_values'] as $form_value) {
      if ($form_value[0] === 'grant_due_date_relative') {
        $hasRelative = TRUE;
      }
    }
    $this->assertEquals(TRUE, $hasRelative);
  }

  /**
   * Test conversion of on hold group.
   */
  public function testOnHoldConversion() {
    $this->callAPISuccess('SavedSearch', 'create', [
      'form_values' => [
        ['on_hold', '=', '1'],
      ]
    ]);
    $smartGroupConversionObject = new CRM_Upgrade_Incremental_SmartGroups('5.11.alpha1');
    $smartGroupConversionObject->convertEqualsStringToInArray('on_hold');
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals('IN', $savedSearch['form_values'][0][1]);
    $this->assertEquals(['1'], $savedSearch['form_values'][0][2]);

  }

  /**
   * Test renaming a field.
   */
  public function testRenameField() {
    $this->callAPISuccess('SavedSearch', 'create', [
      'form_values' => [
        ['activity_date_low', '=', '01/22/2019'],
      ]
    ]);
    $smartGroupConversionObject = new CRM_Upgrade_Incremental_SmartGroups();
    $smartGroupConversionObject->renameField('activity_date_low', 'activity_date_time_low');
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals('activity_date_time_low', $savedSearch['form_values'][0][0]);
  }

  /**
   * Test renaming multiple fields.
   */
  public function testRenameFields() {
    $this->callAPISuccess('SavedSearch', 'create', [
      'form_values' => [
        ['activity_date_low', '=', '01/22/2019'],
        ['activity_date_relative', '=', 0],
      ]
    ]);
    $smartGroupConversionObject = new CRM_Upgrade_Incremental_SmartGroups();
    $smartGroupConversionObject->renameFields([
      ['old' => 'activity_date_low', 'new' => 'activity_date_time_low'],
      ['old' => 'activity_date_relative', 'new' => 'activity_date_time_relative'],
    ]);
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals('activity_date_time_low', $savedSearch['form_values'][0][0]);
    $this->assertEquals('activity_date_time_relative', $savedSearch['form_values'][1][0]);
  }

}
