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
 * Trait IDOrNameTrait
 *
 * Shared cases for testing that a field which stores the id of another record
 * gives the same result whether it is given the id or the record's name.
 *
 * Purpose
 * -------
 * Many tests pass names rather than ids, e.g. 'financial_type_id' => 'Donation'
 * in APIv3 or 'location_type_id:name' => 'Home' in APIv4. That was made the
 * norm so tests no longer assume ids (the test suite offsets the ids of seeded
 * and test-created records - see Civi\Test\Schema::setAutoIncrement()). The
 * tests that switched no longer check that giving the id still works, and the
 * name handling itself is only exercised indirectly. These cases check both,
 * explicitly, for every such field.
 *
 * Which fields
 * ------------
 * Each field that refers to the id of a record in another table (its
 * pseudoconstant has a 'table') and that tests anywhere give by name, plus
 * PriceSet.extends (components, from a callback). Not included:
 * - fields that store the name itself (e.g. currency, contact_type);
 * - option value fields (e.g. activity_type_id, contribution_status_id): they
 *   store the option's value, which doesn't change when ids are offset;
 * - domain_id, as domain 1 is assumed throughout.
 *
 * Method
 * ------
 * For each case (see getIDOrNameFields()) the test creates two records, one
 * with the field given as the id and one given as the name (APIv3: the name
 * in the field; APIv4: the field:name suffix), and checks that both store the
 * expected id.
 * - The expected id is looked up from the field's metadata (its
 *   pseudoconstant table and name column, see getIDForName()), not with the
 *   APIs' own name handling, so a mistake in that handling can't hide itself.
 * - The stored value is read back with APIv4, whichever API created the
 *   record, which avoids APIv3's inconsistent 'get' output.
 * - Each case names a method returning the record's other values. Those
 *   values are ids (not names), so they work with both APIs; the field being
 *   tested is replaced. The method is called once per record, so each record
 *   gets its own contact, event etc. and unique names. Records that a case
 *   refers to by name (a membership type, profile, price set...) are created
 *   the first time, with APIv4.
 * - The test classes run in a transaction, but creating a custom group or
 *   field changes the schema, which commits it: deleteIDOrNameCustomGroup()
 *   removes those in tearDown(). The custom field cases use their own option
 *   group, as deleting a custom field deletes its option group.
 *
 * Adding a case
 * -------------
 * Add a row to getIDOrNameFields() with the entity, field, a name to give and
 * the method for the other values (add one if needed). If the entity has no
 * APIv3 API, add it to the exclusions in getApi3IDOrNameFields().
 *
 * @see api_v3_IDOrNameTest
 * @see \api\v4\Action\IDOrNameTest
 */
trait CRMTraits_Api_IDOrNameTrait {

  /**
   * Counter to keep the names of the records the cases create unique.
   *
   * @var int
   */
  private $idOrNameCounter = 0;

