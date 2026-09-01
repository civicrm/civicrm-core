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
 * Tests that a merge rewrites serialized contact references, whichever data type
 * they use.
 *
 * CRM_Dedupe_Merger::getMultiValueCidRefs() decides which custom fields hold a
 * *serialized* list of contact ids. Fields it returns are rewritten by
 * moveContactBelongings() with a separator-aware REPLACE(); everything else gets
 * a plain equality update:
 *
 *   UPDATE <table> SET <field> = <mainId> WHERE <field> = <otherId>
 *
 * For a serialized field that statement compares a text column holding bookend
 * separators against an integer. MySQL casts the column and raises 1292
 * "Truncated incorrect DECIMAL value", aborting the merge; without strict mode
 * the comparison silently never matches and the field keeps pointing at the
 * contact that was merged away.
 *
 * The collector used to look only at data_type 'ContactReference', so serialized
 * 'EntityReference' fields pointing at a contact fell into the broken branch.
 * Contact subtypes matter here too: appendCustomContactReferenceFields(), which
 * fills cidRefs(), counts Individual/Household/Organization as contact
 * references, so a field that is in cidRefs() but not in getMultiValueCidRefs()
 * lands in exactly the wrong place.
 *
 * @group headless
 */
class CRM_Dedupe_MergerSerializedEntityReferenceTest extends CiviUnitTestCase {

  /**
   * Custom groups created per test, removed in tearDown.
   *
   * @var array
   */
  private $customGroups = [];

  /**
   * Contacts created per test, removed in tearDown.
   *
   * @var array
   */
  private $contacts = [];

