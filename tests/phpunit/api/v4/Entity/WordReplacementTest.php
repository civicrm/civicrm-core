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


namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class WordReplacementTest extends Api4TestBase implements TransactionalInterface {

  public function testDefaults(): void {
    $create = \Civi\Api4\WordReplacement::create(FALSE)
      ->addValue('find_word', 'Foo')
      ->addValue('replace_word', 'Bar')
      ->execute()
      ->first();

    $result = $this->getTestRecord('WordReplacement', $create['id']);
    $this->assertTrue($result['is_active']);
    $this->assertEquals('wildcardMatch', $result['match_type']);
    $this->assertEquals(\CRM_Core_Config::domainID(), $result['domain_id']);
    $this->assertEquals(\CRM_Core_I18n::getLocale(), $result['language']);
  }

  public function testMultilingualAndReplace(): void {
    $domainId = \CRM_Core_Config::domainID();

    \Civi\Api4\WordReplacement::create(FALSE)
      ->setValues([
        'find_word' => 'Contribution',
        'replace_word' => 'Donation',
        'language' => 'en_US',
      ])
      ->execute();

    \Civi\Api4\WordReplacement::create(FALSE)
      ->setValues([
        'find_word' => 'Contribution',
        'replace_word' => 'Don',
        'language' => 'fr_CA',
      ])
      ->execute();

    $enCustomStrings = \CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $frCustomStrings = \CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('fr_CA');

    $this->assertEquals('Donation', $enCustomStrings['enabled']['wildcardMatch']['Contribution']);
    $this->assertEquals('Don', $frCustomStrings['enabled']['wildcardMatch']['Contribution']);

    // Test APIv4 replace scoped to en_US: should not delete the fr_CA record
    \Civi\Api4\WordReplacement::replace(FALSE)
      ->setWhere([
        ['domain_id', '=', $domainId],
        ['language', '=', 'en_US'],
      ])
      ->setMatch(['find_word', 'domain_id', 'language'])
      ->setRecords([
        [
          'find_word' => 'Contribution',
          'replace_word' => 'Gift',
          'is_active' => TRUE,
          'match_type' => 'wildcardMatch',
        ],
        [
          'find_word' => 'Membership',
          'replace_word' => 'Subscription',
          'is_active' => TRUE,
          'match_type' => 'exactMatch',
        ],
      ])
      ->execute();

    $enCustomStrings = \CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $frCustomStrings = \CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('fr_CA');

    $this->assertEquals('Gift', $enCustomStrings['enabled']['wildcardMatch']['Contribution']);
    $this->assertEquals('Subscription', $enCustomStrings['enabled']['exactMatch']['Membership']);
    $this->assertEquals('Don', $frCustomStrings['enabled']['wildcardMatch']['Contribution']);
  }

  public function testHtmlWordReplacement(): void {
    $domainId = \CRM_Core_Config::domainID();

    \Civi\Api4\WordReplacement::replace(FALSE)
      ->setWhere([
        ['domain_id', '=', $domainId],
        ['language', '=', 'en_US'],
      ])
      ->setMatch(['find_word', 'domain_id', 'language'])
      ->setRecords([
        [
          'find_word' => '<strong>Important</strong>',
          'replace_word' => '<em>Crucial</em>',
          'is_active' => TRUE,
          'match_type' => 'wildcardMatch',
        ],
      ])
      ->execute();

    $enStrings = \CRM_Core_BAO_WordReplacement::getLocaleCustomStrings('en_US');
    $this->assertArrayHasKey('<strong>Important</strong>', $enStrings['enabled']['wildcardMatch']);
    $this->assertEquals('<em>Crucial</em>', $enStrings['enabled']['wildcardMatch']['<strong>Important</strong>']);
  }

}
