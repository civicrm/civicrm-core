<?php
/**
 * @file
 * File for the TestCase class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: ActivityTest.php 31254 2010-12-15 10:09:29Z eileen $
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 * Include class definitions
 */

/**
 *  Test APIv3 civicrm_case_* functions
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_CaseContactTest extends CiviCaseTestCase {
  protected $_params;
  protected $_entity;
  protected $_cid;
  /**
   * Activity ID of created case.
   *
   * @var int
   */
  protected $_caseActivityId;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_entity = 'case';

    parent::setUp();

    $this->_cid = $this->individualCreate();

    $this->_case = $this->callAPISuccess('case', 'create', array(
      'case_type_id' => $this->caseTypeId,
      'subject' => __CLASS__,
      'contact_id' => $this->_cid,
    ));
  }

  public function testCaseContactGet() {
    $result = $this->callAPIAndDocument('CaseContact', 'get', array(
      'contact_id' => $this->_cid,
    ), __FUNCTION__, __FILE__);
    $this->assertEquals($this->_case['id'], $result['id']);
  }

}
