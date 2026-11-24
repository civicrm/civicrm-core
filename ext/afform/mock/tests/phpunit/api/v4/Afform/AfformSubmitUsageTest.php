<?php
namespace Civi\ext\afform\mock\tests\phpunit\api\v4\Afform;

use api\v4\Afform\AfformUsageTestCase;
use Civi\Api4\Afform;
use Civi\Api4\AfformSubmission;
use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class AfformSubmitUsageTest extends AfformUsageTestCase {

  public function testSubmitWithDisplayOnlyFields(): void {

    $layout = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC"  />
        <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
          <af-field name="first_name" defn="{input_type: 'DisplayOnly', required: true}" />
          <af-field name="last_name" />
        </fieldset>
        <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
        <af-field defn="{input_type: 'Text', name: 'test_field'}" />
      </af-form>
    EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $cid = $this->saveTestRecords('Individual', [
      'records' => [
        ['first_name' => 'One', 'last_name' => 'Person'],
      ],
    ])->column('id');

    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => $cid])
      ->execute()
      ->indexBy('name');
    $this->assertCount(1, $prefill['Individual1']['values']);
    $this->assertSame('One', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertSame('Person', $prefill['Individual1']['values'][0]['fields']['last_name']);

    // Submit with empty first_name: should not hit a validation error because DisplayOnly fields cannot be required
    $submission = [
      ['fields' => ['last_name' => 'Person']],
    ];
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => $submission])
      ->setArgs(['Individual1' => $cid])
      ->execute();
    $this->assertSame($cid[0], $result[0]['Individual1'][0]['id']);
  }

  public function testSubmitWithTokensInConfirmationMessage(): void {
    $layout = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{source: 'Hello'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC"  />
        <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
          <af-field name="first_name" />
          <af-field name="last_name" />
          <div af-join="Email" actions="{update: true, delete: true}" data="{location_type_id: 1}">
            <div class="af-container af-layout-inline">
              <af-field name="email" />
            </div>
          </div>
          <div af-join="Email" actions="{update: true, delete: true}" data="{location_type_id: 2}">
            <div class="af-container af-layout-inline">
              <af-field name="email" />
            </div>
          </div>
        </fieldset>
        <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'confirmation_type' => 'show_confirmation_message',
      'confirmation_message' => '
        <p>Thank you "[Individual1.0.first_name] [Individual1.0.last_name]".</p>
        <p>You are now registered as [Individual1.0.source] ID_[Individual1.0.id].</p>
        <p>Your home email is [Individual1.0.Email.0.email] (ID [Individual1.0.Email.0.id]).</p>
        <p>Your work email is [Individual1.0.Email.1.email] (ID [Individual1.0.Email.1.id]).</p>
      ',
    ]);

    // Submit with new contact
    $submission = [
      'Individual1' => [
        0 => [
          'fields' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
          ],
          'joins' => [
            'Email' => [
              0 => [
                'email' => 'jane@home.com',
              ],
              1 => [
                'email' => 'jane@work.com',
              ],
            ],
          ],
        ],
      ],
    ];

    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute();

    $contactId = $result[0]['Individual1'][0]['id'];
    $emails = \Civi\Api4\Email::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->execute()
      ->indexBy('location_type_id');

    $expectedLines = [
      "<p>Thank you \"Jane Doe\".</p>",
      "<p>You are now registered as Hello ID_{$contactId}.</p>",
      "<p>Your home email is jane@home.com (ID {$emails[1]['id']}).</p>",
      "<p>Your work email is jane@work.com (ID {$emails[2]['id']}).</p>",
    ];
    foreach ($expectedLines as $line) {
      $this->assertStringContainsString($line, $result[0]['message']);
    }
  }

  public function testSubmitWithRecaptcha(): void {
    \Civi::settings()->set('recaptchaPrivateKey', 'test_private_key');
    \Civi::settings()->set('recaptchaPublicKey', 'test_public_key');

    $layout = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{source: 'Hello'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC"  />
        <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
          <af-field name="first_name" />
          <af-field name="last_name" />
        </fieldset>
        <crm-recaptcha2 />
        <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with recaptcha2 token in the extra entity (which replicates what FormBuilder does)
    $submission = [
      'Individual1' => [
        ['fields' => ['first_name' => 'Jane', 'last_name' => 'Doe']],
      ],
      'extra' => [
        'recaptcha2' => '',
      ],
    ];

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Please go back and complete the CAPTCHA');

    Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute();
  }

  public function testSubmitWithRepeatMinZero(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
  </fieldset>
  <div af-join="Email" min="0" af-repeat="Add">
    <af-field name="email" defn="{required: true}" />
  </div>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Submit with zero items for the Email block: should succeed.
    $submissionZeroItems = [
      'Individual1' => [
        [
          'fields' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
          'joins' => [
            'Email' => [],
          ],
        ],
      ],
    ];

    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues($submissionZeroItems)
      ->execute();

    $this->assertNotEmpty($result[0]['Individual1'][0]['id']);

    // Submit with 1 empty item for the Email block: should trigger validation error.
    $submissionOneEmptyItem = [
      'Individual1' => [
        [
          'fields' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
          'joins' => [
            'Email' => [[]],
          ],
        ],
      ],
    ];

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($submissionOneEmptyItem)
        ->execute();
      $this->fail('Should have thrown validation exception');
    }
    catch (\CRM_Core_Exception $e) {
    }
    $this->assertSame('Email is a required field.', $e->getMessage());
  }

  /**
   * A Hidden field's afform_default can be a relative date filter value (e.g. "this.day"
   * for Today); the entity's create action needs the absolute date it represents, not
   * the filter string itself.
   */
  public function testSubmitConvertsRelativeDateDefaultOnHiddenField(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC"  />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
    <af-field name="birth_date" defn="{input_type: 'Hidden', afform_default: 'this.day'}" />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid('RelativeDate');
    $submission = [
      'Individual1' => [
        ['fields' => ['first_name' => 'Jane', 'last_name' => $lastName]],
      ],
    ];
    // Pin the DB session's clock so CURDATE() (what relativeToSql() resolves "this.day"
    // against) is deterministic, rather than racing a real midnight rollover.
    \CRM_Core_DAO::executeQuery('SET @@session.timestamp = %1', [1 => [strtotime('2020-08-01 01:00:00'), 'Integer']]);
    try {
      $result = Afform::submit()
        ->setName($this->formName)
        ->setValues($submission)
        ->execute();
    }
    finally {
      \CRM_Core_DAO::executeQuery('SET @@session.timestamp = DEFAULT');
    }

    $contactId = $result[0]['Individual1'][0]['id'];
    $birthDate = \Civi\Api4\Contact::get(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addSelect('birth_date')
      ->execute()->single()['birth_date'];
    $this->assertSame('2020-08-01', $birthDate);
  }

  /**
   * Activity fields whose hidden defaults complete the record without user input.
   */
  private const COMPLETE_ACTIVITY_FIELDS = '<af-field name="subject" />' .
    '<af-field name="activity_type_id:name" defn="{input_type: \'Hidden\', afform_default: \'Meeting\'}" />' .
    '<af-field name="source_contact_id" defn="{input_type: \'Hidden\', afform_default: \'Individual1\'}" />';

  public static function getSaveFailures(): array {
    return [
      'database error' => [new \Civi\Core\Exception\DBQueryException('Simulated database failure')],
      'api error' => [new \CRM_Core_Exception('Simulated api failure')],
    ];
  }

  /**
   * An error saving any entity fails the submission and leaves nothing saved,
   * including the entities saved before it and the submission record.
   *
   * @dataProvider getSaveFailures
   */
  public function testSaveErrorRollsBackSubmission(\CRM_Core_Exception $failure): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout(self::COMPLETE_ACTIVITY_FIELDS),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
    ]);
    $submitted = 0;
    $countSubmitted = function () use (&$submitted) {
      $submitted++;
    };
    $failActivity = function ($event) use ($failure) {
      if ($event instanceof \Civi\Core\Event\PreEvent && $event->entity === 'Activity') {
        throw $failure;
      }
    };
    $lastName = uniqid('Rollback');
    \Civi::dispatcher()->addListener('civi.afform.submitted', $countSubmitted);
    \Civi::dispatcher()->addListener('hook_civicrm_pre', $failActivity);
    try {
      $this->submitContactWithActivity($lastName, ['subject' => 'Rolled back']);
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertSame($failure->getMessage(), $e->getMessage());
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.afform.submitted', $countSubmitted);
      \Civi::dispatcher()->removeListener('hook_civicrm_pre', $failActivity);
    }
    $this->assertCount(0, Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute());
    $this->assertCount(0, AfformSubmission::get(FALSE)->addWhere('afform_name', '=', $this->formName)->execute());
    $this->assertSame(0, $submitted);
  }

  /**
   * A new record the user left blank is skipped when it lacks required values, and saved
   * when the form's defaults complete it. A record the user filled in must be saved.
   */
  public function testBlankRecord(): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('Blank');
    $this->submitContactWithActivity($lastName, ['subject' => '']);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));

    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" /><af-field name="status_id:name" defn="{afform_default: \'Scheduled\'}" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('UntouchedDefault');
    $this->submitContactWithActivity($lastName, ['subject' => '', 'status_id:name' => 'Scheduled']);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));

    $lastName = uniqid('Entered');
    try {
      $this->submitContactWithActivity($lastName, ['subject' => 'No type, no source']);
      $this->fail('Should have thrown exception');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertSame('mandatory_missing', $e->getErrorCode());
    }
    $this->assertCount(0, Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute());

    $this->useValues([
      'layout' => $this->getContactWithActivityLayout(self::COMPLETE_ACTIVITY_FIELDS),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('Defaults');
    $this->submitContactWithActivity($lastName, ['subject' => '']);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(1, $this->countActivities($contact['id']));
  }

  /**
   * A value preset on the af-entity `data` attribute overrides submitted values
   * server-side, so even when the field shows on the form it is not user input:
   * an otherwise blank record stays dropped.
   */
  public function testBlankRecordWithPresetData(): void {
    $statusId = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'activity_status')
      ->addWhere('name', '=', 'Scheduled')
      ->execute()->single()['value'];
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" /><af-field name="status_id" />', "{target_contact_id: ['Individual1'], status_id: $statusId}"),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('PresetData');
    $this->submitContactWithActivity($lastName, ['subject' => '']);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));
  }

  /**
   * replaceRelativeDates() turns a relative date filter default (e.g. "this.day")
   * into an absolute date before the blank record check runs; the resolved value
   * must still be recognized as the form default, so the blank record is dropped
   * instead of reaching save and failing the whole submission.
   */
  public function testBlankRecordWithRelativeDateDefault(): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" /><af-field name="activity_date_time" defn="{afform_default: \'this.day\'}" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('RelativeDateBlank');
    $this->submitContactWithActivity($lastName, ['subject' => '', 'activity_date_time' => 'this.day']);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));
  }

  /**
   * The browser turns a "now" date default into its own local date before submitting,
   * which the server cannot reproduce: the field does not count as user input.
   */
  public function testBlankRecordWithClientResolvedDateDefault(): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" /><af-field name="activity_date_time" defn="{afform_default: \'now -1 day\'}" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('NowDefaultBlank');
    $this->submitContactWithActivity($lastName, ['subject' => '', 'activity_date_time' => date('Y-m-d', strtotime('-1 day'))]);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));
  }

  /**
   * The browser turns a "user_contact_id" default into the logged-in contact's id.
   */
  public function testBlankRecordWithCurrentUserDefault(): void {
    $userID = $this->createLoggedInUser();
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" /><af-field name="source_contact_id" defn="{afform_default: \'user_contact_id\'}" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('UserDefaultBlank');
    $this->submitContactWithActivity($lastName, ['subject' => '', 'source_contact_id' => $userID]);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame(0, $this->countActivities($contact['id']));
  }

  public function testTextDefaultIsNotResolvedAsDate(): void {
    [$resolved] = \CRM_Utils_Date::getFromTo('this.day');
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" defn="{afform_default: \'this.day\'}" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $lastName = uniqid('TextDate');
    try {
      $this->submitContactWithActivity($lastName, ['subject' => $resolved]);
      $this->fail('Entered text must not be treated as a date default');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertSame('mandatory_missing', $e->getErrorCode());
    }
    $this->assertCount(0, Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute());
  }

  public function testDefaultComparisonForMultiValueFields(): void {
    $call = [\Civi\Api4\Action\Afform\Submit::class, 'matchesDefault'];
    $this->assertTrue(\Civi\Test\Invasive::call($call, [['a', 'b'], ['b', 'a'], FALSE, TRUE]));
    $this->assertFalse(\Civi\Test\Invasive::call($call, [['a', 'c'], ['b', 'a'], FALSE, TRUE]));
    $this->assertFalse(\Civi\Test\Invasive::call($call, [['a', 'b'], ['b', 'a'], FALSE, FALSE]));
    $entered = [\Civi\Api4\Action\Afform\Submit::class, 'hasEnteredValue'];
    $fields = ['subject' => ['defn' => ['input_type' => 'CheckBox', 'afform_default' => ['a', 'b']]]];
    $this->assertFalse(\Civi\Test\Invasive::call($entered, [['subject' => ['b', 'a']], $fields, [], 'Activity', TRUE]));
    $this->assertTrue(\Civi\Test\Invasive::call($entered, [['subject' => ['a', 'c']], $fields, [], 'Activity', TRUE]));
  }

  public function testJoinedPresetAndEnteredValues(): void {
    $call = [\Civi\Api4\Action\Afform\Submit::class, 'hasEnteredValue'];
    $fields = ['email' => ['defn' => []]];
    $this->assertFalse(\Civi\Test\Invasive::call($call, [['email' => 'preset@example.org'], $fields, ['email' => 'preset@example.org'], 'Email', FALSE]));
    $this->assertTrue(\Civi\Test\Invasive::call($call, [['email' => 'entered@example.org'], $fields, [], 'Email', FALSE]));
  }

  /**
   * 'civi.afform.submitted' fires once, after the transaction is committed, with the saved ids.
   */
  public function testSubmittedEventFiresAfterCommit(): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $seen = [];
    $listener = function (\Civi\Afform\Event\AfformSubmittedEvent $event) use (&$seen) {
      $seen[] = [
        \CRM_Core_Transaction::isActive(),
        $event->getEntityIds('Individual1')[0]['id'] ?? NULL,
        $event->getRecords('Individual1')[0]['fields']['last_name'] ?? NULL,
      ];
    };
    $lastName = uniqid('Submitted');
    \Civi::dispatcher()->addListener('civi.afform.submitted', $listener);
    try {
      $this->submitContactWithActivity($lastName, ['subject' => '']);
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.afform.submitted', $listener);
    }
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', $lastName)->execute()->single();
    $this->assertSame([[FALSE, $contact['id'], $lastName]], $seen);
  }

  /**
   * Inside an enclosing transaction the event waits for its commit, and a rollback cancels it.
   */
  public function testSubmittedEventWaitsForEnclosingTransaction(): void {
    $this->useValues([
      'layout' => $this->getContactWithActivityLayout('<af-field name="subject" />'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $seen = [];
    $listener = function (\Civi\Afform\Event\AfformSubmittedEvent $event) use (&$seen) {
      $seen[] = $event->getRecords('Individual1')[0]['fields']['last_name'];
    };
    \Civi::dispatcher()->addListener('civi.afform.submitted', $listener);
    try {
      $committed = uniqid('Committed');
      $transaction = \CRM_Core_Transaction::create();
      $this->submitContactWithActivity($committed, ['subject' => '']);
      $this->assertSame([], $seen);
      $transaction->commit();
      $this->assertSame([$committed], $seen);

      $transaction = \CRM_Core_Transaction::create();
      $this->submitContactWithActivity(uniqid('RolledBack'), ['subject' => '']);
      $transaction->rollback()->commit();
      $this->assertSame([$committed], $seen);
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.afform.submitted', $listener);
    }
  }

  private function getContactWithActivityLayout(string $activityFields, string $activityData = "{target_contact_id: ['Individual1']}"): string {
    return <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" actions="{create: true, update: true}" security="FBAC" />
  <af-entity type="Activity" name="Activity1" actions="{create: true, update: true}" security="FBAC" data="$activityData" />
  <fieldset af-fieldset="Individual1"><af-field name="last_name" /></fieldset>
  <fieldset af-fieldset="Activity1">$activityFields</fieldset>
</af-form>
EOHTML;
  }

  private function submitContactWithActivity(string $lastName, array $activity): void {
    Afform::submit()
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [['fields' => ['last_name' => $lastName]]],
        'Activity1' => [['fields' => $activity]],
      ])
      ->execute();
  }

  private function countActivities(int $contactId): int {
    return \Civi\Api4\ActivityContact::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->execute()->count();
  }

  /**
   * Ensure saveJoins() does not allow ids of join entities that do not belong
   * to the parent entity.
   */
  public function testSaveJoinsDoesNotAllowForeignIds(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
    <div af-join="Email" min="1" af-repeat="Add">
      <afblock-contact-email></afblock-contact-email>
    </div>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'is_public' => TRUE,
      'permission' => ['*always allow*'],
    ]);

    $contact = $this->createTestRecord('Individual', [
      'first_name' => 'Alice',
      'last_name' => 'Tester',
    ]);

    $email1 = $this->createTestRecord('Email', [
      'contact_id' => $contact['id'],
      'email' => 'alice1@example.com',
      'location_type_id' => 1,
      'is_primary' => TRUE,
    ]);

    $email2 = $this->createTestRecord('Email', [
      'contact_id' => $contact['id'],
      'email' => 'alice2@example.com',
      'location_type_id' => 2,
      'is_primary' => FALSE,
    ]);

    $otherContact = $this->createTestRecord('Individual', [
      'first_name' => 'Bob',
      'last_name' => 'Victim',
    ]);

    $otherEmail = $this->createTestRecord('Email', [
      'contact_id' => $otherContact['id'],
      'email' => 'bob@example.com',
      'location_type_id' => 1,
      'is_primary' => TRUE,
    ]);

    $submission = [
      'Individual1' => [
        [
          'fields' => [
            'id' => $contact['id'],
            'first_name' => 'Alice',
            'last_name' => 'Tester',
          ],
          'joins' => [
            'Email' => [
              ['email' => 'alice1@example.com', 'location_type_id' => 1],
              ['email' => 'alice2@example.com', 'location_type_id' => 2],
              ['id' => $otherEmail['id'], 'email' => 'hacked@example.com', 'location_type_id' => 1],
            ],
          ],
        ],
      ],
    ];

    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($submission)
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
    }

    $victimEmail = Email::get(FALSE)
      ->addWhere('id', '=', $otherEmail['id'])
      ->execute()
      ->first();

    $this->assertSame('bob@example.com', $victimEmail['email']);
    $this->assertEquals($otherContact['id'], $victimEmail['contact_id']);
  }

  public function testDraftWithDisplayOnlyFields(): void {
    $layout = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" url-autofill="1" security="RBAC" />
        <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
          <af-field name="first_name" defn="{input_type: 'DisplayOnly'}" />
          <af-field name="last_name" />
        </fieldset>
        <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $loggedInUser = $this->createLoggedInUser();
    Contact::update(FALSE)
      ->addWhere('id', '=', $loggedInUser)
      ->addValue('first_name', 'InitialFirst')
      ->addValue('last_name', 'InitialLast')
      ->execute();

    // 1. Submit a draft with an empty (or stale) display-only field and an edited last_name
    Afform::submitDraft()
      ->setName($this->formName)
      ->setValues([
        'Individual1' => [
          [
            'fields' => [
              'first_name' => '',
              'last_name' => 'DraftModifiedLast',
            ],
          ],
        ],
      ])
      ->execute();

    // Verify Fix 1: Display-only field first_name is not stored in civicrm_afform_submission draft
    $savedDraft = AfformSubmission::get(FALSE)
      ->addWhere('contact_id', '=', $loggedInUser)
      ->addWhere('status_id:name', '=', 'Draft')
      ->addWhere('afform_name', '=', $this->formName)
      ->execute()
      ->first();

    $this->assertNotEmpty($savedDraft);
    $this->assertArrayNotHasKey('first_name', $savedDraft['data']['Individual1'][0]['fields']);
    $this->assertSame('DraftModifiedLast', $savedDraft['data']['Individual1'][0]['fields']['last_name']);

    // 2. An admin modifies the display-only field in the database
    Contact::update(FALSE)
      ->addWhere('id', '=', $loggedInUser)
      ->addValue('first_name', 'AdminUpdatedFirst')
      ->execute();

    // 3. User revisits form: prefill should merge draft while reflecting the updated display-only field
    $prefill = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => [$loggedInUser]])
      ->execute()
      ->indexBy('name');

    $this->assertSame('AdminUpdatedFirst', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertSame('DraftModifiedLast', $prefill['Individual1']['values'][0]['fields']['last_name']);

    // 4. Verify Fix 2: Even if an existing draft in DB contains a display-only field (e.g. empty string),
    // prefill should ignore the display-only value from the draft.
    AfformSubmission::update(FALSE)
      ->addWhere('id', '=', $savedDraft['id'])
      ->addValue('data', [
        'Individual1' => [
          [
            'fields' => [
              'first_name' => '',
              'last_name' => 'LegacyDraftLast',
            ],
          ],
        ],
      ])
      ->execute();

    $prefillLegacy = Afform::prefill()
      ->setName($this->formName)
      ->setFillMode('form')
      ->setArgs(['Individual1' => [$loggedInUser]])
      ->execute()
      ->indexBy('name');

    $this->assertSame('AdminUpdatedFirst', $prefillLegacy['Individual1']['values'][0]['fields']['first_name']);
    $this->assertSame('LegacyDraftLast', $prefillLegacy['Individual1']['values'][0]['fields']['last_name']);
  }

  /**
   * Submitted values are escaped when they replace tokens in the confirmation message.
   */
  public function testConfirmationMessageEscapesTokenValues(): void {
    $this->useValues([
      'layout' => self::NAME_FORM_LAYOUT,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
      'manual_processing' => TRUE,
      'confirmation_type' => 'show_confirmation_message',
      'confirmation_message' => '<p>Thanks [Individual1.0.first_name] [Individual1.0.last_name]!</p>',
    ]);
    $values = ['Individual1' => [['fields' => ['first_name' => '<b data-test="1">Jane</b>', 'last_name' => '[Individual1.0.id]']]]];

    $result = Afform::submit()->setName($this->formName)->setValues($values)->execute();
    $expected = '<p>Thanks &lt;b data-test=&quot;1&quot;&gt;Jane&lt;/b&gt; [Individual1.0.id]!</p>';
    $this->assertSame($expected, $result[0]['message']);

    // A reviewer processing the stored submission gets the same escaped message.
    $submission = AfformSubmission::get(FALSE)->addWhere('afform_name', '=', $this->formName)->execute()->single();
    $result = Afform::submit(FALSE)->setName($this->formName)->setArgs(['sid' => $submission['id']])->setValues($values)->execute();
    $this->assertStringStartsWith('<p>Thanks &lt;b data-test=&quot;1&quot;&gt;Jane&lt;/b&gt; ', $result[0]['message']);
    $this->assertStringNotContainsString('<b', $result[0]['message']);
  }

  /**
   * Submitted values are URL-encoded when they replace tokens in the redirect.
   */
  public function testRedirectEncodesTokenValues(): void {
    $this->useValues([
      'layout' => self::NAME_FORM_LAYOUT,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'confirmation_type' => 'redirect_to_url',
      'redirect' => 'civicrm/thanks?name=[Individual1.0.first_name]&cid=[Individual1.0.id]',
    ]);
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => [['fields' => ['first_name' => 'Jane&admin=1 #x', 'last_name' => 'Doe']]]])
      ->execute();
    $this->assertSame('civicrm/thanks?name=Jane%26admin%3D1%20%23x&cid=' . $result[0]['Individual1'][0]['id'], $result[0]['redirect']);
  }

  private const NAME_FORM_LAYOUT = <<<EOHTML
    <af-form ctrl="afform">
      <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
      <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
        <af-field name="first_name" />
        <af-field name="last_name" />
      </fieldset>
      <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
    </af-form>
  EOHTML;

  /**
   * A join without af-repeat holds one record per af-join element; surplus records are dropped.
   */
  public function testNonRepeatingJoinSavesOneRecordPerElement(): void {
    $this->useValues([
      'layout' => $this->getContactWithJoinLayout('<div af-join="Email"><af-field name="email" /></div>'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $emails = $this->submitEmailsPerContact([
      ['email' => 'one@example.org'],
      ['email' => 'two@example.org'],
      'x' => ['email' => 'three@example.org'],
    ]);
    $this->assertSame([['one@example.org']], $emails);
  }

  public function testNonRepeatingJoinDeclaredTwiceSavesTwoRecords(): void {
    $this->useValues([
      'layout' => $this->getContactWithJoinLayout(
        '<div af-join="Email" data="{location_type_id: 1}"><af-field name="email" /></div>' .
        '<div af-join="Email" data="{location_type_id: 2}"><af-field name="email" /></div>'
      ),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $emails = $this->submitEmailsPerContact([
      ['email' => 'home@example.org'],
      ['email' => 'work@example.org'],
      ['email' => 'surplus@example.org'],
    ]);
    $this->assertSame([['home@example.org', 'work@example.org']], $emails);
  }

  public function testRepeatingJoinHonoursMax(): void {
    $this->useValues([
      'layout' => $this->getContactWithJoinLayout('<div af-join="Email" af-repeat="Add" max="2"><af-field name="email" /></div>'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $emails = $this->submitEmailsPerContact([
      ['email' => 'one@example.org'],
      ['email' => 'two@example.org'],
      ['email' => 'three@example.org'],
    ]);
    $this->assertSame([['one@example.org', 'two@example.org']], $emails);
  }

  public function testRepeatingJoinWithoutMaxIsUnlimited(): void {
    $this->useValues([
      'layout' => $this->getContactWithJoinLayout('<div af-join="Email" af-repeat="Add"><af-field name="email" /></div>'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $emails = $this->submitEmailsPerContact([
      ['email' => 'one@example.org'],
      ['email' => 'two@example.org'],
      ['email' => 'three@example.org'],
    ]);
    $this->assertSame([['one@example.org', 'two@example.org', 'three@example.org']], $emails);
  }

  /**
   * In a repeating fieldset the client numbers join offsets across items,
   * so the second item's only record arrives at offset 1 behind a NULL.
   */
  public function testNonRepeatingJoinInRepeatingFieldset(): void {
    $this->useValues([
      'layout' => $this->getContactWithJoinLayout('<div af-join="Email"><af-field name="email" /></div>', 'af-repeat="Add"'),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
    $emails = $this->submitEmailsPerContact(
      [['email' => 'first@example.org']],
      [NULL, ['email' => 'second@example.org']],
    );
    $this->assertSame([['first@example.org'], ['second@example.org']], $emails);
  }

  private function getContactWithJoinLayout(string $joins, string $fieldsetAttributes = ''): string {
    return <<<EOHTML
      <af-form ctrl="afform">
        <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
        <fieldset af-fieldset="Individual1" $fieldsetAttributes>
          <af-field name="last_name" />
          $joins
        </fieldset>
        <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;
  }

  /**
   * Submits one Individual1 record per argument and returns the saved emails of each contact.
   */
  private function submitEmailsPerContact(array ...$emailJoinsPerContact): array {
    $lastName = uniqid('JoinLimit');
    $records = array_map(fn($emailJoins) => [
      'fields' => ['last_name' => $lastName],
      'joins' => ['Email' => $emailJoins],
    ], $emailJoinsPerContact);
    Afform::submit()
      ->setName($this->formName)
      ->setValues(['Individual1' => $records])
      ->execute();
    $contactIds = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addOrderBy('id')
      ->execute()->column('id');
    return array_map(fn($contactId) => Email::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->addOrderBy('id')
      ->execute()->column('email'), $contactIds);
  }

}