  /**
   * The cases: fields that tests give by name.
   *
   * @return array
   *   Keyed by 'Entity.field'. Each case is
   *   [entity, field, the name to give (or names, for a multi-valued field),
   *   the method returning the record's other values].
   */
  public static function getIDOrNameFields(): array {
    return [
      'Address.country_id' => ['Address', 'country_id', 'CA', 'getAddressValues'],
      'Address.location_type_id' => ['Address', 'location_type_id', 'Work', 'getAddressValues'],
      'Address.state_province_id' => ['Address', 'state_province_id', 'Saskatchewan', 'getAddressValues'],
      'Case.case_type_id' => ['Case', 'case_type_id', 'id_or_name_case_type', 'getCaseValues'],
      'ContactType.parent_id' => ['ContactType', 'parent_id', 'Individual', 'getContactTypeValues'],
      'Contribution.financial_type_id' => ['Contribution', 'financial_type_id', 'Member Dues', 'getContributionValues'],
      'ContributionPage.financial_type_id' => ['ContributionPage', 'financial_type_id', 'Member Dues', 'getContributionPageValues'],
      'ContributionRecur.financial_type_id' => ['ContributionRecur', 'financial_type_id', 'Member Dues', 'getContributionRecurValues'],
      'ContributionRecur.payment_processor_id' => ['ContributionRecur', 'payment_processor_id', 'id_or_name_processor', 'getContributionRecurValues'],
      'County.state_province_id' => ['County', 'state_province_id', 'Saskatchewan', 'getCountyValues'],
      'CustomField.custom_group_id' => ['CustomField', 'custom_group_id', 'id_or_name_custom_group', 'getCustomFieldValues'],
      'CustomField.option_group_id' => ['CustomField', 'option_group_id', 'id_or_name_option_group', 'getCustomFieldValues'],
      'Email.location_type_id' => ['Email', 'location_type_id', 'Work', 'getEmailValues'],
      'EntityFinancialAccount.financial_account_id' => ['EntityFinancialAccount', 'financial_account_id', 'Deposit Bank Account', 'getEntityFinancialAccountValues'],
      'EntityTag.tag_id' => ['EntityTag', 'tag_id', 'Major_Donor', 'getEntityTagValues'],
      'GroupContact.group_id' => ['GroupContact', 'group_id', 'id_or_name_group', 'getGroupContactValues'],
      'Membership.membership_type_id' => ['Membership', 'membership_type_id', 'id_or_name_membership_type', 'getMembershipValues'],
      'Membership.status_id' => ['Membership', 'status_id', 'Grace', 'getMembershipValues'],
      'MembershipType.financial_type_id' => ['MembershipType', 'financial_type_id', 'Member Dues', 'getMembershipTypeValues'],
      'OptionValue.component_id' => ['OptionValue', 'component_id', 'CiviEvent', 'getOptionValueValues'],
      'OptionValue.option_group_id' => ['OptionValue', 'option_group_id', 'gender', 'getOptionValueValues'],
      'Participant.status_id' => ['Participant', 'status_id', 'Attended', 'getParticipantValues'],
      'PaymentProcessor.payment_processor_type_id' => ['PaymentProcessor', 'payment_processor_type_id', 'Dummy', 'getPaymentProcessorValues'],
      'Phone.location_type_id' => ['Phone', 'location_type_id', 'Work', 'getPhoneValues'],
      'Pledge.financial_type_id' => ['Pledge', 'financial_type_id', 'Member Dues', 'getPledgeValues'],
      'PriceField.price_set_id' => ['PriceField', 'price_set_id', 'id_or_name_price_set', 'getPriceFieldValues'],
      'PriceFieldValue.financial_type_id' => ['PriceFieldValue', 'financial_type_id', 'Member Dues', 'getPriceFieldValueValues'],
      'PriceSet.extends' => ['PriceSet', 'extends', ['CiviEvent', 'CiviContribute'], 'getPriceSetValues'],
      'PriceSet.financial_type_id' => ['PriceSet', 'financial_type_id', 'Member Dues', 'getPriceSetValues'],
      'PriceSetEntity.price_set_id' => ['PriceSetEntity', 'price_set_id', 'id_or_name_price_set', 'getPriceSetEntityValues'],
      'Product.financial_type_id' => ['Product', 'financial_type_id', 'Member Dues', 'getProductValues'],
      'Relationship.relationship_type_id' => ['Relationship', 'relationship_type_id', 'Employee of', 'getRelationshipValues'],
      'Tag.parent_id' => ['Tag', 'parent_id', 'Major_Donor', 'getTagValues'],
      'UFField.uf_group_id' => ['UFField', 'uf_group_id', 'id_or_name_profile', 'getUFFieldValues'],
    ];
  }

  /**
   * The cases for entities that have an APIv3 API.
   *
   * @return array
   */
  public static function getApi3IDOrNameFields(): array {
    return array_filter(self::getIDOrNameFields(), fn($case) => !in_array($case[0], ['County', 'PriceSetEntity'], TRUE));
  }

