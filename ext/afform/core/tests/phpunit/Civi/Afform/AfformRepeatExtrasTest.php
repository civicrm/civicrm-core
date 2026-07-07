<?php
namespace Civi\Afform;

use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Api4\Afform;
use Civi\Api4\AfformSubmissionData;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;

/**
 * Extra fields inside a repeating fieldset are handed to submit subscribers as a
 * per-slot `extras` bag alongside each slot's `fields`. Extra fields outside a
 * repeater keep flowing through the form-level "extra" record and are saved with
 * the submission as before.
 *
 * @group headless
 */
class AfformRepeatExtrasTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {
  use Api4TestTrait;

  protected $formName;

  /**
   * Records seen by the civi.afform.submit listener, keyed by entity name.
   *
   * @var array
   */
  protected $capturedRecords;

  /**
   * The listener registered on civi.afform.submit, kept so tearDown can remove it.
   *
   * @var callable|null
   */
  protected $submitListener;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
    $this->formName = 'mock_repeat_extras_form_' . rand(0, 100000);
    $this->capturedRecords = [];
  }

  public function tearDown(): void {
    if ($this->submitListener) {
      \Civi::dispatcher()->removeListener('civi.afform.submit', $this->submitListener);
      $this->submitListener = NULL;
    }
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    parent::tearDown();
  }

  /**
   * Each slot of a repeating fieldset carries its own extras bag through to the
   * submit handlers.
   *
   * Per-slot extras are delivered to civi.afform.submit subscribers, not exposed
   * as queryable AfformSubmissionData fields, so they are observed through a
   * listener standing in for a real subscriber rather than read back from the API.
   */
  public function testRepeatingFieldsetPassesExtrasPerSlot(): void {
    $this->createRepeatingForm();
    $this->captureSubmitRecords();

    Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => 'John'],
            'extras' => ['referral_note' => 'Met at the conference'],
          ],
          [
            'fields' => ['first_name' => 'Jane'],
            'extras' => ['referral_note' => 'Walk-in'],
          ],
        ],
      ])
      ->execute();

    $individualRecords = $this->capturedRecords['Individual1'];
    $this->assertCount(2, $individualRecords);
    $this->assertSame(['referral_note' => 'Met at the conference'], $individualRecords[0]['extras']);
    $this->assertSame(['referral_note' => 'Walk-in'], $individualRecords[1]['extras']);
  }

  /**
   * Extras that arrive as something other than an array are dropped even on a
   * repeating fieldset, so subscribers can trust `$record['extras']` is an array.
   */
  public function testNonArrayExtrasAreStripped(): void {
    $this->createRepeatingForm();
    $this->captureSubmitRecords();

    Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => 'John'],
            'extras' => 'not-an-array',
          ],
        ],
      ])
      ->execute();

    $this->assertArrayNotHasKey('extras', $this->capturedRecords['Individual1'][0]);
  }

  /**
   * The per-slot `extras` bag only applies to a repeating fieldset. On a
   * non-repeating entity, afField.component.js binds an extra field to the
   * form-level "extra" record, never to `<entity>.extras` - so a well-formed
   * `extras` array submitted against a non-repeating entity is a shape the form
   * UI never produces, and it is dropped rather than passed to submit handlers.
   * (Extra data that legitimately belongs outside a repeat travels through the
   * form-level "extra" record and is saved - see
   * testFormLevelExtrasAreSavedWithTheSubmission.)
   */
  public function testEntityLevelExtrasDroppedForNonRepeatingEntity(): void {
    $this->createRepeatingForm();
    $this->captureSubmitRecords();

    Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues([
        'Organization1' => [
          [
            'fields' => ['organization_name' => 'Acme'],
            'extras' => ['referral_note' => 'not honored outside a repeat'],
          ],
        ],
      ])
      ->execute();

    $this->assertArrayNotHasKey('extras', $this->capturedRecords['Organization1'][0]);
  }

  /**
   * Extra fields outside a repeater are collected on the form-level "extra"
   * record, not on a per-slot bag. The new per-slot handling must not disturb
   * that: their values are still saved as part of the submission.
   */
  public function testFormLevelExtrasAreSavedWithTheSubmission(): void {
    $this->createFormLevelExtraForm();
    $this->createLoggedInUser(['first_name' => 'Current', 'last_name' => 'User']);

    Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          ['fields' => ['first_name' => 'John']],
        ],
        'extra' => [
          'fields' => ['campaign_source' => 'newsletter'],
        ],
      ])
      ->execute();

    $record = AfformSubmissionData::get(FALSE)
      ->setAfformName($this->formName)
      ->execute()
      ->single();
    $this->assertEquals('newsletter', $record['extra.campaign_source']);
  }

  /**
   * Both mechanisms can be used on the same form in one submission: per-slot
   * `extras` inside a repeating fieldset and a form-level `extra` field. Each
   * reaches its own destination without disturbing the other - the per-slot bags
   * arrive on the repeat records for submit subscribers, and the form-level extra
   * is saved with the submission.
   */
  public function testPerSlotAndFormLevelExtrasCoexistInOneSubmission(): void {
    $this->createRepeatingAndFormLevelExtraForm();
    $this->createLoggedInUser(['first_name' => 'Current', 'last_name' => 'User']);
    // Capture the records but let the submission save so the form-level extra persists.
    $this->captureSubmitRecords(FALSE);

    Afform::submit(FALSE)
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => ['first_name' => 'John'],
            'extras' => ['referral_note' => 'Met at the conference'],
          ],
          [
            'fields' => ['first_name' => 'Jane'],
            'extras' => ['referral_note' => 'Walk-in'],
          ],
        ],
        'extra' => [
          'fields' => ['campaign_source' => 'newsletter'],
        ],
      ])
      ->execute();

    // Per-slot extras reached the submit handlers for each repeat slot.
    $individualRecords = $this->capturedRecords['Individual1'];
    $this->assertSame(['referral_note' => 'Met at the conference'], $individualRecords[0]['extras']);
    $this->assertSame(['referral_note' => 'Walk-in'], $individualRecords[1]['extras']);

    // The form-level extra was saved alongside them.
    $record = AfformSubmissionData::get(FALSE)
      ->setAfformName($this->formName)
      ->execute()
      ->single();
    $this->assertEquals('newsletter', $record['extra.campaign_source']);
  }

  /**
   * A repeating Individual fieldset holding a normal field and an "extra" field
   * (a nameless af-field, stored per repeat slot), plus a non-repeating
   * Organization fieldset used to check that a per-slot bag is not honored
   * outside a repeat.
   */
  protected function createRepeatingForm(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <af-entity type="Organization" name="Organization1" label="Organization 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-repeat="Add">
    <af-field name="first_name" />
    <af-field defn="{name: 'referral_note', input_type: 'Text', label: 'Referral Note'}" />
  </fieldset>
  <fieldset af-fieldset="Organization1" class="af-container">
    <af-field name="organization_name" />
  </fieldset>
  <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
    $this->createForm($layout);
  }

  /**
   * A non-repeating Individual fieldset plus a form-level "extra" field (declared
   * outside any fieldset). The form stores submissions so the extra value can be
   * read back.
   */
  protected function createFormLevelExtraForm(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container">
    <af-field name="first_name" />
  </fieldset>
  <af-field defn="{name: 'campaign_source', input_type: 'Text', label: 'Campaign Source'}" />
  <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
    $this->createForm($layout, ['create_submission' => TRUE]);
  }

  /**
   * A form that uses both mechanisms: a repeating Individual fieldset with a
   * per-slot extra field, and a form-level "extra" field outside it. Stores
   * submissions so the form-level extra can be read back.
   */
  protected function createRepeatingAndFormLevelExtraForm(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-repeat="Add">
    <af-field name="first_name" />
    <af-field defn="{name: 'referral_note', input_type: 'Text', label: 'Referral Note'}" />
  </fieldset>
  <af-field defn="{name: 'campaign_source', input_type: 'Text', label: 'Campaign Source'}" />
  <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
    $this->createForm($layout, ['create_submission' => TRUE]);
  }

  protected function createForm(string $layout, array $extraValues = []): void {
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'title' => 'Test Repeat Extras Form',
        'name' => $this->formName,
        'layout' => $layout,
        'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      ] + $extraValues)
      ->execute();
  }

  /**
   * Capture the records for each entity as they reach submit handlers.
   *
   * @param bool $stopPropagation
   *   When TRUE (the default) the event is halted so nothing is written to the
   *   database - enough to inspect what handlers receive. Pass FALSE to let the
   *   submission save, e.g. when a later assertion reads it back.
   */
  protected function captureSubmitRecords(bool $stopPropagation = TRUE): void {
    $this->submitListener = function (AfformSubmitEvent $event) use ($stopPropagation) {
      $this->capturedRecords[$event->getEntityName()] = $event->getRecords();
      if ($stopPropagation) {
        $event->stopPropagation();
      }
    };
    // Higher priority than the core save handler (priority 0) so it runs first.
    \Civi::dispatcher()->addListener('civi.afform.submit', $this->submitListener, 1000);
  }

}
