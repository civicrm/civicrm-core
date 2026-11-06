<?php

use Civi\Api4\Contact;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\Relationship;
use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test the Postal Mailing custom search.
 *
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_PostalMailingTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['legacycustomsearches'])
      ->apply();
  }

  /**
   * Household members are mailed at their household; other relationships don't redirect mail.
   */
  public function testHouseholdMembersAreMailedAtTheirHousehold(): void {
    $household = Contact::create(FALSE)->setValues(['contact_type' => 'Household', 'household_name' => 'Smith household'])->execute()->single()['id'];
    $organization = Contact::create(FALSE)->setValues(['contact_type' => 'Organization', 'organization_name' => 'Charity'])->execute()->single()['id'];
    $householdMember = Contact::create(FALSE)->setValues(['contact_type' => 'Individual', 'first_name' => 'Anne', 'last_name' => 'Smith'])->execute()->single()['id'];
    $volunteer = Contact::create(FALSE)->setValues(['contact_type' => 'Individual', 'first_name' => 'Bob', 'last_name' => 'Jones'])->execute()->single()['id'];

    Relationship::create(FALSE)->setValues([
      'contact_id_a' => $householdMember,
      'contact_id_b' => $household,
      'relationship_type_id:name' => 'Household Member of',
    ])->execute();
    Relationship::create(FALSE)->setValues([
      'contact_id_a' => $volunteer,
      'contact_id_b' => $organization,
      'relationship_type_id:name' => 'Volunteer for',
    ])->execute();

    $groupID = Group::create(FALSE)->setValues(['title' => 'Postal mailing test'])->execute()->single()['id'];
    foreach ([$householdMember, $volunteer] as $contactID) {
      GroupContact::create(FALSE)->setValues(['group_id' => $groupID, 'contact_id' => $contactID, 'status' => 'Added'])->execute();
    }

    $formValues = ['group_id' => $groupID];
    $search = new CRM_Contact_Form_Search_Custom_PostalMailing($formValues);
    $contactIDs = array_map('intval', array_column(CRM_Core_DAO::executeQuery($search->all())->fetchAll(), 'contact_id'));
    sort($contactIDs);
    $expected = [$household, $volunteer];
    sort($expected);
    $this->assertSame($expected, $contactIDs);
  }

}
