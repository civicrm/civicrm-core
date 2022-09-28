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

use Civi\Api4\Contact;
use api\v4\UnitTestCase;
use Civi\Api4\Event\ValidateValuesEvent;
use Civi\Test\TransactionalInterface;

/**
 * Assert that 'validateValues' runs during
 *
 * @group headless
 */
class ValidateValuesTest extends UnitTestCase implements TransactionalInterface {

  private $lastValidator;

  protected function setUp(): void {
    $this->lastValidator = NULL;
    parent::setUp();
  }

  protected function tearDown(): void {
    $this->setValidator(NULL);
    parent::tearDown();
  }

  /**
   * Fire ValidateValuesEvent several times - and ensure it conveys the
   * expected data.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testHookData() {
    $hookCount = 0;

    // Step 1: `create()` a record for Ms. Alice Alison.
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertEquals('create', $e->getActionName());
      $this->assertEquals('Alice', $e->records[0]['first_name']);
      $this->assertEquals('Alison', $e->records[0]['last_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertEquals(1, count($e->diffs));
      $this->assertEquals(NULL, $e->diffs[0]['old']);
      $this->assertEquals('Alice', $e->diffs[0]['new']['first_name']);
      $this->assertEquals('Alison', $e->diffs[0]['new']['last_name']);

    });
    $created = Contact::create()->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'Alice',
      'last_name' => 'Alison',
    ])->execute()->single();
    $this->assertTrue(is_numeric($created['id']));
    $this->assertEquals(1, $hookCount);

    // Step 2: `save()` a couple records for Ms. Alice Alison and Mr. Bob Bobmom.
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertEquals('save', $e->getActionName());
      $this->assertEquals('Alicia', $e->records[0]['first_name']);
      $this->assertEquals('Bob', $e->records[1]['first_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertEquals(2, count($e->diffs));
      $this->assertEquals('Alice', $e->diffs[0]['old']['first_name']);
      $this->assertEquals('Alicia', $e->diffs[0]['new']['first_name']);
      $this->assertEquals(NULL, $e->diffs[1]['old']);
      $this->assertEquals('Bob', $e->diffs[1]['new']['first_name']);

    });
    $saved = Contact::save()->setRecords([
      ['id' => $created['id'], 'first_name' => 'Alicia'],
      ['contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Bobmom'],
    ])->execute();
    $this->assertEquals(2, $saved->count());
    $this->assertEquals(2, $hookCount);

    // Step 3: `update()` a record for Mr. Bob Bobmom
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertEquals('update', $e->getActionName());
      $this->assertEquals('Bobby', $e->records[0]['first_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertEquals(1, count($e->diffs));
      $this->assertEquals('Bob', $e->diffs[0]['old']['first_name']);
      $this->assertEquals('Bobmom', $e->diffs[0]['old']['last_name']);
      $this->assertEquals('Bobby', $e->diffs[0]['new']['first_name']);
    });
    $updated = Contact::update()
      ->setValues(['first_name' => 'Bobby'])
      ->addWhere('last_name', '=', 'Bobmom')
      ->execute();
    $this->assertEquals(1, $updated->count());
    $this->assertEquals(3, $hookCount);
  }

  public function testRaiseError() {
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      foreach ($e->records as $k => $record) {
        $e->addError($k, 'first_name', 'not-namey-enough', ts('The first name is not sufficiently namey.'));
        $e->addError($k, ['first_name', 'last_name'], 'tongue-twister', ts('When the names are put together, they become a tongue twister.'));
        $e->errors[] = [
          'record' => $k,
          'fields' => ['last_name'],
          'name' => 'misspelled',
          'message' => ts('I disagree with the spelling of your name.'),
        ];
      }

      $hookCount++;
    });

    try {
      Contact::create()->setValues([
        'contact_type' => 'Individual',
        'first_name' => 'Alice',
        'last_name' => 'Alison',
      ])->execute();
      $this->fail('Expected an exception due to validation error');
    }
    catch (\API_Exception $e) {
      $this->assertEquals(1, $hookCount);
      $this->assertRegExp(';not sufficiently namey;', $e->getMessage());
      $this->assertRegExp(';tongue twister;', $e->getMessage());
      $this->assertRegExp(';disagree with the spelling;', $e->getMessage());
    }
  }

  /**
   * Add/replace the validator.
   *
   * @param callable $func
   */
  protected function setValidator($func) {
    $dispatcher = \Civi::dispatcher();
    if ($this->lastValidator) {
      $dispatcher->removeListener('civi.api4.validate', $this->lastValidator);
    }
    if ($func) {
      $dispatcher->addListener('civi.api4.validate', $func);
    }
    $this->lastValidator = $func;
  }

  protected function assertWellFormedEvent(ValidateValuesEvent $e) {
    $this->assertRegExp('/Contact/', $e->getEntityName());
    $this->assertRegExp('/create|save|update/', $e->getActionName());
    $this->assertTrue(count($e->records) > 0);
    foreach ($e->records as $record) {
      $this->assertWellFormedFields($record);
    }

    // We want to let the main test do some assertions on the lazy-array, so we'll peek a clone.
    $peekAtDiffs = clone $e->diffs;
    $this->assertTrue(count($peekAtDiffs) > 0);
    foreach ($peekAtDiffs as $diff) {
      $this->assertTrue(is_array($diff['new']));
      $this->assertWellFormedFields($diff['new']);
      if ($diff['old'] !== NULL) {
        $this->assertTrue(is_array($diff['old']));
        $this->assertWellFormedFields($diff['old']);
      }
    }
  }

  protected function assertWellFormedFields($record) {
    foreach ($record as $field => $value) {
      $this->assertRegExp('/^[a-zA-Z0-9_]+$/', $field);
    }
  }

}
