<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;

/**
 * Test case for Afform.prefill and Afform.submit with Event records.
 *
 * @group headless
 */
class AfformEventUsageTest extends AfformUsageTestCase {
  use \Civi\Test\Api4TestTrait;

  /**
   * Tests prefilling an event from a template
   */
  public function testEventTemplatePrefill(): void {
    $locBlock1 = $this->createTestEntity('LocBlock', [
      'email_id' => $this->createTestEntity('Email', ['email' => '1@te.st'])['id'],
      'phone_id' => $this->createTestEntity('Phone', ['phone' => '1234567'])['id'],
    ]);
    $locBlock2 = $this->createTestEntity('LocBlock', [
      'email_id' => $this->createTestEntity('Email', ['email' => '2@te.st'])['id'],
      'phone_id' => $this->createTestEntity('Phone', ['phone' => '2234567'])['id'],
    ]);

    $eventTemplate = $this->createTestEntity('Event', [
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

}