  /**
   * Get the value a record stores for a field (read with APIv4).
   *
   * @param string $entity
   * @param int $id
   * @param string $field
   *
   * @return mixed
   */
  protected function getStoredIDOrNameValue(string $entity, int $id, string $field) {
    return civicrm_api4($entity, 'get', [
      'select' => [$field],
      'where' => [['id', '=', $id]],
      'checkPermissions' => FALSE,
    ])->first()[$field];
  }

  /**
   * Get the id a field should store for a name, from the field's metadata.
   *
   * This deliberately doesn't use the APIs' own name lookup.
   *
   * @param string $entity
   * @param string $field
   * @param string|array $name
   *
   * @return int|int[]
   */
  protected function getIDForName(string $entity, string $field, $name) {
    if (is_array($name)) {
      return array_map(fn($oneName) => $this->getIDForName($entity, $field, $oneName), $name);
    }
    $pseudoconstant = \Civi::entity($entity)->getField($field)['pseudoconstant'];
    if (empty($pseudoconstant['table'])) {
      // PriceSet.extends lists components.
      return (int) CRM_Core_Component::getComponentID($name);
    }
    $nameColumn = $pseudoconstant['name_column'] ?? 'name';
    $id = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM {$pseudoconstant['table']} WHERE `$nameColumn` = %1", [1 => [$name, 'String']]);
    $this->assertNotEmpty($id, "No $field named $name");
    return $id;
  }

  /**
   * Delete the custom group the custom field cases create.
   *
   * Creating it changes the schema, which isn't rolled back with a transaction.
   */
  protected function deleteIDOrNameCustomGroup(): void {
    civicrm_api4('CustomGroup', 'delete', ['where' => [['name', '=', 'id_or_name_custom_group']], 'checkPermissions' => FALSE]);
    civicrm_api4('OptionGroup', 'delete', ['where' => [['name', '=', 'id_or_name_option_group']], 'checkPermissions' => FALSE]);
  }

  /**
   * Create a record (with APIv4, as setup for a case).
   *
   * @param string $entity
   * @param array $values
   *
   * @return int
   */
  private function createIDOrNameRecord(string $entity, array $values): int {
    return (int) civicrm_api4($entity, 'create', ['values' => $values, 'checkPermissions' => FALSE])->first()['id'];
  }

  /**
   * Get the id of a record, creating it the first time.
   *
   * @param string $entity
   * @param array $values
   *   Must include a unique 'name' (or 'name_a_b').
   *
   * @return int
   */
  private function getOrCreateIDOrNameRecord(string $entity, array $values): int {
    $existing = civicrm_api4($entity, 'get', ['where' => [['name', '=', $values['name']]], 'checkPermissions' => FALSE])->first();
    return $existing ? (int) $existing['id'] : $this->createIDOrNameRecord($entity, $values);
  }

  private function getUniqueIDOrName(string $prefix): string {
    return $prefix . '_' . ++$this->idOrNameCounter;
  }

  private function getIDOrNameContactID(string $contactType = 'Individual'): int {
    $name = $this->getUniqueIDOrName('IDOrName');
    return $this->createIDOrNameRecord('Contact', $contactType === 'Individual'
      ? ['contact_type' => 'Individual', 'first_name' => $name, 'last_name' => 'Test']
      : ['contact_type' => 'Organization', 'organization_name' => $name]);
  }

  private function getIDOrNameMembershipTypeID(): int {
    return $this->getOrCreateIDOrNameRecord('MembershipType', [
      'name' => 'id_or_name_membership_type',
      'member_of_contact_id' => $this->getIDOrNameContactID('Organization'),
      'financial_type_id:name' => 'Member Dues',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
    ]);
  }

  private function getIDOrNamePriceSetID(): int {
    return $this->getOrCreateIDOrNameRecord('PriceSet', [
      'name' => 'id_or_name_price_set',
      'title' => 'ID or name',
      'extends:name' => ['CiviContribute'],
      'financial_type_id:name' => 'Donation',
    ]);
  }

