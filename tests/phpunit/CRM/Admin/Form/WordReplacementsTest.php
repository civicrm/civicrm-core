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
use Civi\Test\FormTrait;

/**
 * Tests for the WordReplacements admin form.
 *
 * @group headless
 */
class CRM_Admin_Form_WordReplacementsTest extends CiviUnitTestCase {
  use FormTrait;

  protected function setUp(): void {
    parent::setUp();
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
  }

  protected function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    parent::tearDown();
  }

  /**
   * Test dev/core#600: Saving word replacements with HTML strings does not reset
   * or wipe other word replacements.
   */
  public function testFormSubmitWithHtml(): void {
    $submittedValues = [
      '_qf_default' => 'WordReplacements:next',
      'old' => [
        1 => '<p>test</p>',
        2 => 'Donor',
      ],
      'new' => [
        1 => '<em>sample</em>',
        2 => '<strong>Supporter</strong>',
      ],
      'enabled' => [
        1 => 1,
        2 => 1,
      ],
      'cb' => [
        1 => 1,
        2 => 0,
      ],
    ];

    try {
      $this->getTestForm('CRM_Admin_Form_WordReplacements', $submittedValues)->processForm();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
    }

    $overrides = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $this->assertNotEmpty($overrides);
    $this->assertEquals('<em>sample</em>', $overrides['enabled']['exactMatch']['<p>test</p>']);
    $this->assertEquals('<strong>Supporter</strong>', $overrides['enabled']['wildcardMatch']['Donor']);
  }

  /**
   * Test that saving in one locale does not delete word replacements in other locales.
   */
  public function testFormSubmitPreservesOtherLocales(): void {
    $domainId = CRM_Core_Config::domainID();

    WordReplacement::create(FALSE)
      ->setValues([
        'find_word' => 'Contribution',
        'replace_word' => 'Don',
        'language' => 'fr_CA',
        'domain_id' => $domainId,
      ])
      ->execute();

    $submittedValues = [
      '_qf_default' => 'WordReplacements:next',
      'old' => [
        1 => 'Contribution',
      ],
      'new' => [
        1 => 'Gift',
      ],
      'enabled' => [
        1 => 1,
      ],
    ];

    try {
      $this->getTestForm('CRM_Admin_Form_WordReplacements', $submittedValues)->processForm();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
    }

    $enOverrides = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $frOverrides = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('fr_CA');

    $this->assertEquals('Gift', $enOverrides['enabled']['wildcardMatch']['Contribution']);
    $this->assertEquals('Don', $frOverrides['enabled']['wildcardMatch']['Contribution']);
  }

  /**
   * Test clearing all replacements in the form removes current locale entries
   * cleanly without throwing notices or errors.
   */
  public function testFormSubmitClearAll(): void {
    $domainId = CRM_Core_Config::domainID();

    WordReplacement::create(FALSE)
      ->setValues([
        'find_word' => 'Member',
        'replace_word' => 'Subscriber',
        'language' => 'en_US',
        'domain_id' => $domainId,
      ])
      ->execute();

    WordReplacement::create(FALSE)
      ->setValues([
        'find_word' => 'Member',
        'replace_word' => 'Adhérent',
        'language' => 'fr_CA',
        'domain_id' => $domainId,
      ])
      ->execute();

    $submittedValues = [
      '_qf_default' => 'WordReplacements:next',
      'old' => [],
      'new' => [],
    ];

    try {
      $this->getTestForm('CRM_Admin_Form_WordReplacements', $submittedValues)->processForm();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
    }

    $enOverrides = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $frOverrides = CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('fr_CA');

    $this->assertEmpty($enOverrides);
    $this->assertEquals('Adhérent', $frOverrides['enabled']['wildcardMatch']['Member']);
  }

  /**
   * Test validation rules in formRule.
   */
  public function testFormRule(): void {
    $errors = CRM_Admin_Form_WordReplacements::formRule([
      'old' => [1 => 'foo'],
      'new' => [1 => ''],
    ]);
    $this->assertArrayHasKey('new[1]', $errors);

    $errors = CRM_Admin_Form_WordReplacements::formRule([
      'old' => [1 => ''],
      'new' => [1 => 'bar'],
    ]);
    $this->assertArrayHasKey('old[1]', $errors);

    // Tags that purify to empty string
    $errors = CRM_Admin_Form_WordReplacements::formRule([
      'old' => [1 => '<unknown></unknown>'],
      'new' => [1 => 'bar'],
    ]);
    $this->assertArrayHasKey('old[1]', $errors);

    $errors = CRM_Admin_Form_WordReplacements::formRule([
      'old' => [1 => 'foo'],
      'new' => [1 => '<unknown></unknown>'],
    ]);
    $this->assertArrayHasKey('new[1]', $errors);

    // Valid pair
    $errors = CRM_Admin_Form_WordReplacements::formRule([
      'old' => [1 => 'foo'],
      'new' => [1 => 'bar'],
    ]);
    $this->assertEmpty($errors);
  }

}
