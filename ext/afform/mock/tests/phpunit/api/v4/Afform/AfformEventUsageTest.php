<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Test\TransactionalInterface;

/**
 * Test case for Afform.prefill and Afform.submit with Event records.
 *
 * @group headless
 */
class AfformEventUsageTest extends AfformUsageTestCase implements TransactionalInterface {

  /**
   * Tests creating a relationship between multiple contacts
   */
  public function testEventTemplatePrefill(): void {
    $eventTemplate = civicrm_api4('Event', 'create', [
      'values' => [
        'template_title' => 'Test Template Title',
        'title' => 'Test Me',
        'event_type_id' => 1,
        'is_template' => TRUE,
      ],
    ])->single();

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity actions="{create: true, update: true}" type="Event" name="Event1" label="Event 1" security="FBAC" />
  <fieldset af-fieldset="Event1" class="af-container" af-title="Event 1">
    <af-field name="template_id" />
    <af-field name="title" />
    <af-field name="event_type_id" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setMatchField('template_id')
      ->setArgs(['Event1' => [$eventTemplate['id']]])
      ->execute()->single();

    $this->assertSame('Test Me', $prefill['values'][0]['fields']['title']);
    $this->assertSame(1, $prefill['values'][0]['fields']['event_type_id']);
    $this->assertSame($eventTemplate['id'], $prefill['values'][0]['fields']['template_id']);
    $this->assertArrayNotHasKey('id', $prefill['values'][0]['fields']);
  }

}