  private function getIDOrNameEventID(): int {
    return $this->createIDOrNameRecord('Event', [
      'title' => $this->getUniqueIDOrName('IDOrName'),
      'event_type_id:name' => 'Conference',
      'start_date' => '2030-01-01',
    ]);
  }

  protected function getAddressValues(): array {
    return [
      'contact_id' => $this->getIDOrNameContactID(),
      'location_type_id' => $this->getLocationTypeID('Home'),
      'street_address' => '1 Test St',
      'country_id' => $this->getIDForName('Address', 'country_id', 'CA'),
    ];
  }

  protected function getCaseValues(): array {
    $this->getOrCreateIDOrNameRecord('CaseType', [
      'name' => 'id_or_name_case_type',
      'title' => 'ID or name',
      'definition' => ['activityTypes' => [['name' => 'Open Case']]],
    ]);
    return [
      'contact_id' => $this->getIDOrNameContactID(),
      'creator_id' => $this->getIDOrNameContactID(),
      'subject' => 'ID or name',
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Case_BAO_Case', 'case_status_id', 'Open'),
    ];
  }

  protected function getContactTypeValues(): array {
    $name = $this->getUniqueIDOrName('IDOrName');
    return ['name' => $name, 'label' => $name];
  }

  protected function getContributionValues(): array {
    return ['contact_id' => $this->getIDOrNameContactID(), 'total_amount' => 10];
  }

  protected function getContributionPageValues(): array {
    return ['title' => $this->getUniqueIDOrName('IDOrName')];
  }

  protected function getContributionRecurValues(): array {
    $this->getOrCreateIDOrNameRecord('PaymentProcessor', [
      'name' => 'id_or_name_processor',
      'payment_processor_type_id:name' => 'Dummy',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
      'domain_id' => 1,
    ]);
    return [
      'contact_id' => $this->getIDOrNameContactID(),
      'amount' => 10,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'start_date' => '2030-01-01',
      'financial_type_id' => $this->getFinancialTypeID('Donation'),
    ];
  }

  protected function getCountyValues(): array {
    return ['name' => $this->getUniqueIDOrName('IDOrName'), 'abbreviation' => 'IDN'];
  }

  protected function getCustomFieldValues(): array {
    // Not a seeded option group: deleting the custom fields deletes it.
    $optionGroupID = $this->getOrCreateIDOrNameRecord('OptionGroup', ['name' => 'id_or_name_option_group', 'title' => 'ID or name']);
    $customGroupID = $this->getOrCreateIDOrNameRecord('CustomGroup', [
      'name' => 'id_or_name_custom_group',
      'title' => 'ID or name',
      'extends' => 'Contact',
    ]);
    return [
      'custom_group_id' => $customGroupID,
      'label' => $this->getUniqueIDOrName('IDOrName'),
      'html_type' => 'Select',
      'data_type' => 'Int',
      'option_group_id' => $optionGroupID,
    ];
  }

  protected function getEmailValues(): array {
    return ['contact_id' => $this->getIDOrNameContactID(), 'email' => 'id_or_name@example.org', 'location_type_id' => $this->getLocationTypeID('Home')];
  }

  protected function getEntityFinancialAccountValues(): array {
    return [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->createIDOrNameRecord('FinancialType', ['name' => $this->getUniqueIDOrName('IDOrName')]),
      'account_relationship' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_EntityFinancialAccount', 'account_relationship', 'Asset Account is'),
    ];
  }

  protected function getEntityTagValues(): array {
    return ['entity_table' => 'civicrm_contact', 'entity_id' => $this->getIDOrNameContactID()];
  }

  protected function getGroupContactValues(): array {
    $this->getOrCreateIDOrNameRecord('Group', ['name' => 'id_or_name_group', 'title' => 'ID or name']);
    return ['contact_id' => $this->getIDOrNameContactID(), 'status' => 'Added'];
  }

