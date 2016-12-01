<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class CRM_Utils_MoneyTest
 * @group headless
 */
class CRM_Utils_MoneyTest extends CiviUnitTestCase {

  /**
   * Test CRM_Utils_Money::format()
   */
  public function testFormat() {
    $formattedAmount = CRM_Utils_Money::format(1000, 'USD');
    $this->assertEquals('$ 1,000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'USD', '%a');
    $this->assertEquals('1,000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'USD', '%C %a');
    $this->assertEquals('USD 1,000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'USD', '%C %a', TRUE);
    $this->assertEquals('1000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'CHE');
    $this->assertEquals('CHE 1,000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'CHE', '%c %a');
    $this->assertEquals('CHE 1,000.00', $formattedAmount);

    $formattedAmount = CRM_Utils_Money::format(1000, 'CHE', '%c %a', TRUE);
    $this->assertEquals('1000.00', $formattedAmount);
  }

}
