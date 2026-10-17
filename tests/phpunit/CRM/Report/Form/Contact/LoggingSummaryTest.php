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

use Civi\Api4\WordReplacement;

/**
 * @group headless
 */
class CRM_Report_Form_Contact_LoggingSummaryTest extends CiviReportTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->callAPISuccess('Setting', 'create', ['logging' => TRUE]);
    $this->quickCleanup(['civicrm_contact', 'log_civicrm_contact']);
    unset(\Civi::$statics['CRM_Utils_Request']['id']);
    CRM_Core_DAO::init(CIVICRM_DSN);
  }

  public function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    $this->callAPISuccess('Setting', 'create', ['logging' => FALSE]);
    parent::tearDown();
    (new CRM_Logging_Schema())->dropAllLogTables();
  }

  /**
   * An email added to a contact is shown as a contact update linked to its details, whatever the actions are called.
   */
  public function testAddedEmailLinksToDetailWithTranslatedActions(): void {
    WordReplacement::save(FALSE)
      ->setRecords([
        ['find_word' => 'Insert', 'replace_word' => 'Einfügen', 'match_type' => 'exactMatch'],
        ['find_word' => 'Update', 'replace_word' => 'Aktualisieren', 'match_type' => 'exactMatch'],
      ])
      ->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    $contactID = $this->individualCreate();
    unset(\Civi::$statics['CRM_Utils_Request']['id']);
    CRM_Core_DAO::init(CIVICRM_DSN);
    $this->callAPISuccess('Email', 'create', ['contact_id' => $contactID, 'email' => 'added@example.org']);

    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'logging/contact/summary',
      'fields' => ['log_type' => 1, 'altered_contact' => 1, 'log_action' => 1],
    ])['values'];
    $emailRows = array_filter($rows, fn($row) => (int) $row['log_civicrm_entity_altered_contact_id'] === $contactID && str_contains($row['log_civicrm_entity_log_action'], 'Update'));
    $this->assertCount(1, $emailRows);
    $this->assertStringContainsString('crm-summary-link', reset($emailRows)['log_civicrm_entity_log_action']);
  }

}
