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
use Civi\Api4\Navigation;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ExportTest extends Api4TestBase implements TransactionalInterface {

  public function testExportNavigation(): void {
    $sampleNav = Navigation::get(FALSE)
      ->setLimit(1)
      ->execute()->single();

    $export = Navigation::export(FALSE)
      ->setId($sampleNav['id'])
      ->execute()->single();

    sort($export['params']['match']);
    $this->assertEquals(['domain_id', 'name'], $export['params']['match']);
    $this->assertArrayNotHasKey('id', $export['params']['values']);
    $this->assertArrayNotHasKey('domain_id', $export['params']['values']);
    $this->assertArrayHasKey('name', $export['params']['values']);
  }

}
