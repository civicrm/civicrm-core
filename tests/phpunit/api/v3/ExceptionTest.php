<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

}
