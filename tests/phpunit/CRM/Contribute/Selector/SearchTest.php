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
 *  Class CRM_Contribute_Selector_SearchTest
 *
 * @package CiviCRM
 */
class CRM_Contribute_Selector_SearchTest extends CiviUnitTestCase {

  /**
   * CRM-20866 - Soft credit appearance inconsistent in contribution search
   */
  public function testSoftCreditFieldsSelected(): void {
    $queryParams = [['contribution_or_softcredits', '=', 'both_related', 0, 0]];
    $searchSelector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::VIEW);

    list($select, $from, $where, $having) = $searchSelector->getQuery()->query();
    $this->assertStringContainsString('civicrm_contribution_soft.amount', $select);
  }

  /**
   * CRM-20866 - Soft credit appearance inconsistent in contribution search
   */
  public function testSoftCreditFieldNotSelected(): void {
    $queryParams = [['contribution_or_softcredits', '=', 'only_contribs', 0, 0]];
    $searchSelector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::VIEW);

    list($select, $from, $where, $having) = $searchSelector->getQuery()->query();
    $this->assertStringNotContainsString('civicrm_contribution_soft.amount', $select);
  }

}
