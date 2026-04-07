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

namespace Civi\Entity;

use Civi\Core\HookInterface;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test the core Entity->getOptions functionality
 * @group headless
 */
class GetOptionsTest extends TestCase implements HeadlessInterface, HookInterface {

  use Api4TestTrait;

  protected static $hookEntity;
  protected static $hookCondition = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  public function setUp(): void {
    parent::setUp();
    self::$hookEntity = NULL;
    self::$hookCondition = [];
  }

  public function testAclHookOptions(): void {
    $this->saveTestRecords('LocationType', [
      'records' => [['name' => 'Restricted']],
      'match' => ['name'],
    ]);

    $address = \Civi::entity('Address');

    // Get options with permission check
    $locationTypes = array_column($address->getOptions('location_type_id', [], TRUE, TRUE), 'name');
    $this->assertContains('Restricted', $locationTypes);

    // Add ACL restriction
    self::$hookEntity = 'LocationType';
    self::$hookCondition = [
      'name' => ['!= "Restricted"'],
    ];

    // Get options with permission check
    $locationTypes = array_column($address->getOptions('location_type_id', [], TRUE, TRUE), 'name');
    $this->assertNotContains('Restricted', $locationTypes);

    // Get options without permission check
    $locationTypes = array_column($address->getOptions('location_type_id'), 'name');
    $this->assertContains('Restricted', $locationTypes);
  }

  /**
   * @implements \CRM_Utils_Hook::selectWhereClause()
   */
  public static function hook_civicrm_selectWhereClause($entity, &$clauses) {
    if ($entity == self::$hookEntity) {
      foreach (self::$hookCondition as $field => $clause) {
        $clauses[$field] = array_merge(($clauses[$field] ?? []), $clause);
      }
    }
  }

}
