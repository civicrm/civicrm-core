<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Include class definitions
 */
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Test whether the case api filters results by status id when searching using contact id.
 *
 * @package CiviCRM_APIv3
 */
class api_v3_FilterCaseByStatus extends CiviCaseTestCase {
  public function testCaseGetByStatus() {
    // Create 2 cases with different status ids.
    $case1 = $this->callAPISuccess('Case', 'create', array(
        'contact_id' => 17,
        'subject' => "Test case 1",
        'case_type_id' => $this->caseTypeId,
        'status_id' => "Open",
        'sequential' => 1,
      ));

    $this->callAPISuccess('Case', 'create', array(
        'contact_id' => 17,
        'subject' => "Test case 2",
        'case_type_id' => $this->caseTypeId,
        'status_id' => "Urgent",
        'sequential' => 1,
      ));

    $result = $this->callAPISuccessGetSingle('Case', array(
        'sequential' => 1,
        'contact_id' => 17,
        'status_id' => "Open",
        ));

    $this->assertEquals($case1['id'], $result['id']);
  }

}
