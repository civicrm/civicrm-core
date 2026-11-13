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

use Civi\Api4\WordReplacement;

/**
 * @group headless
 */
class CRM_Report_Form_Contact_RelationshipTest extends CiviReportTestCase {

  public function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    parent::tearDown();
  }

  /**
   * The relationship type filter is described by type label, whatever the filter is called.
   */
  public function testRelationshipTypeFilterStatisticWithTranslatedTitle(): void {
    WordReplacement::save(FALSE)
      ->setRecords([['find_word' => 'Relationship', 'replace_word' => 'Beziehung', 'match_type' => 'exactMatch']])
      ->execute();
    CRM_Core_BAO_WordReplacement::rebuild();
    $typeID = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Relationship', 'relationship_type_id', 'Employee of');

    $filters = $this->callAPISuccess('report_template', 'getstatistics', [
      'report_id' => 'contact/relationship',
      'relationship_type_id_op' => 'in',
      'relationship_type_id_value' => [$typeID . '_a_b'],
    ])['values']['filters'];

    $this->assertContains(['title' => 'Beziehung', 'value' => ts('Is one of') . ' Employee of'], $filters);
  }

}
