<?php

use Civi\Api4\RelationshipType;
use Civi\Api4\Tag;

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Case_BAO_QueryTest extends CiviCaseTestCase {

  public function tearDown(): void {
    // Child tags before the tag set they belong to.
    foreach (array_reverse($this->ids['Tag'] ?? []) as $tagID) {
      Tag::delete(FALSE)->addWhere('id', '=', $tagID)->execute();
    }
    parent::tearDown();
  }

  /**
   * Create a case tag and a tag set with one child tag, and a tagged and an untagged case.
   *
   * With the untagged case, a search which ignores the tag filter returns more
   * cases than one which applies it.
   */
  private function createTaggedCases(): void {
    $this->createTestEntity('Tag', ['name' => 'case_tag', 'used_for' => ['civicrm_case']], 'case_tag');
    $this->createTestEntity('Tag', ['name' => 'case_tag_set', 'is_tagset' => TRUE, 'used_for' => ['civicrm_case']], 'case_tag_set');
    $this->createTestEntity('Tag', ['name' => 'case_tag_in_set', 'parent_id' => $this->ids['Tag']['case_tag_set'], 'used_for' => ['civicrm_case']], 'case_tag_in_set');
    $this->ids['Case']['tagged'] = $this->callAPISuccess('Case', 'create', [
      'client_id' => $this->individualCreate([], 'tagged_client'),
      'case_type_id' => 'housing_support',
      'status_id' => 'Open',
      'subject' => 'Case Subject',
    ])['id'];
    $this->ids['Case']['untagged'] = $this->callAPISuccess('Case', 'create', [
      'client_id' => $this->individualCreate([], 'untagged_client'),
      'case_type_id' => 'housing_support',
      'status_id' => 'Open',
      'subject' => 'Case Subject',
    ])['id'];
    foreach (['case_tag', 'case_tag_in_set'] as $tag) {
      $this->createTestEntity('EntityTag', [
        'entity_table' => 'civicrm_case',
        'entity_id' => $this->ids['Case']['tagged'],
        'tag_id' => $this->ids['Tag'][$tag],
      ], $tag);
    }
  }

  /**
   * Run a case search and return the IDs of the matched cases.
   *
   * @param array $params
   *   Query params, as produced by CRM_Contact_BAO_Query::convertFormValues().
   *
   * @return int[]
   */
  private function getMatchedCaseIDs(array $params): array {
    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);
    [, $from, $where] = $query->query();
    $rows = CRM_Core_DAO::executeQuery("SELECT DISTINCT civicrm_case.id $from $where ORDER BY civicrm_case.id")->fetchAll();
    return array_map('intval', array_column($rows, 'id'));
  }

  /**
   * Convert search form values to query params, as the search form does.
   */
  private function convertFormValues(array $formValues): array {
    return CRM_Contact_BAO_Query::convertFormValues($formValues);
  }

  /**
   * Searching by case tag (tag tree) and by a tag in a tag set finds only the tagged case.
   */
  public function testSearchByCaseTags(): void {
    $this->createTaggedCases();
    $tagID = $this->ids['Tag']['case_tag'];
    $tagInSetID = $this->ids['Tag']['case_tag_in_set'];
    $taggedCase = [$this->ids['Case']['tagged']];

    $this->assertEquals($taggedCase, $this->getMatchedCaseIDs($this->convertFormValues(['case_tags' => (string) $tagID])));
    $this->assertEquals($taggedCase, $this->getMatchedCaseIDs($this->convertFormValues(['case_tags' => "$tagID,$tagInSetID"])));
    $this->assertEquals($taggedCase, $this->getMatchedCaseIDs([['case_tags', '=', [$tagID => 1], 0, 0]]));
    $this->assertEquals($taggedCase, $this->getMatchedCaseIDs([['case_taglist', '=', [$this->ids['Tag']['case_tag_set'] => (string) $tagInSetID], 0, 0]]));
  }

  /**
   * SQL in the case_tags search value must not reach the query.
   *
   * The form field is a plain text input, so the submitted value is
   * attacker-controlled. An injected tautology would make the search return
   * every case.
   */
  public function testCaseTagsValueIsNotInjectedIntoQuery(): void {
    $this->createTaggedCases();
    $tagID = $this->ids['Tag']['case_tag'];

    $params = $this->convertFormValues(['case_tags' => "$tagID,0) OR (1=1"]);
    $this->assertWhereDoesNotContain('1=1', $params);
    $this->assertEquals([$this->ids['Case']['tagged']], $this->getMatchedCaseIDs($params));

    $params = $this->convertFormValues(['case_tags' => '0) OR (1=1']);
    $this->assertWhereDoesNotContain('1=1', $params);
    $this->assertEquals([], $this->getMatchedCaseIDs($params));
  }

  /**
   * SQL in a case tag set value must not reach the query.
   *
   * The tag set branch drops non-numeric entries, but used to fall through to
   * the tag tree branch with the unfiltered last entry when none were numeric.
   */
  public function testCaseTaglistValueIsNotInjectedIntoQuery(): void {
    $this->createTaggedCases();
    $params = [['case_taglist', '=', [$this->ids['Tag']['case_tag_set'] => '0) OR (1=1'], 0, 0]];

    $this->assertWhereDoesNotContain('1=1', $params);
    $this->assertEquals([], $this->getMatchedCaseIDs($params));
  }

  /**
   * An injected sub-query against an unrelated table must not reach the search.
   *
   * This is the mechanism behind blind data extraction: if an attacker-supplied
   * sub-query against a table the search never joins is evaluated, its truth
   * leaks through whether rows come back, so the attacker can read arbitrary
   * data one yes/no question at a time, regardless of CiviCRM permissions.
   *
   * The probe here only asks the harmless question "does civicrm_domain id 1
   * exist" (it always does) versus "does id 999999 exist" (it never does). On
   * the fixed code the sub-query never reaches the query and neither probe
   * returns a row; before the fix the two probes return different result sets,
   * which is what proves the read is possible.
   */
  public function testCaseTagsInjectionCannotProbeOtherTables(): void {
    $this->createTaggedCases();
    $trueProbe = $this->convertFormValues(['case_tags' => '0) OR (SELECT id FROM civicrm_domain WHERE id = 1) = 1 OR (1=0']);
    $falseProbe = $this->convertFormValues(['case_tags' => '0) OR (SELECT id FROM civicrm_domain WHERE id = 999999) = 1 OR (1=0']);

    $this->assertWhereDoesNotContain('civicrm_domain', $trueProbe);
    $this->assertWhereDoesNotContain('civicrm_domain', $falseProbe);
    // Neither probe may return a case, and the two may not differ (no leak).
    $this->assertEquals([], $this->getMatchedCaseIDs($trueProbe));
    $this->assertEquals([], $this->getMatchedCaseIDs($falseProbe));
  }

  private function assertWhereDoesNotContain(string $needle, array $params): void {
    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CASE);
    [, , $where] = $query->query();
    $this->assertStringNotContainsString($needle, $where);
  }

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
  public function testWhereClauseSingle(): void {
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
  public function testFindCasesQuery(): void {
    $params = [
      [
        0 => 'case_type_id',
        1 => 'IN',
        2 => [$this->caseTypeId],
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
          0 => "civicrm_case.case_type_id IN (\"$this->caseTypeId\")",
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
    $benefitRelationshipTypeId = RelationshipType::get(FALSE)
      ->addWhere('name_a_b', '=', 'Benefits Specialist is')
      ->execute()->single()['id'];
    $clientContactID = $this->individualCreate(['first_name' => 'John', 'last_name' => 'Smith']);
    $benefitSpecialist1 = $this->individualCreate(['Individual', 'first_name' => 'Alexa', 'last_name' => 'Clarke']);
    $benefitSpecialist2 = $this->individualCreate(['Individual', 'first_name' => 'Sandra', 'last_name' => 'Johnson']);
    $housingSupportCase = $this->createCase($clientContactID, NULL, ['case_type' => 'housing_support', 'case_type_id' => 1]);
    $adultDayCareReferralCase = $this->createCase($clientContactID, NULL, ['case_type' => 'adult_day_care_referral', 'case_type_id' => 2]);
    $this->createTestEntity('Relationship', ['contact_id_a' => $clientContactID, 'contact_id_b' => $benefitSpecialist1, 'relationship_type_id' => $benefitRelationshipTypeId, 'case_id' => $housingSupportCase->id], 'housing_support');
    $this->createTestEntity('Relationship', ['contact_id_a' => $clientContactID, 'contact_id_b' => $benefitSpecialist2, 'relationship_type_id' => $benefitRelationshipTypeId, 'case_id' => $adultDayCareReferralCase->id], 'adult_day_care');

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
    $this->assertSame('Alexa', $rows[0]['first_name']);
    $this->assertSame('Clarke', $rows[0]['last_name']);
  }

}