  protected function getMembershipValues(): array {
    return [
      'contact_id' => $this->getIDOrNameContactID(),
      'membership_type_id' => $this->getIDOrNameMembershipTypeID(),
      'join_date' => '2030-01-01',
      'status_id' => $this->getMembershipStatusID('New'),
      'is_override' => TRUE,
    ];
  }

  protected function getMembershipTypeValues(): array {
    return [
      'name' => $this->getUniqueIDOrName('IDOrName'),
      'member_of_contact_id' => $this->getIDOrNameContactID('Organization'),
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
    ];
  }

  protected function getOptionValueValues(): array {
    $name = $this->getUniqueIDOrName('IDOrName');
    return ['option_group_id' => $this->getOptionGroupID('activity_type'), 'name' => $name, 'label' => $name];
  }

  protected function getParticipantValues(): array {
    return ['contact_id' => $this->getIDOrNameContactID(), 'event_id' => $this->getIDOrNameEventID(), 'register_date' => '2030-01-01'];
  }

  protected function getPaymentProcessorValues(): array {
    return [
      'name' => $this->getUniqueIDOrName('IDOrName'),
      'payment_processor_type_id' => $this->getPaymentProcessorTypeID('Dummy'),
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
      'domain_id' => 1,
    ];
  }

  protected function getPhoneValues(): array {
    return ['contact_id' => $this->getIDOrNameContactID(), 'phone' => '555-1234', 'location_type_id' => $this->getLocationTypeID('Home')];
  }

  protected function getPledgeValues(): array {
    return [
      'contact_id' => $this->getIDOrNameContactID(),
      'amount' => 100,
      'installments' => 2,
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'start_date' => '2030-01-01',
      'create_date' => '2030-01-01',
      'original_installment_amount' => 50,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'Pending'),
    ];
  }

  protected function getPriceFieldValues(): array {
    $this->getIDOrNamePriceSetID();
    $name = $this->getUniqueIDOrName('id_or_name');
    return ['name' => $name, 'label' => $name, 'html_type' => 'Text'];
  }

  protected function getPriceFieldValueValues(): array {
    $priceFieldID = $this->createIDOrNameRecord('PriceField', [
      'price_set_id' => $this->getIDOrNamePriceSetID(),
      'name' => $this->getUniqueIDOrName('id_or_name'),
      'label' => 'ID or name',
      'html_type' => 'Radio',
    ]);
    $name = $this->getUniqueIDOrName('id_or_name');
    return ['price_field_id' => $priceFieldID, 'name' => $name, 'label' => $name, 'amount' => 1];
  }

  protected function getPriceSetValues(): array {
    $name = $this->getUniqueIDOrName('id_or_name');
    return [
      'name' => $name,
      'title' => $name,
      'extends' => [CRM_Core_Component::getComponentID('CiviContribute')],
      'financial_type_id' => $this->getFinancialTypeID('Donation'),
    ];
  }

  protected function getPriceSetEntityValues(): array {
    $this->getIDOrNamePriceSetID();
    return ['entity_table' => 'civicrm_event', 'entity_id' => $this->getIDOrNameEventID()];
  }

  protected function getProductValues(): array {
    return ['name' => $this->getUniqueIDOrName('IDOrName'), 'price' => 1];
  }

  protected function getRelationshipValues(): array {
    return ['contact_id_a' => $this->getIDOrNameContactID(), 'contact_id_b' => $this->getIDOrNameContactID('Organization')];
  }

  protected function getTagValues(): array {
    return ['name' => $this->getUniqueIDOrName('IDOrName')];
  }

  protected function getUFFieldValues(): array {
    $this->getOrCreateIDOrNameRecord('UFGroup', ['name' => 'id_or_name_profile', 'title' => 'ID or name', 'group_type' => 'Individual,Contact']);
    // A profile can only have each field once.
    return [
      'field_name' => ['first_name', 'last_name', 'middle_name', 'nick_name'][$this->idOrNameCounter++ % 4],
      'field_type' => 'Individual',
      'label' => 'ID or name',
    ];
  }

}
