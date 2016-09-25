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
 * Test class for CRM_Contact_BAO_Relationship
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_RelationshipTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    parent::tearDown();
  }

  /**
   * Test removeRelationshipTypeDuplicates method.
   *
   * @dataProvider getRelationshipTypeDuplicates
   */
  public function testRemoveRelationshipTypeDuplicates($relationshipTypeList, $suffix = NULL, $expected, $description) {
    $result = CRM_Contact_BAO_Relationship::removeRelationshipTypeDuplicates($relationshipTypeList, $suffix);
    $this->assertEquals($expected, $result, "Failure on set '$description'");
  }

  public function getRelationshipTypeDuplicates() {
    $relationshipTypeList = array(
      '1_a_b' => 'duplicate one',
      '1_b_a' => 'duplicate one',
      '2_a_b' => 'two a',
      '2_b_a' => 'two b',
    );
    $data = array(
      array(
        $relationshipTypeList,
        'a_b',
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix a_b',
      ),
      array(
        $relationshipTypeList,
        'b_a',
        array(
          '1_b_a' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix b_a',
      ),
      array(
        $relationshipTypeList,
        NULL,
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix NULL',
      ),
      array(
        $relationshipTypeList,
        NULL,
        array(
          '1_a_b' => 'duplicate one',
          '2_a_b' => 'two a',
          '2_b_a' => 'two b',
        ),
        'With suffix "" (empty string)',
      ),
    );
    return $data;
  }

}
