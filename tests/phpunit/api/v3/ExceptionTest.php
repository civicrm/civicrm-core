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
 * Test class for Dedupe exceptions.
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_ExceptionTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  /**
   * Test that when a dedupe exception is created the pair are saved from the merge cache.
   */
  public function testCreatingAnExceptionRemovesFromCachedMergePairs() {
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate();
    $defaultRuleGroupID = $this->callAPISuccess('RuleGroup', 'getvalue', [
      'contact_type' => 'Individual',
      'used' => 'Unsupervised',
      'return' => 'id',
      'options' => ['limit' => 1],
    ]);
    $dupes = $this->callAPISuccess('Dedupe', 'getduplicates', ['rule_group_id' => $defaultRuleGroupID]);
    $this->assertEquals(1, $dupes['count']);
    $this->callAPISuccess('Exception', 'create', ['contact_id1' => $contact1, 'contact_id2' => $contact2]);
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('
      SELECT count(*) FROM civicrm_prevnext_cache
      WHERE (entity_id1 = ' . $contact1 . ' AND entity_id2 = ' . $contact2 . ')
      OR (entity_id1 = ' . $contact2 . ' AND entity_id2 = ' . $contact1 . ')'
    ));
    $dupes = $this->callAPISuccess('Dedupe', 'getduplicates', ['rule_group_id' => $defaultRuleGroupID]);
    $this->assertEquals(0, $dupes['count']);
  }

  /**
   * Per the ajax code there is an expectation the lower id will be contact 1 - ensure api handles this.
   *
   * @throws \Exception
   */
  public function testExceptionSavesLowerIDFirst() {
    $contact1 = $this->individualCreate();
    $contact2 = $this->individualCreate();
    $this->callAPISuccess('Exception', 'create', ['contact_id1' => $contact2, 'contact_id2' => $contact1]);
    $this->callAPISuccessGetSingle('Exception', ['contact_id1' => $contact1, 'contact_id2' => $contact2]);
  }

}
