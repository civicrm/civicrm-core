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


namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class FullTextSearchQueryTest extends Api4TestBase implements TransactionalInterface {

  protected function createTestContacts(): void {
    Contact::save(FALSE)
      ->setRecords([
        [
          'first_name' => 'John',
          'last_name' => 'Smith',
        ],
        [
          'first_name' => 'Jonny',
          'legal_name' => 'Jonathon Rotten',
          'last_name' => 'Rotten',
        ],
        [
          'first_name' => 'Jonny',
          'last_name' => 'Boy',
        ],
        [
          'first_name' => 'Jonathon',
          'last_name' => 'Swift',
        ],
        [
          'first_name' => 'Jess',
          'last_name' => '',
        ],
        [
          'first_name' => 'Johannes',
          'last_name' => 'Kahn',
        ],
        [
          'first_name' => 'Samuel',
          'last_name' => 'Johnson',
          'birth_date' => '1709-09-18',
        ],
        [
          'first_name' => 'Alexander',
          'middle_name' => 'Boris de Pfeffel',
          'last_name' => 'Johnson',
          'birth_date' => '1964-06-19',
        ],
      ])
      ->execute();

    \Civi::settings()->set('search_mysql_fts', TRUE);
  }

  public function testContainsQueries(): void {
    $this->createTestContacts();

    // find in first name and legal_name
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'CONTAINS', 'Jonathon')
      ->execute();

    $this->assertEquals(2, $results->count());

    // match multiple words, check better matches come first
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'CONTAINS', 'Boris Johnson')
      ->execute();

    $this->assertEquals('Alexander', $results[0]['first_name']);
    $this->assertEquals('Samuel', $results[1]['first_name']);

    // check it wasn't coincidentally first
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'CONTAINS', 'Samuel Johnson')
      ->execute();

    $this->assertEquals('Samuel', $results[0]['first_name']);
    $this->assertEquals('Alexander', $results[1]['first_name']);
  }

  public function testMatchesQueries(): void {
    $this->createTestContacts();

    // match two words
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'MATCHES', '+Jonny +Rotten')
      ->execute();

    $this->assertEquals(1, $results->count());
    $this->assertEquals('Jonny Rotten', $results->single()['display_name']);

    // include exclude - only match should be Jonny Boy
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'MATCHES', '+Jonny -Rotten')
      ->execute();

    $this->assertEquals(1, $results->count());
    $this->assertEquals('Jonny Boy', $results->single()['display_name']);
  }

  public function testLikeQueries(): void {
    $this->createTestContacts();

    // supprt Mysql LIKE style wildcards
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'LIKE', 'Jon%')
      ->execute();

    $this->assertEquals(3, $results->count());

    // only trailing wildcards are supported
    try {
      $results = \Civi\Api4\Contact::get(FALSE)
        ->addWhere('contact_names', 'LIKE', 'J%n')
        ->execute();

      $this->fail('Invalid wildcard for FTS LIKE');
    }
    catch (\Throwable $e) {
      $this->assertTrue(\str_contains($e->getMessage(), 'Invalid wildcard in FTS query'));
    }

    // multiple words can be provided
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'LIKE', 'Joh% Kah%')
      ->execute();

    $this->assertEquals(1, $results->count());
    // the best match should be first
    $this->assertEquals('Johannes Kahn', $results->first()['display_name']);
  }

  public function testInteractionWithExplicitOrderBy(): void {
    $this->createTestContacts();

    // check it wasn't coincidentally first
    $results = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('contact_names', 'CONTAINS', 'Boris Johnson')
      ->addOrderBy('birth_date', 'ASC')
      ->execute();

    // birth date should take precedence over the better FTS match
    $this->assertEquals('Samuel', $results->first()['first_name']);
  }

}
