<?php

/**
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

use Civi\Import\ActivityParser;
use Civi\Test\Invasive;

/**
 * Test general Import Parser functions
 *
 * @package   CiviCRM
 * @group headless
 * @group import
 */
class CRM_Import_ParserTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->callAPISuccess('Extension', 'install', ['keys' => 'civiimport']);
  }

  /**
   * Provides test cases for contact type guessing
   */
  public function contactTypeProvider(): array {
    return [
      'explicit contact type' => [
        ['contact_type' => 'Organization'],
        'Organization',
      ],
      'individual field 1' => [
        ['first_name' => 'John'],
        'Individual',
      ],
      'individual field 2' => [
        ['formal_title' => 'Sir John'],
        'Individual',
      ],
      // `organization_name` is used to cache the individual's current employer's name
      // So this test case ensures the guesser doesn't get confused by that.
      'individual field 3' => [
        ['organization_name' => 'Smith Corp', 'last_name' => 'Smith'],
        'Individual',
      ],
      'organization field 1' => [
        ['organization_name' => 'ACME Corp'],
        'Organization',
      ],
      'organization field 2' => [
        ['sic_code' => 123],
        'Organization',
      ],
      'household field 1' => [
        ['household_name' => 'Smith Family'],
        'Household',
      ],
      'household field 2' => [
        ['primary_contact_id' => 123],
        'Household',
      ],
      'non-specific field' => [
        ['email' => 'test@example.com'],
        'Individual',
      ],
      'empty values' => [
        [],
        'Individual',
      ],
    ];
  }

  /**
   * @dataProvider contactTypeProvider
   */
  public function testGuessContactType(array $values, string $expectedType): void {
    $activityParser = new ActivityParser();
    $result = Invasive::call([$activityParser, 'guessContactType'], [$values]);
    $this->assertEquals($expectedType, $result);
  }

}
