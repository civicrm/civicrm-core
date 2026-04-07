<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Event;
use Civi\Test\TransactionalInterface;

/**
 * Test case for Afform.prefill and Afform.submit with Event records.
 *
 * @group headless
 */
class AfformEventUsageTest extends AfformUsageTestCase implements TransactionalInterface {

  /**
   * Tests prefilling an event from a template
   */
  public function testEventTemplatePrefill(): void {
    $locBlock1 = $this->createTestRecord('LocBlock', [
      'email_id' => $this->createTestRecord('Email', ['email' => '1@te.st'])['id'],
      'phone_id' => $this->createTestRecord('Phone', ['phone' => '1234567'])['id'],
    ]);
    $locBlock2 = $this->createTestRecord('LocBlock', [
      'email_id' => $this->createTestRecord('Email', ['email' => '2@te.st'])['id'],
      'phone_id' => $this->createTestRecord('Phone', ['phone' => '2234567'])['id'],
    ]);

    $eventTemplate = $this->createTestRecord('Event', [
      'template_title' => 'Test Template Title',
      'title' => 'Test Me',
      'event_type_id' => 1,
      'is_template' => TRUE,
      'loc_block_id' => $locBlock1['id'],
    ]);

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity actions="{create: true, update: true}" type="Event" name="Event1" label="Event 1" security="FBAC" />
  <fieldset af-fieldset="Event1" class="af-container" af-title="Event 1">
    <af-field name="template_id" />
    <af-field name="title" />
    <af-field name="event_type_id" />
    <div af-join="LocBlock">
      <afblock-event-loc-block></afblock-event-loc-block>
    </div>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Prefill from template
    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('entity')
      ->setArgs(['Event1' => [['template_id' => $eventTemplate['id']]]])
      ->execute()->single();
    $this->assertSame('Test Me', $prefill['values'][0]['fields']['title']);
    $this->assertSame($eventTemplate['event_type_id'], $prefill['values'][0]['fields']['event_type_id']);
    $this->assertSame($eventTemplate['id'], $prefill['values'][0]['fields']['template_id']);
    $this->assertArrayNotHasKey('id', $prefill['values'][0]['fields']);
    $this->assertSame('1@te.st', $prefill['values'][0]['joins']['LocBlock'][0]['email_id.email']);
    $this->assertSame('1234567', $prefill['values'][0]['joins']['LocBlock'][0]['phone_id.phone']);

    // Prefill just the locBlock
    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('join')
      ->setArgs(['Event1' => [['joins' => ['LocBlock' => [['id' => $locBlock2['id']]]]]]])
      ->execute()->single();
    $this->assertSame('2@te.st', $prefill['values'][0]['joins']['LocBlock'][0]['email_id.email']);
    $this->assertSame('2234567', $prefill['values'][0]['joins']['LocBlock'][0]['phone_id.phone']);
  }

  /**
   * Test saving & updating
   */
  public function testEventLocationUpdate(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity actions="{create: true, update: true}" type="Event" name="Event1" label="Event 1" security="FBAC" />
  <fieldset af-fieldset="Event1" class="af-container" af-title="Event 1">
    <af-field name="id" />
    <af-field name="title" />
    <af-field name="start_date" />
    <af-field name="end_date" defn="{input_type: 'DisplayOnly'}" />
    <af-field name="event_type_id" />
    <div af-join="LocBlock">
      <af-field name="id" />
      <af-field name="email_id.email" />
      <af-field name="address_id.street_address" />
    </div>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Create a new event with a new location
    $submit = Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Event1' => [
          [
            'fields' => [
              'title' => 'Event Title 1',
              'start_date' => '2021-01-01 00:00:00',
              'end_date' => '2021-01-02 00:00:00',
              'event_type_id' => 1,
            ],
            'joins' => [
              'LocBlock' => [
                [
                  'email_id.email' => 'test1@te.st',
                  'address_id.street_address' => '12345',
                ],
              ],
            ],
          ],
        ],
      ])->execute();

    $event1 = $submit[0]['Event1'][0]['id'];
    $loc1 = $submit[0]['Event1'][0]['joins']['LocBlock'][0]['id'];

    // End date is readonly so should not have been saved.
    $event1Values = $this->getTestRecord('Event', $event1);
    $this->assertNull($event1Values['end_date']);
    Event::update(FALSE)
      ->addWhere('id', '=', $event1)
      ->addValue('end_date', '2021-01-03 00:00:00')
      ->execute();

    // Create a 2nd new event with a new location
    $submit = Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Event1' => [
          [
            'fields' => [
              'title' => 'Event Title 2',
              'start_date' => '2022-01-01 00:00:00',
              'event_type_id' => 1,
            ],
            'joins' => [
              'LocBlock' => [
                [
                  'id' => $loc1,
                ],
              ],
            ],
          ],
        ],
      ])->execute();

    $event2 = $submit[0]['Event1'][0]['id'];
    $this->assertGreaterThan($event1, $event2);
    $this->assertSame($loc1, $submit[0]['Event1'][0]['joins']['LocBlock'][0]['id']);

    // Update event 1 with a new location
    $submit = Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Event1' => [
          [
            'fields' => [
              'id' => $event1,
              'title' => 'Event Title 1 Updated',
            ],
            'joins' => [
              'LocBlock' => [
                [
                  'id' => NULL,
                  'address_id.street_address' => '12345',
                ],
              ],
            ],
          ],
        ],
      ])->execute();

    $this->assertSame($event1, $submit[0]['Event1'][0]['id']);
    $this->assertGreaterThan($loc1, $submit[0]['Event1'][0]['joins']['LocBlock'][0]['id']);

    // End date is readonly so should not have been modified.
    $event1Values = $this->getTestRecord('Event', $event1);
    $this->assertSame('2021-01-03 00:00:00', $event1Values['end_date']);
  }

}
