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

    Afform::process()
      ->setName($this->formName)
      ->setSubmissionId($submissionId)
      ->execute();
    $this->assertSame('Processed', $this->getSubmissionStatus($submissionId));
    $this->assertSame(1, $this->countSubmittedContacts());
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
