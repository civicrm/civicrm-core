<?php

namespace Civi\Searches;

use Civi\Core\HookInterface;
use Civi\Test;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use CRM_Contact_BAO_Contact_Permission;
use CRM_Contact_Form_Search_Custom_RandomSegment;
use CRM_Core_Config;
use CRM_Core_DAO;
use CRM_Core_Session;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class RandomSegmentTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use EntityTrait;

  /**
   * Contact IDs each user may view, keyed by the user's contact ID.
   *
   * @var int[][]
   */
  private array $viewable = [];

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()->install(['legacycustomsearches'])->apply();
  }

  /**
   * @see \CRM_Utils_Hook::aclWhereClause()
   */
  public function hook_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where): void {
    if (!empty($this->viewable[$contactID])) {
      $where = 'contact_a.id IN (' . implode(',', $this->viewable[$contactID]) . ')';
    }
  }

  /**
   * The search returns only the contacts the searching user may view.
   */
  public function testSearchAppliesContactAcl(): void {
    $contacts = [];
    foreach (['searcher', 'colleague', 'shared', 'colleague_only', 'hidden'] as $name) {
      $contacts[$name] = $this->createTestEntity('Individual', ['first_name' => $name, 'last_name' => 'Segment'], $name)['id'];
    }
    $groupID = $this->createTestEntity('Group', ['title' => 'Segment source', 'name' => 'segment_source'])['id'];
    foreach (['shared', 'colleague_only', 'hidden'] as $name) {
      $this->createTestEntity('GroupContact', ['group_id' => $groupID, 'contact_id' => $contacts[$name]], $name);
    }
    $this->viewable = [
      $contacts['searcher'] => [$contacts['shared']],
      $contacts['colleague'] => [$contacts['shared'], $contacts['colleague_only']],
    ];
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Contact_BAO_Contact_Permission::cache($contacts['colleague']);
    CRM_Core_Session::singleton()->set('userID', $contacts['searcher']);

    foreach (['10', '100%'] as $segmentSize) {
      $formValues = ['segmentSize' => $segmentSize, 'includeGroups' => [$groupID]];
      // The class declares none of its properties.
      $errorReporting = error_reporting(E_ALL & ~E_DEPRECATED);
      try {
        $search = new CRM_Contact_Form_Search_Custom_RandomSegment($formValues);
        $found = array_column(CRM_Core_DAO::executeQuery($search->all(0, 0, NULL, FALSE, TRUE))->fetchAll(), 'contact_id');
      }
      finally {
        error_reporting($errorReporting);
      }
      $this->assertEquals([$contacts['shared']], $found, "Segment size $segmentSize");
    }
  }

}
