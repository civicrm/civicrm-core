<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Api4\AfformSubmission;
use Civi\Api4\Contact;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;

/**
 * Completing a stored submission on behalf of the contact who submitted it.
 *
 * @group headless
 */
class AfformProcessSubmissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  use Api4TestTrait;

  private $formName;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->formName = 'mock_manual_form_' . rand(0, 100000);
    $this->setPermissions(['access CiviCRM', 'view all contacts', 'edit all contacts', 'administer afform', 'manage own afform']);

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: false, update: true}" security="FBAC" autofill="user" />
  <fieldset af-fieldset="Individual1" class="af-container">
    <af-field name="job_title" />
  </fieldset>
  <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'title' => 'Manual Processing Test Form',
        'name' => $this->formName,
        'layout' => $layout,
        'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
        'create_submission' => TRUE,
        'manual_processing' => TRUE,
      ])
      ->execute();
  }

  public function tearDown(): void {
    $this->setPermissions(NULL);
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    \CRM_Core_Session::singleton()->set('userID', NULL);
    parent::tearDown();
  }

  /**
   * An admin processing someone else's stored submission must write the submitted
   * values to the submitter's contact, not to their own.
   */
  public function testProcessingActsAsTheSubmitter(): void {
    $submitter = $this->createTestRecord('Individual')['id'];
    $admin = $this->createTestRecord('Individual')['id'];

    $submissionId = $this->submitAs($submitter, 'Proposer');
    // manual_processing defers the writes
    $this->assertNull($this->getJobTitle($submitter));

    $this->login($admin);
    Afform::process()->setName($this->formName)->setSubmissionId($submissionId)->execute();

    $this->assertEquals('Proposer', $this->getJobTitle($submitter));
    $this->assertNull($this->getJobTitle($admin), 'The processing user must not be written to');
  }

  /**
   * The same applies to Submit when it is given a submission id.
   */
  public function testSubmittingWithSidActsAsTheSubmitter(): void {
    $submitter = $this->createTestRecord('Individual')['id'];
    $admin = $this->createTestRecord('Individual')['id'];

    $submissionId = $this->submitAs($submitter, 'Proposer');

    $this->login($admin);
    Afform::submit()->setName($this->formName)
      ->setArgs(['sid' => $submissionId])
      ->setValues(['Individual1' => [['fields' => ['job_title' => 'Proposer']]]])
      ->execute();

    $this->assertEquals('Proposer', $this->getJobTitle($submitter));
    $this->assertNull($this->getJobTitle($admin), 'The processing user must not be written to');
  }

  /**
   * The email-verification link is an anonymous request, so the lookup must still
   * resolve the submitter when the caller is trusted.
   */
  public function testAnonymousVerificationActsAsTheSubmitter(): void {
    $submitter = $this->createTestRecord('Individual')['id'];
    $submissionId = $this->submitAs($submitter, 'Proposer');

    // As CRM_Afform_Page_Verify does after decoding the emailed token
    $this->login(NULL);
    $this->setPermissions([]);
    Afform::process(FALSE)->setName($this->formName)->setSubmissionId($submissionId)->execute();

    $this->assertEquals('Proposer', $this->getJobTitle($submitter));
  }

  /**
   * A submission made by an anonymous user has no contact, so processing it must not
   * fall back to autofilling the contact of whoever processes it.
   */
  public function testProcessingAnonymousSubmissionDoesNotAutofill(): void {
    $admin = $this->createTestRecord('Individual')['id'];

    $submissionId = $this->submitAs(NULL, 'Anon');
    $this->assertNull(
      AfformSubmission::get(FALSE)->addWhere('id', '=', $submissionId)->execute()->single()['contact_id']
    );

    $this->login($admin);
    Afform::process()->setName($this->formName)->setSubmissionId($submissionId)->execute();

    $this->assertNull($this->getJobTitle($admin), 'The processing user must not be written to');
  }

  /**
   * The lookup is permission-checked, so passing someone else's submission id must not
   * let an untrusted request act as that contact.
   */
  public function testCannotActAsAnotherContactWithoutPermission(): void {
    $submitter = $this->createTestRecord('Individual')['id'];
    $attacker = $this->createTestRecord('Individual')['id'];

    $submissionId = $this->submitAs($submitter, 'Proposer');

    // Cannot see the submitter, so cannot read their submission
    $this->login($attacker);
    $this->setPermissions(['access CiviCRM', 'manage own afform']);
    try {
      Afform::process()->setName($this->formName)->setSubmissionId($submissionId)->execute();
      $this->fail('Expected UnauthorizedException');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
    }

    $this->setPermissions(['access CiviCRM', 'view all contacts', 'edit all contacts', 'administer afform', 'manage own afform']);
    $this->assertNull($this->getJobTitle($submitter), 'The submitter must not be written to');
    $this->assertNull($this->getJobTitle($attacker), 'The caller must not be written to either');
  }

  /**
   * Submit as the given contact, returning the id of the stored submission.
   */
  private function submitAs($contactId, string $jobTitle) {
    $this->login($contactId);
    Afform::submit(FALSE)->setName($this->formName)
      ->setValues(['Individual1' => [['fields' => ['job_title' => $jobTitle]]]])
      ->execute();
    return AfformSubmission::get(FALSE)
      ->addWhere('afform_name', '=', $this->formName)
      ->execute()->single()['id'];
  }

  private function login($contactId): void {
    \CRM_Core_Session::singleton()->set('userID', $contactId);
  }

  /**
   * Replace the permission set. CRM_Core_Permission_UnitTests grants everything while
   * its $permissions array is unset, so it has to be assigned, not supplemented.
   */
  private function setPermissions(?array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

  private function getJobTitle($contactId) {
    return Contact::get(FALSE)->addSelect('job_title')->addWhere('id', '=', $contactId)->execute()->single()['job_title'];
  }

}
