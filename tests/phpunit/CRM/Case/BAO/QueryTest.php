<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Case_BAO_QueryTest extends CiviCaseTestCase {

  /**
   * Check that Qill is calculated correctly.
   *
   * CRM-17120 check the qill is still calculated after changing function used
   * to retrieve function.
   *
   * I could not find anyway to actually do this search with the relevant fields
   * as parameters & don't know if they exist as legitimate code or code cruft so
   * this test was the only way I could verify the change.
   *  - case_activity_type
   *  - case_activity_status_id
   *  - case_activity_medium_id
   */
  public function testWhereClauseSingle() {
    $params = [
      0 => [
        0 => 'case_activity_type',
        1 => '=',
        2 => 6,
        3 => 1,
        4 => 0,
      ],
      1 => [
        0 => 'case_activity_status_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ],
      2 => [
        0 => 'case_activity_medium_id',
        1 => '=',
        2 => 1,
        3 => 1,
        4 => 0,
      ],
    ];

    $queryObj = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);
    $this->assertEquals(
      [
        0 => 'Activity Type = Contribution',
        1 => 'Activity Status = Scheduled',
        2 => 'Activity Medium = In Person',
      ],
      $queryObj->_qill[1]
    );
  }

  /**
   * Test the qill for a find cases search.
   */
  public function testFindCasesQuery() {
    $params = [
      [
        0 => 'case_type_id',
        1 => 'IN',
        2 => [1],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_status_id',
        1 => 'IN',
        2 => [1],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_deleted',
        1 => '=',
        2 => 0,
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_owner',
        1 => '=',
        2 => 1,
        3 => 0,
        4 => 0,
      ],
    ];

    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);

    $this->assertEquals(
      [
        0 => [
          0 => 'Case Type(s) In Housing Support',
          1 => 'Case Status(s) In Ongoing',
          2 => 'Case = All Cases',
        ],
      ],
      $query->_qill
    );

    $this->assertEquals(
      [
        0 => [
          0 => 'civicrm_case.case_type_id IN ("1")',
          1 => 'civicrm_case.status_id IN ("1")',
          2 => 'civicrm_case.is_deleted = 0',
          3 => 'civicrm_case_contact.contact_id = contact_a.id',
        ],
      ],
      $query->_where
    );
  }

  /**
   * Tests the advanced search query by searching on related contacts and case type at the same time.
   *
   * Preparation:
   *   Create a contact Contact A
   *   Create another contact Contact B
   *   Create a third contact Contact C
   *   Create a case of type Housing Support for Contact A
   *   On the case assign the role Benefit specialist is to Contact B
   *   Create a second case of type Adult day care referral
   *   On the case assign the role Benefit specialist is to Contact C
   *
   * Searching:
   *   Go to advanced search
   *   Click on View contact as related contact
   *   Select Benefit Specialist as relationship type
   *   Go to tab cases and select Housing Support as case type
   *
   * Expected results
   *   We expect to find contact B and not C.
   *
   * @throws \Exception
   */
  public function testAdvancedSearchWithDisplayRelationshipsAndCaseType(): void {
    $this->markTestIncomplete('temporarily disabled as https://github.com/civicrm/civicrm-core/pull/20002 reverted for now');
    $benefitRelationshipTypeId = $this->callAPISuccess('RelationshipType', 'getvalue', ['return' => 'id', 'name_a_b' => 'Benefits Specialist is']);
    $clientContactID = $this->individualCreate(['first_name' => 'John', 'last_name' => 'Smith']);
    $benefitSpecialist1 = $this->individualCreate(['Individual', 'first_name' => 'Alexa', 'last_name' => 'Clarke']);
    $benefitSpecialist2 = $this->individualCreate(['Individual', 'first_name' => 'Sandra', 'last_name' => 'Johnson']);
    $housingSupportCase = $this->createCase($clientContactID, NULL, ['case_type' => 'housing_support', 'case_type_id' => 1]);
    $adultDayCareReferralCase = $this->createCase($clientContactID, NULL, ['case_type' => 'adult_day_care_referral', 'case_type_id' => 2]);
    $this->callAPISuccess('Relationship', 'create', ['contact_id_a' => $clientContactID, 'contact_id_b' => $benefitSpecialist1, 'relationship_type_id' => $benefitRelationshipTypeId, 'case_id' => $housingSupportCase->id]);
    $this->callAPISuccess('Relationship', 'create', ['contact_id_a' => $clientContactID, 'contact_id_b' => $benefitSpecialist2, 'relationship_type_id' => $benefitRelationshipTypeId, 'case_id' => $adultDayCareReferralCase->id]);

    // Search setup
    $formValues = ['display_relationship_type' => $benefitRelationshipTypeId . '_b_a', 'case_type_id' => 1];
    $params = CRM_Contact_BAO_Query::convertFormValues($formValues, 0, FALSE, NULL, []);
    $isDeleted = in_array(['deleted_contacts', '=', 1, 0, 0], $params);
    $selector = new CRM_Contact_Selector(
      'CRM_Contact_Selector',
      $formValues,
      $params,
      NULL,
      CRM_Core_Action::NONE,
      NULL,
      FALSE,
      'advanced'
    );
    $queryObject = $selector->getQueryObject();
    $sql = $queryObject->query(FALSE, FALSE, FALSE, $isDeleted);
    // Run the search
    $rows = CRM_Core_DAO::executeQuery(implode(' ', $sql))->fetchAll();
    // Check expected results.
    $this->assertCount(1, $rows);
    $this->assertEquals('Alexa', $rows[0]['first_name']);
    $this->assertEquals('Clarke', $rows[0]['last_name']);
  }

}
