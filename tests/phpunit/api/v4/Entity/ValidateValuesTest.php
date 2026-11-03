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
use api\v4\Api4TestBase;
use Civi\Api4\Event\ValidateValuesEvent;
use Civi\Test\TransactionalInterface;

/**
 * Assert that 'validateValues' runs during
 *
 * @group headless
 */
class ValidateValuesTest extends Api4TestBase implements TransactionalInterface {

  private $lastValidator;

  public function setUp(): void {
    $this->lastValidator = NULL;
    parent::setUp();
  }

  public function tearDown(): void {
    $this->setValidator(NULL);
    parent::tearDown();
  }

  /**
   * Fire ValidateValuesEvent several times - and ensure it conveys the
   * expected data.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testHookData(): void {
    $hookCount = 0;

    // Step 1: `create()` a record for Ms. Alice Alison.
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertSame('create', $e->getActionName());
      $this->assertSame('Alice', $e->records[0]['first_name']);
      $this->assertSame('Alison', $e->records[0]['last_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertSame(1, count($e->diffs));
      $this->assertNull($e->diffs[0]['old']);
      $this->assertSame('Alice', $e->diffs[0]['new']['first_name']);
      $this->assertSame('Alison', $e->diffs[0]['new']['last_name']);

    });
    $created = Contact::create()->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'Alice',
      'last_name' => 'Alison',
    ])->execute()->single();
    $this->assertTrue(is_numeric($created['id']));
    $this->assertSame(1, $hookCount);

    // Step 2: `save()` a couple records for Ms. Alice Alison and Mr. Bob Bobmom.
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertSame('save', $e->getActionName());
      $this->assertSame('Alicia', $e->records[0]['first_name']);
      $this->assertSame('Bob', $e->records[1]['first_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertSame(2, count($e->diffs));
      $this->assertSame('Alice', $e->diffs[0]['old']['first_name']);
      $this->assertSame('Alicia', $e->diffs[0]['new']['first_name']);
      $this->assertNull($e->diffs[1]['old']);
      $this->assertSame('Bob', $e->diffs[1]['new']['first_name']);

    });
    $saved = Contact::save()->setRecords([
      ['id' => $created['id'], 'first_name' => 'Alicia'],
      ['contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Bobmom'],
    ])->execute();
    $this->assertSame(2, $saved->count());
    $this->assertSame(2, $hookCount);

    // Step 3: `update()` a record for Mr. Bob Bobmom
    $this->setValidator(function (ValidateValuesEvent $e) use (&$hookCount) {
      $this->assertWellFormedEvent($e);
      if ($e->getEntityName() !== 'Contact') {
        return;
      }

      $hookCount++;
      $this->assertSame('update', $e->getActionName());
      $this->assertSame('Bobby', $e->records[0]['first_name']);

      $this->assertFalse($e->diffs->isLoaded());
      $this->assertSame(1, count($e->diffs));
      $this->assertSame('Bob', $e->diffs[0]['old']['first_name']);
      $this->assertSame('Bobmom', $e->diffs[0]['old']['last_name']);
      $this->assertSame('Bobby', $e->diffs[0]['new']['first_name']);
    });
    $updated = Contact::update()
      ->setValues(['first_name' => 'Bobby'])
      ->addWhere('last_name', '=', 'Bobmom')
      ->execute();
    $this->assertSame(1, $updated->count());
    $this->assertSame(3, $hookCount);
  }

  public function testRaiseError(): void {
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
    catch (\CRM_Core_Exception $e) {
      $this->assertSame(1, $hookCount);
      $this->assertMatchesRegularExpression(';not sufficiently namey;', $e->getMessage());
      $this->assertMatchesRegularExpression(';tongue twister;', $e->getMessage());
      $this->assertMatchesRegularExpression(';disagree with the spelling;', $e->getMessage());
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
    $this->assertMatchesRegularExpression('/Contact/', $e->getEntityName());
    $this->assertMatchesRegularExpression('/create|save|update/', $e->getActionName());
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
      $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $field);
    }
  }

}
