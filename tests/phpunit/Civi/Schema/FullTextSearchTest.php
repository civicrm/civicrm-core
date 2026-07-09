<?php

namespace Civi\Schema;

use Civi\Test;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class FullTextSearchTest extends TestCase implements HeadlessInterface {

  /**
   * Keep in sync with Civi\Schema\FullTextSearch::setDefaultIndices
   */

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and
   * sqlFile(). See:
   * https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    \Civi::settings()->revert('search_mysql_fts');
    parent::tearDown();
  }

  public function testTurnOn(): void {
    $service = \Civi::service('civi.schema.fts');
    $settings = \Civi::settings();

    // turn ON
    $settings->set('search_mysql_fts', FALSE);
    $settings->set('search_mysql_fts', TRUE);

    // check contact_name is available
    $contactIndexes = $service->getIndexNamesForEntity('Contact');
    $this->assertTrue(in_array('contact_name', $contactIndexes), 'contact_name in available indexes');

    $this->tryQueryUsingContactName();
    $this->assertTrue(TRUE, 'Query using contact_name index didn\'t crash');
  }

  public function testTurnOff(): void {
    $settings = \Civi::settings();

    // turn OFF
    $settings->set('search_mysql_fts', TRUE);
    $settings->set('search_mysql_fts', FALSE);

    // check we can no longer use it
    try {
      $this->tryQueryUsingContactName();
      $this->fail('Query using contact_name should have failed as we removed it');
    }
    catch (\Throwable $e) {
      $this->assertTrue(TRUE, 'Query using contact_name index failed, as expected');
    }
  }

  protected function tryQueryUsingContactName(): void {
    // columns must match definition from Civi\Schema\FullTextSearch::setDefaultIndices
    $match = \implode(',', ['first_name', 'last_name', 'nick_name', 'organization_name', 'household_name', 'legal_name']);
    // check we can contact_name index defined in ContactType.entityType.php
    \CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contact WHERE MATCH({$match}) AGAINST ('joe')");
  }

}
