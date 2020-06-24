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
 * Test APIv3 ability to join across multiple entities
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_EntityJoinTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testJoinEmailToContact() {
    $first = 'firstthisisatest';
    $last = 'lastthisisatest';
    $org = $this->organizationCreate(['organization_name' => 'Employer of one']);
    $person1 = $this->individualCreate(['employer_id' => $org, 'first_name' => $first, 'last_name' => $last, 'gender_id' => 1]);
    $person2 = $this->individualCreate([], 1);
    $result = $this->callAPISuccessGetSingle('Email', [
      'return' => 'contact_id.employer_id.display_name,contact_id.gender_id.label',
      'contact_id.last_name' => $last,
      'contact_id.first_name' => $first,
    ]);
    $this->assertEquals('Employer of one', $result['contact_id.employer_id.display_name']);
    $this->assertEquals('Female', $result['contact_id.gender_id.label']);
  }

}
