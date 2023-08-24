<?php

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class api_v4_AfformPrefillUsageTest extends api_v4_AfformUsageTestCase {
  use \Civi\Test\Api4TestTrait;

  /**
   * Ensure that Afform restricts autocomplete results when it's set to use a SavedSearch
   */
  public function testPrefillWithRepeat(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1" af-repeat="Add" min="1" max="3">
    <div class="af-container">
      <af-field name="id"></af-field>
      <af-field name="preferred_communication_method"></af-field>
      <afblock-name-individual></afblock-name-individual>
    </div>
    <div af-join="Email" af-repeat="Add" af-copy="Copy" min="1">
      <afblock-contact-email></afblock-contact-email>
    </div>
    <div af-join="Phone" af-repeat="Add" af-copy="Copy" min="1" max="2">
      <afblock-contact-phone></afblock-contact-phone>
    </div>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $cid = $this->saveTestRecords('Contact', [
      'records' => [
        ['first_name' => 'A', 'last_name' => '_A', 'preferred_communication_method' => [1, 3]],
        ['first_name' => 'B', 'last_name' => '_B', 'email_primary.email' => 'b@afform.test'],
        ['first_name' => 'C', 'last_name' => '_C'],
        ['first_name' => 'D', 'last_name' => '_D', 'email_primary.email' => 'd@afform.test'],
      ],
    ])->column('id');

    $this->saveTestRecords('Phone', [
      'records' => [
        ['contact_id' => $cid[0], 'phone' => '0-1'],
        ['contact_id' => $cid[0], 'phone' => '0-2'],
        ['contact_id' => $cid[0], 'phone' => '0-3'],
        ['contact_id' => $cid[2], 'phone' => '2-1'],
        ['contact_id' => $cid[3], 'phone' => '3-1'],
      ],
    ]);

    $prefill = Civi\Api4\Afform::prefill()
      ->setName($this->formName)
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');

    // Form entity has `max="3"`
    $this->assertCount(3, $prefill['Individual1']['values']);
    $this->assertEquals('A', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertEquals([1, 3], $prefill['Individual1']['values'][0]['fields']['preferred_communication_method']);
    $this->assertEquals('B', $prefill['Individual1']['values'][1]['fields']['first_name']);
    $this->assertEquals('C', $prefill['Individual1']['values'][2]['fields']['first_name']);

    // One email should have been filled
    $this->assertCount(1, $prefill['Individual1']['values'][1]['joins']['Email']);
    $this->assertEquals('b@afform.test', $prefill['Individual1']['values'][1]['joins']['Email'][0]['email']);
    $this->assertEmpty($prefill['Individual1']['values'][0]['joins']['Email']);
    $this->assertEmpty($prefill['Individual1']['values'][2]['joins']['Email']);

    // Phone join has `max="2"`
    $this->assertCount(2, $prefill['Individual1']['values'][0]['joins']['Phone']);
    $this->assertCount(1, $prefill['Individual1']['values'][2]['joins']['Phone']);
    $this->assertEquals('2-1', $prefill['Individual1']['values'][2]['joins']['Phone'][0]['phone']);
    $this->assertEmpty($prefill['Individual1']['values'][1]['joins']['Phone']);

    // Prefill a specific contact for the af-repeat entity
    $prefill = Civi\Api4\Afform::prefill()
      ->setName($this->formName)
      ->setArgs(['Individual1' => [1 => $cid[3]]])
      ->execute()
      ->indexBy('name');
    $this->assertCount(1, $prefill['Individual1']['values']);
    $this->assertEquals('D', $prefill['Individual1']['values'][1]['fields']['first_name']);
    $this->assertEquals('_D', $prefill['Individual1']['values'][1]['fields']['last_name']);
    $this->assertEquals('d@afform.test', $prefill['Individual1']['values'][1]['joins']['Email'][0]['email']);
    $this->assertEquals('3-1', $prefill['Individual1']['values'][1]['joins']['Phone'][0]['phone']);

    // Form entity has `max="3"` so a forth contact (index 3) is out-of-bounds
    $prefill = Civi\Api4\Afform::prefill()
      ->setName($this->formName)
      ->setArgs(['Individual1' => [3 => $cid[0]]])
      ->execute();
    $this->assertTrue(empty($prefill['Individual1']['values']));
  }

}