  public function tearDown(): void {
    foreach ($this->customGroups as $groupId) {
      $this->callAPISuccess('CustomGroup', 'delete', ['id' => $groupId]);
    }
    foreach ($this->contacts as $contactId) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $contactId, 'skip_undelete' => TRUE]);
    }
    $this->customGroups = [];
    $this->contacts = [];
    parent::tearDown();
  }

  /**
   * The plain case: EntityReference with fk_entity Contact.
   */
  public function testSerializedEntityReferenceToContactIsRewritten(): void {
    $field = $this->createSerializedReferenceField('EntityReference', 'Contact');
    [$keep, $drop, $holder] = $this->createHolderReferencing($field);

    $this->mergeContacts($keep, $drop);

    $this->assertSame(
      $this->bookend($keep),
      $this->readRawValue($field, $holder),
      'After the merge the reference should point at the surviving contact.'
    );
  }

  /**
   * A contact *subtype* is a contact reference as well.
   *
   * This is the case a narrower `fk_entity = 'Contact'` check misses: the field
   * is in cidRefs() via appendCustomContactReferenceFields() but was absent from
   * getMultiValueCidRefs(), so the merge hit the scalar branch and failed.
   */
  public function testSerializedEntityReferenceToContactSubtypeIsRewritten(): void {
    $field = $this->createSerializedReferenceField('EntityReference', 'Individual');
    [$keep, $drop, $holder] = $this->createHolderReferencing($field);

    $this->mergeContacts($keep, $drop);

    $this->assertSame(
      $this->bookend($keep),
      $this->readRawValue($field, $holder),
      'A serialized reference to Individual should be rewritten like one to Contact.'
    );
  }

  /**
   * The legacy data type must keep working.
   */
  public function testSerializedContactReferenceIsRewritten(): void {
    $field = $this->createSerializedReferenceField('ContactReference', NULL);
    [$keep, $drop, $holder] = $this->createHolderReferencing($field);

    $this->mergeContacts($keep, $drop);

    $this->assertSame(
      $this->bookend($keep),
      $this->readRawValue($field, $holder),
      'Legacy ContactReference fields should still be rewritten.'
    );
  }

  /**
   * An EntityReference to something that is not a contact must be left alone.
   *
   * Guards against widening the clause to every EntityReference: a reference to,
   * say, a Participant has nothing to do with the contacts being merged, and its
   * ids would be corrupted by a blind rewrite.
   */
  public function testSerializedEntityReferenceToOtherEntityIsNotCollected(): void {
    $field = $this->createSerializedReferenceField('EntityReference', 'Participant');

    $method = new ReflectionMethod('CRM_Dedupe_Merger', 'getMultiValueCidRefs');
    $method->setAccessible(TRUE);
    $refs = $method->invoke(NULL);

    $this->assertArrayNotHasKey(
      $field['column_name'],
      $refs[$field['table_name']] ?? [],
      'An EntityReference to a non-contact entity should not count as a contact reference.'
    );
  }

  // ------------------------------------------------------------------
  // Helpers
  // ------------------------------------------------------------------

  /**
   * Creates a custom group on Contact with one serialized reference field.
   *
   * @param string $dataType
   *   'EntityReference' or 'ContactReference'.
   * @param string|null $fkEntity
   *   Target entity for EntityReference; NULL for the legacy type.
   *
   * @return array
   *   ['id', 'column_name', 'table_name']
   */
  private function createSerializedReferenceField(string $dataType, ?string $fkEntity): array {
    $suffix = strtolower($dataType . ($fkEntity ?? 'legacy'));
    $group = $this->callAPISuccess('CustomGroup', 'create', [
      'name' => 'merger_ref_' . $suffix,
      'title' => 'Merger ref ' . $suffix,
      'extends' => 'Contact',
    ]);
    $this->customGroups[] = $group['id'];

    $params = [
      'custom_group_id' => $group['id'],
      'name' => 'ref_' . $suffix,
      'label' => 'Ref ' . $suffix,
      'data_type' => $dataType,
      'html_type' => 'Autocomplete-Select',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
    ];
    if ($fkEntity !== NULL) {
      $params['fk_entity'] = $fkEntity;
    }
    $field = $this->callAPISuccess('CustomField', 'create', $params);

    // column_name and table_name are generated, so read them back rather than
    // predicting them.
    $fieldRecord = $this->callAPISuccess('CustomField', 'getsingle', ['id' => $field['id']]);
    $groupRecord = $this->callAPISuccess('CustomGroup', 'getsingle', ['id' => $group['id']]);

    return [
      'id' => $field['id'],
      'column_name' => $fieldRecord['column_name'],
      'table_name' => $groupRecord['table_name'],
    ];
  }

  /**
   * Creates keep/drop contacts plus a holder whose field references the drop.
   *
   * The value is written with direct SQL so the stored form is exactly the
   * bookend-separated string the merge has to cope with.
   *
   * @param array $field
   *
   * @return array
   *   [keepId, dropId, holderId]
   */
  private function createHolderReferencing(array $field): array {
    // Identical names for keep and drop: a safe-mode merge refuses on a name
    // conflict and then silently does nothing.
    $duplicate = ['first_name' => 'Merge', 'last_name' => 'Duplicate'];
    $keep = $this->individualCreate($duplicate);
    $drop = $this->individualCreate($duplicate);
    $holder = $this->individualCreate(['first_name' => 'Merge', 'last_name' => 'Holder']);
    $this->contacts[] = $keep;
    $this->contacts[] = $drop;
    $this->contacts[] = $holder;

    CRM_Core_DAO::executeQuery(
      "INSERT INTO `{$field['table_name']}` (entity_id, `{$field['column_name']}`) VALUES (%1, %2)",
      [
        1 => [$holder, 'Integer'],
        2 => [$this->bookend($drop), 'String'],
      ]
    );

    return [$keep, $drop, $holder];
  }

  /**
   * Merges $drop into $keep through the public API.
   *
   * Deliberately Contact.merge and not moveAllBelongings() directly: this is the
   * route that fails in practice, and calling the internal method skips the
   * surrounding merge context.
   *
   * Also asserts that the merge actually happened. Without that check the test
   * would pass vacuously whenever the merge is refused — a safe-mode merge does
   * nothing at all when the two contacts conflict, and then the reference is of
   * course still untouched.
   */
  private function mergeContacts(int $keep, int $drop): void {
    $this->callAPISuccess('Contact', 'merge', [
      'to_keep_id' => $keep,
      'to_remove_id' => $drop,
      'mode' => 'safe',
    ]);

    $stillThere = CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_contact WHERE id = %1 AND (is_deleted = 0 OR is_deleted IS NULL)',
      [1 => [$drop, 'Integer']]
    );
    $this->assertEquals(0, $stillThere,
      'Precondition: the merge must actually have run, otherwise this test proves nothing.');
  }

  /**
   * Reads the stored column value without API-side deserialization.
   */
  private function readRawValue(array $field, int $entityId): ?string {
    return CRM_Core_DAO::singleValueQuery(
      "SELECT `{$field['column_name']}` FROM `{$field['table_name']}` WHERE entity_id = %1",
      [1 => [$entityId, 'Integer']]
    );
  }

  /**
   * One id in the bookend-separator form CiviCRM stores serialized values in.
   */
  private function bookend(int $id): string {
    return CRM_Core_DAO::VALUE_SEPARATOR . $id . CRM_Core_DAO::VALUE_SEPARATOR;
  }

}
