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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Options;

use api\v4\Api4TestBase;
use Civi\Api4\MessageTemplate;
use Civi\Api4\Translation;
use Civi\Test\TransactionalInterface;

/**
 * Tests for the option `$apiRequest->setTranslationMode(...)`.
 *
 * Broadly, these tests need to:
 *   - Make some example business records
 *   - Add translations for them
 *   - Read back the translations, with variations on the translation-mode.
 *
 * @group headless
 */
class TranslationModeTest extends Api4TestBase implements TransactionalInterface {

  public static function getTranslationSettings(): array {
    $es = [];
    $es['fr_FR-full'] = [
      ['partial_locales' => FALSE, 'uiLanguages' => ['en_US', 'fr_FR', 'fr_CA']],
    ];
    $es['fr_FR-partial'] = [
      ['partial_locales' => TRUE, 'uiLanguages' => ['en_US']],
    ];
    return $es;
  }

  /**
   * Test that translated strings are rendered for templates where they exist.
   *
   * @dataProvider getTranslationSettings
   * @throws \CRM_Core_Exception
   * @group locale
   */
  public function testGetTranslatedTemplate($translationSettings): void {
    $cleanup = \CRM_Utils_AutoClean::swapSettings($translationSettings);

    $cid = $this->createTestRecord('Contact', ['preferred_language' => 'fr_FR'])['id'];
    $this->createTestRecord('Contribution', ['contact_id' => $cid]);
    $this->addTranslation();

    $messageTemplate = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', 'IN', ['contribution_online_receipt', 'contribution_offline_receipt'])
      ->addSelect('id', 'msg_subject', 'msg_html', 'workflow_name')
      ->setLanguage('fr_FR')
      ->setTranslationMode('fuzzy')
      ->execute()->indexBy('workflow_name');

    $this->assertFrenchTranslationRetrieved($messageTemplate['contribution_online_receipt']);

    $this->assertStringContainsString('{ts}Contribution Receipt{/ts}', $messageTemplate['contribution_offline_receipt']['msg_subject']);
    $this->assertStringContainsString('Below you will find a receipt', $messageTemplate['contribution_offline_receipt']['msg_html']);
    $this->assertArrayNotHasKey('actual_language', $messageTemplate['contribution_offline_receipt']);

    $messageTemplate = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', 'IN', ['contribution_online_receipt', 'contribution_offline_receipt'])
      ->addSelect('id', 'msg_subject', 'msg_html', 'workflow_name')
      ->setLanguage('fr_CA')
      ->setTranslationMode('fuzzy')
      ->execute()->indexBy('workflow_name');

    $this->assertFrenchTranslationRetrieved($messageTemplate['contribution_online_receipt']);
  }

  /**
   * Test that a draft translation is never used in place of the active one.
   *
   * @dataProvider getTranslationSettings
   * @throws \CRM_Core_Exception
   * @group locale
   */
  public function testDraftTranslationsAreNotUsed($translationSettings): void {
    $cleanup = \CRM_Utils_AutoClean::swapSettings($translationSettings);

    // Case 1: The requested language has no translation and
    // falls back to the site-default language.
    $messageTemplateID = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', '=', 'contribution_online_receipt')
      ->addSelect('id')
      ->execute()->first()['id'];

    Translation::save()->setRecords([
      ['status_id:name' => 'active', 'string' => 'Active English Subject'],
      ['status_id:name' => 'draft', 'string' => 'Draft English Subject'],
    ])->setDefaults([
      'entity_table' => 'civicrm_msg_template',
      'entity_id' => $messageTemplateID,
      'entity_field' => 'msg_subject',
      'language' => 'en_US',
    ])->execute();

    $fallbackResult = MessageTemplate::get()
      ->addWhere('id', '=', $messageTemplateID)
      ->addSelect('id', 'msg_subject')
      ->setLanguage('fr_CA')
      ->setTranslationMode('fuzzy')
      ->execute()->single();

    $this->assertEquals('Active English Subject', $fallbackResult['msg_subject']);
    $this->assertEquals('en_US', $fallbackResult['actual_language']);

    // Case 2: The requested language itself has a translation.
    $this->addTranslation();
    Translation::create()->setValues([
      'entity_table' => 'civicrm_msg_template',
      'entity_id' => $messageTemplateID,
      'entity_field' => 'msg_subject',
      'language' => 'fr_FR',
      'status_id:name' => 'draft',
      'string' => 'Bonjour (draft)',
    ])->execute();

    $frenchResult = MessageTemplate::get()
      ->addWhere('id', '=', $messageTemplateID)
      ->addSelect('id', 'msg_subject', 'msg_html')
      ->setLanguage('fr_FR')
      ->setTranslationMode('fuzzy')
      ->execute()->single();

    $this->assertFrenchTranslationRetrieved($frenchResult);
  }

  /**
   * @return mixed
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function addTranslation() {
    $messageTemplateID = MessageTemplate::get()
      ->addWhere('is_default', '=', 1)
      ->addWhere('workflow_name', '=', 'contribution_online_receipt')
      ->addSelect('id')
      ->execute()->first()['id'];

    Translation::save()->setRecords([
      ['entity_field' => 'msg_subject', 'string' => 'Bonjour'],
      ['entity_field' => 'msg_html', 'string' => 'Voila!'],
      ['entity_field' => 'msg_text', 'string' => '{contribution.total_amount}'],
    ])->setDefaults([
      'entity_table' => 'civicrm_msg_template',
      'entity_id' => $messageTemplateID,
      'status_id:name' => 'active',
      'language' => 'fr_FR',
    ])->execute();
    return $messageTemplateID;
  }

  /**
   * @param $contribution_online_receipt
   */
  private function assertFrenchTranslationRetrieved($contribution_online_receipt): void {
    $this->assertEquals('Bonjour', $contribution_online_receipt['msg_subject']);
    $this->assertEquals('Voila!', $contribution_online_receipt['msg_html']);
    $this->assertEquals('fr_FR', $contribution_online_receipt['actual_language']);
  }

}
