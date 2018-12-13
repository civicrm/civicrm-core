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
 *  Class CRM_Contribute_Selector_SearchTest
 *
 * @package CiviCRM
 */
class CRM_Contribute_Selector_SearchTest extends CiviUnitTestCase {

  /**
   * CRM-20866 - Soft credit appearance inconsistent in contribution search
   */
  public function testSoftCreditFieldsSelected() {
    $queryParams = array(array('contribution_or_softcredits', '=', 'both_related', 0, 0));
    $searchSelector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::VIEW);

    list($select, $from, $where, $having) = $searchSelector->getQuery()->query();
    self::assertContains('civicrm_contribution_soft.amount', $select);
  }

  /**
   * CRM-20866 - Soft credit appearance inconsistent in contribution search
   */
  public function testSoftCreditFieldNotSelected() {
    $queryParams = array(array('contribution_or_softcredits', '=', 'only_contribs', 0, 0));
    $searchSelector = new CRM_Contribute_Selector_Search($queryParams, CRM_Core_Action::VIEW);

    list($select, $from, $where, $having) = $searchSelector->getQuery()->query();
    self::assertNotContains('civicrm_contribution_soft.amount', $select);
  }

}
