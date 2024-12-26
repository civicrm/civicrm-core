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
namespace Civi\FlexMailer\Listener;

/**
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

// For compat w/v4.6 phpunit
//require_once 'tests/phpunit/.php';
use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\FlexMailerTask;

/**
 * Class SimpleFilterTest
 *
 * @group headless
 */
class SimpleFilterTest extends \CiviUnitTestCase {

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['org.civicrm.flexmailer']);
    }

    parent::setUp();
  }

  /**
   * Ensure that the utility `SimpleFilter::byValue()` correctly filters.
   */
  public function testByValue(): void {
    $test = $this;
    list($tasks, $e) = $this->createExampleBatch();

    SimpleFilter::byValue($e, 'text', function ($value, $t, $e) use ($test) {
      $test->assertInstanceOf('Civi\FlexMailer\FlexMailerTask', $t);
      $test->assertInstanceOf('Civi\FlexMailer\Event\ComposeBatchEvent', $e);
      $test->assertTrue(in_array($value, ['eat more cheese', 'eat more ice cream']));
      return preg_replace('/more/', 'thoughtfully considered quantities of', $value);
    });

    $this->assertEquals('eat thoughtfully considered quantities of cheese', $tasks[0]->getMailParam('text'));
    $this->assertEquals('eat thoughtfully considered quantities of ice cream', $tasks[1]->getMailParam('text'));
  }

  /**
   * Ensure that the utility `SimpleFilter::byColumn()` correctly filters.
   */
  public function testByColumn(): void {
    $test = $this;
    list($tasks, $e) = $this->createExampleBatch();

    SimpleFilter::byColumn($e, 'text', function ($values, $e) use ($test) {
      $test->assertInstanceOf('Civi\FlexMailer\Event\ComposeBatchEvent', $e);
      $test->assertEquals('eat more cheese', $values[0]);
      $test->assertEquals('eat more ice cream', $values[1]);
      $test->assertEquals(2, count($values));
      return preg_replace('/more/', 'thoughtfully considered quantities of', $values);
    });

    $this->assertEquals('eat thoughtfully considered quantities of cheese', $tasks[0]->getMailParam('text'));
    $this->assertEquals('eat thoughtfully considered quantities of ice cream', $tasks[1]->getMailParam('text'));
  }

  /**
   * @return array
   */
  protected function createExampleBatch() {
    $tasks = [];
    $tasks[0] = new FlexMailerTask(1000, 2000, 'asdf', 'foo@example.org');
    $tasks[1] = new FlexMailerTask(1001, 2001, 'fdsa', 'bar@example.org');

    $e = new ComposeBatchEvent([], $tasks);

    $tasks[0]->setMailParam('text', 'eat more cheese');
    $tasks[1]->setMailParam('text', 'eat more ice cream');
    return [$tasks, $e];
  }

}
