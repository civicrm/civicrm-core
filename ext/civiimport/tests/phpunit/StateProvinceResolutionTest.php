<?php

use Civi\Api4\Country;
use Civi\Api4\StateProvince;
use Civi\Api4\UserJob;
use Civi\Import\ContributionParser;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class StateProvinceResolutionTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install('org.civicrm.search_kit')
      ->installMe(__DIR__)
      ->apply();
  }

  public function testResolvesAmbiguousStateProvinceAbbreviationWithCountry(): void {
    $canadaID = $this->getCountryIdByIso('CA');
    $abbr = 'ON';
    $expectedStateID = $this->getStateProvinceIdByCountryAndAbbr($canadaID, $abbr);

    $userJobID = $this->createContributionImportUserJob([
      ['name' => 'Contact.address_primary.state_province_id'],
      ['name' => 'Contact.address_primary.country_id'],
    ], 2);

    $parser = new ContributionParser();
    $parser->setUserJobID($userJobID);
    $parser->init();

    // Map state before country to reproduce the ambiguity.
    $mapped = $parser->getMappedRow([$abbr, $canadaID]);
    $this->assertSame($canadaID, (int) $mapped['Contact']['address_primary.country_id']);
    $this->assertSame($expectedStateID, (int) $mapped['Contact']['address_primary.state_province_id']);
  }

  public function testResolvesStateProvinceValuesWhenOptionsNotPreloaded(): void {
    $canadaID = $this->getCountryIdByIso('CA');

    // Even seemingly-unambiguous values (like "BC") generally cannot be resolved by
    // the base option-value transformer because `state_province_id` is a chain-select
    // whose options depend on country and are not preloaded.
    $bcID = $this->getStateProvinceIdByCountryAndAbbr($canadaID, 'BC');

    $userJobID = $this->createContributionImportUserJob([
      ['name' => 'Contact.address_primary.state_province_id'],
      ['name' => 'Contact.address_primary.country_id'],
    ], 2);

    $parser = new ContributionParser();
    $parser->setUserJobID($userJobID);
    $parser->init();

    // Abbreviation.
    $mapped = $parser->getMappedRow(['BC', $canadaID]);
    $this->assertSame($bcID, (int) $mapped['Contact']['address_primary.state_province_id']);

    // Full name.
    $mapped = $parser->getMappedRow(['British Columbia', $canadaID]);
    $this->assertSame($bcID, (int) $mapped['Contact']['address_primary.state_province_id']);
  }

  public function testAcceptsNumericStateProvinceId(): void {
    $canadaID = $this->getCountryIdByIso('CA');
    $bcID = $this->getStateProvinceIdByCountryAndAbbr($canadaID, 'BC');

    $userJobID = $this->createContributionImportUserJob([
      ['name' => 'Contact.address_primary.state_province_id'],
      ['name' => 'Contact.address_primary.country_id'],
    ], 2);

    $parser = new ContributionParser();
    $parser->setUserJobID($userJobID);
    $parser->init();

    $mapped = $parser->getMappedRow([$bcID, $canadaID]);
    $this->assertSame($bcID, (int) $mapped['Contact']['address_primary.state_province_id']);
  }

  public function testResolvesAmbiguousStateProvinceNameWithCountry(): void {
    $canadaID = $this->getCountryIdByIso('CA');
    $usaID = $this->getCountryIdByIso('US');
    $name = 'Test Province Shared Name ' . uniqid('', TRUE);

    $caStateID = $this->createStateProvince($canadaID, $name, $this->randomAbbr());
    $this->createStateProvince($usaID, $name, $this->randomAbbr());

    $userJobID = $this->createContributionImportUserJob([
      ['name' => 'Contact.address_primary.state_province_id'],
      ['name' => 'Contact.address_primary.country_id'],
    ], 2);

    $parser = new ContributionParser();
    $parser->setUserJobID($userJobID);
    $parser->init();

    $mapped = $parser->getMappedRow([$name, $canadaID]);
    $this->assertSame($canadaID, (int) $mapped['Contact']['address_primary.country_id']);
    $this->assertSame($caStateID, (int) $mapped['Contact']['address_primary.state_province_id']);
  }

  public function testDoesNotResolveStateProvinceWithoutCountry(): void {
    $canadaID = $this->getCountryIdByIso('CA');
    $abbr = 'ON';

    $userJobID = $this->createContributionImportUserJob([
      ['name' => 'Contact.address_primary.state_province_id'],
      ['name' => 'Contact.address_primary.country_id'],
    ], 2);

    $parser = new ContributionParser();
    $parser->setUserJobID($userJobID);
    $parser->init();

    // Leave country blank; the resolver should not run.
    $mapped = $parser->getMappedRow([$abbr, '']);
    $this->assertSame($abbr, $mapped['Contact']['address_primary.state_province_id']);
  }

  private function getCountryIdByIso(string $isoCode): int {
    return (int) Country::get(FALSE)
      ->addSelect('id')
      ->addWhere('iso_code', '=', $isoCode)
      ->execute()
      ->single()['id'];
  }

  private function createStateProvince(int $countryID, string $name, string $abbr): int {
    return (int) StateProvince::create(FALSE)
      ->setValues([
        'country_id' => $countryID,
        'name' => $name,
        'abbreviation' => $abbr,
        'is_active' => TRUE,
      ])
      ->execute()
      ->first()['id'];
  }

  private function getStateProvinceIdByCountryAndAbbr(int $countryID, string $abbr): int {
    return (int) StateProvince::get(FALSE)
      ->addSelect('id')
      ->addWhere('country_id', '=', $countryID)
      ->addWhere('abbreviation', '=', $abbr)
      ->execute()
      ->single()['id'];
  }

  private function createContributionImportUserJob(array $importMappings, int $numberOfColumns): int {
    $userJob = UserJob::create(FALSE)->setValues([
      'job_type' => 'contribution_import',
      'status_id:name' => 'draft',
      'metadata' => [
        'DataSource' => [
          'number_of_columns' => $numberOfColumns,
        ],
        'submitted_values' => [
          'contactType' => 'Individual',
          'dataSource' => 'CRM_Import_DataSource_CSV',
        ],
        'import_mappings' => $importMappings,
        'entity_configuration' => [
          'Contribution' => ['action' => 'create'],
          'Contact' => [
            'action' => 'save',
            'contact_type' => 'Individual',
            'dedupe_rule' => ['IndividualUnsupervised'],
          ],
        ],
        'import_options' => [
          'date_format' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ],
        'bundled_actions' => [],
      ],
    ])->execute()->first();

    return (int) $userJob['id'];
  }

  private function randomAbbr(): string {
    // 4 chars max.
    return substr(strtoupper(bin2hex(random_bytes(2))), 0, 4);
  }

}
