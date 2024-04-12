<?php

/**
 * Class CRM_Contact_BAO_IndividualTest
 * @group headless
 */
class CRM_Contact_BAO_IndividualTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->revertSetting('display_name_format');
    parent::tearDown();
  }

  /**
   * Test case for format() with "null" value dates.
   *
   * See CRM-19123: Merging contacts: blank date fields write as 1970
   */
  public function testFormatNullDates(): void {
    $params = [
      'contact_type' => 'Individual',
      'birth_date' => 'null',
      'deceased_date' => 'null',
    ];
    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEmpty($contact->birth_date);
    $this->assertEmpty($contact->deceased_date);
  }

  /**
   *  Test case to check the formatting of the Display name and Sort name
   *  Standard formatting is assumed.
   */
  public function testFormatDisplayName(): void {
    $params = [
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
      'individual_prefix' => 'Mr.',
      'individual_suffix' => 'Jr.',
    ];
    $contact = new CRM_Contact_DAO_Contact();
    CRM_Contact_BAO_Individual::format($params, $contact);
    $this->assertEquals('Mr. Ben Lee Jr.', $contact->display_name);
    $this->assertEquals('Lee, Ben Jr.', $contact->sort_name);

    // Check with legacy tokens too.
    \Civi::settings()->set('display_name_format', '{contact.individual_prefix}{ }{contact.first_name}{ }{contact.last_name}{ }{contact.individual_suffix}');
    $contact = new CRM_Contact_DAO_Contact();
    CRM_Contact_BAO_Individual::format($params, $contact);
    $this->assertEquals('Mr. Ben Lee Jr.', $contact->display_name);
    $this->assertEquals('Lee, Ben Jr.', $contact->sort_name);
  }

  /**
   *  Testing the use of adding prefix and suffix by id.
   *  Standard Prefixes and Suffixes are assumed part of
   *  the test database
   */
  public function testFormatDisplayNamePrefixesById(): void {

    $params = [
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
      // this is the doctor
      'prefix_id' => 4,
      // and the doctor is a senior
      'suffix_id' => 2,
    ];

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEquals("Dr. Ben Lee Sr.", $contact->display_name);
  }

  /**
   *  Testing the use of adding prefix and suffix by id.
   *  Standard Prefixes and Suffixes are assumed part of
   *  the test database
   */
  public function testFormatDisplayNameNoIndividual(): void {

    $params = [
      'contact_type' => 'Organization',
      'first_name' => 'Ben',
      'last_name' => 'Lee',
    ];

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertNotEquals("Ben Lee", $contact->display_name);
  }

  /**
   *  When no first name or last name are defined, the primary email is used
   */
  public function testFormatDisplayNameOnlyEmail(): void {

    $email['1'] = ['email' => "bleu01@example.com"];
    $email['2'] = ['email' => "bleu02@example.com", 'is_primary' => 1];
    $email['3'] = ['email' => "bleu03@example.com"];

    $params = [
      'contact_type' => 'Individual',
      'email' => $email ,
    ];

    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);

    $this->assertEquals("bleu02@example.com", $contact->display_name);
    $this->assertEquals('bleu02@example.com', $contact->sort_name);

  }

  /**
   * Display Format cases
   */
  public static function displayFormatCases(): array {
    return [
      'Nick name with tilde' => ['{contact.first_name}{ }{contact.last_name}{ ~ }{contact.nick_name}', 'Michael Jackson ~ Mick'],
      'Nick name surrounding brackets' => ['{contact.first_name}{ (}{contact.nick_name}{) }{ }{contact.last_name}', 'Michael (Mick) Jackson'],
      'Nick name surrounding brackets at end' => ['{contact.first_name}{ }{contact.last_name}{ (}{contact.nick_name}{)}', 'Michael Jackson (Mick)'],
      'Empty nick name' => ['{contact.first_name}{ }{contact.last_name}{ ~ }{contact.nick_name}', 'Michael Jackson', ['nick_name' => '']],
      'No Nick Name but Prefix' => ['{contact.individual_prefix}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}{ ~ }{contact.nick_name}', 'Mr. Michael Jackson Jr.', ['nick_name' => '']],
    ];
  }

  /**
   * @dataProvider displayFormatCases
   */
  public function testGenerateDisplayNameCustomFormats(string $displayNameFormat, string $expected, $contactValues = []): void {
    $params = $contactValues + [
      'contact_type' => 'Individual',
      'first_name' => 'Michael',
      'last_name' => 'Jackson',
      'individual_prefix' => 'Mr.',
      'individual_suffix' => 'Jr.',
      'nick_name' => 'Mick',
    ];
    \Civi::settings()->set('display_name_format', $displayNameFormat);
    $contact = new CRM_Contact_DAO_Contact();

    CRM_Contact_BAO_Individual::format($params, $contact);
    $this->assertEquals($expected, $contact->display_name);
    \Civi::settings()->set('display_name_format', \Civi::settings()->getDefault('display_name_format'));
  }

}
