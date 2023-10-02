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
namespace Civi\FlexMailer;

/**
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

// For compat w/v4.6 phpunit
require_once 'tests/phpunit/api/v3/JobProcessMailingTest.php';

/**
 * Class ConcurrentDeliveryTest
 *
 * Check that CiviMail batching and concurrency features work as expected.
 * This is a variation on api_v3_JobProcessMailingTest -- but this uses
 * FlexMailer instead of BAO delivery.
 *
 * @group headless
 * @group civimail
 * @see \api_v3_JobProcessMailingTest
 */
class ConcurrentDeliveryTest extends \api_v3_JobProcessMailingTest {

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['org.civicrm.flexmailer']);
    }

    parent::setUp();
  }

  // ---- Boilerplate ----

  // The remainder of this class contains dummy stubs which make it easier to
  // work with the tests in an IDE.

  /**
   * @dataProvider concurrencyExamples
   * @see          _testConcurrencyCommon
   */
  public function testConcurrency($settings, $expectedTallies, $expectedTotal): void {
    parent::testConcurrency($settings, $expectedTallies, $expectedTotal);
  }

  public function testBasic(): void {
    parent::testBasic();
  }

}
