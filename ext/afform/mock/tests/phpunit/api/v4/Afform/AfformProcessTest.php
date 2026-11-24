<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\AfformSubmission;
use Civi\Api4\Contact;

/**
 * Test case for Afform.process.
 *
 * @group headless
 */
class AfformProcessTest extends AfformUsageTestCase {

  private const LAYOUT = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: false}" security="FBAC" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
  </fieldset>
  <button class="af-button btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

  private string $otherFormName;

  private string $firstName;

  public function setUp(): void {
    parent::setUp();
    $this->otherFormName = $this->formName . 'other';
    $this->firstName = 'Pending' . $this->formName;
  }

  public function tearDown(): void {
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->otherFormName)
      ->execute();
    AfformSubmission::delete(FALSE)
      ->addWhere('afform_name', '=', $this->formName)
      ->execute();
    Contact::delete(FALSE)
      ->addWhere('first_name', '=', $this->firstName)
      ->setUseTrash(FALSE)
      ->execute();
    parent::tearDown();
  }

  public function testProcessOnlyAcceptsSubmissionsOfTheNamedForm(): void {
    $this->useValues([
      'layout' => self::LAYOUT,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
      'manual_processing' => TRUE,
    ]);
    $this->useValues([
      'name' => $this->otherFormName,
      'layout' => self::LAYOUT,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => [['fields' => ['first_name' => $this->firstName]]]])
      ->execute();
    $submissionId = AfformSubmission::get(FALSE)
      ->addWhere('afform_name', '=', $this->formName)
      ->execute()->single()['id'];

    Afform::process()
      ->setName($this->otherFormName)
      ->setSubmissionId($submissionId)
      ->execute();
    $this->assertSame('Pending', $this->getSubmissionStatus($submissionId));
    $this->assertSame(0, $this->countSubmittedContacts());

    $submitted = [];
    $listener = function (\Civi\Afform\Event\AfformSubmittedEvent $event) use (&$submitted) {
      $submitted[] = $event->getEntityIds('Individual1')[0]['id'];
    };
    \Civi::dispatcher()->addListener('civi.afform.submitted', $listener);
    try {
      Afform::process()
        ->setName($this->formName)
        ->setSubmissionId($submissionId)
        ->execute();
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.afform.submitted', $listener);
    }
    $this->assertSame('Processed', $this->getSubmissionStatus($submissionId));
    $this->assertSame(1, $this->countSubmittedContacts());
    $contactId = Contact::get(FALSE)->addWhere('first_name', '=', $this->firstName)->execute()->single()['id'];
    $this->assertSame([$contactId], $submitted);
  }

  /**
   * A failure saving one entity leaves nothing saved and the submission pending, so it
   * can be processed again.
   */
  public function testProcessFailureRollsBack(): void {
    $this->useValues([
      'layout' => <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" actions="{create: true, update: false}" security="FBAC" />
  <af-entity type="Activity" name="Activity1" actions="{create: true, update: false}" security="FBAC" data="{'activity_type_id:name': 'Meeting', source_contact_id: 'Individual1'}" />
  <fieldset af-fieldset="Individual1"><af-field name="first_name" /></fieldset>
  <fieldset af-fieldset="Activity1"><af-field name="subject" /></fieldset>
</af-form>
EOHTML,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
      'manual_processing' => TRUE,
    ]);
    Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [['fields' => ['first_name' => $this->firstName]]],
        'Activity1' => [['fields' => ['subject' => 'Fails']]],
      ])
      ->execute();
    $submissionId = AfformSubmission::get(FALSE)
      ->addWhere('afform_name', '=', $this->formName)
      ->execute()->single()['id'];

    $failActivity = function ($event) {
      if ($event instanceof \Civi\Core\Event\PreEvent && $event->entity === 'Activity') {
        throw new \CRM_Core_Exception('Simulated failure');
      }
    };
    \Civi::dispatcher()->addListener('hook_civicrm_pre', $failActivity);
    try {
      Afform::process()
        ->setName($this->formName)
        ->setSubmissionId($submissionId)
        ->execute();
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertSame('Simulated failure', $e->getMessage());
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_pre', $failActivity);
    }
    $this->assertSame('Pending', $this->getSubmissionStatus($submissionId));
    $this->assertSame(0, $this->countSubmittedContacts());
  }

  private function getSubmissionStatus(int $submissionId): string {
    return AfformSubmission::get(FALSE)
      ->addSelect('status_id:name')
      ->addWhere('id', '=', $submissionId)
      ->execute()->single()['status_id:name'];
  }

  private function countSubmittedContacts(): int {
    return Contact::get(FALSE)
      ->selectRowCount()
      ->addWhere('first_name', '=', $this->firstName)
      ->execute()->count();
  }

}
