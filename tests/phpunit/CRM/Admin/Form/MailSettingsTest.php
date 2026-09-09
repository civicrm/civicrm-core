<?php

use Civi\Api4\MailSettings;

/**
 * Test the Mail Account form's handling of externally-supplied credentials.
 *
 * @group headless
 */
class CRM_Admin_Form_MailSettingsTest extends CiviUnitTestCase {

  use \Civi\Test\FormTrait;

  /**
   * What the fake connector reports. Empty means "offer nothing".
   *
   * @var array
   */
  private array $initiators = [];

  public function setUp(): void {
    parent::setUp();
    $this->initiators = [];
    $this->hookClass->setHook('civicrm_initiators', [$this, 'hook_civicrm_initiators']);
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_mail_settings']);
    parent::tearDown();
  }

  public function hook_civicrm_initiators(array $context, array &$available, &$default): void {
    $available = array_merge($available, $this->initiators);
  }

  private function createMailSettings(): int {
    return MailSettings::create(FALSE)
      ->setValues([
        'name' => 'acct-' . uniqid(),
        'domain' => 'example.org',
        'protocol:name' => 'IMAP',
        'server' => 'imap.example.org',
        'username' => 'bob@example.org',
        'password' => 'stored-secret',
        'is_ssl' => TRUE,
        'is_default' => FALSE,
      ])
      ->execute()
      ->single()['id'];
  }

  private function getStored(int $id): array {
    return MailSettings::get(FALSE)
      ->addSelect('password', 'username', 'server', 'is_ssl')
      ->addWhere('id', '=', $id)
      ->execute()
      ->single();
  }

  /**
   * Build the form ready to process.
   *
   * FormWrapper leaves _action at its default, since that is normally set by the
   * controller, and CRM_Admin_Form does not read it back from the request.
   */
  private function buildForm(int $id, array $submittedValues = []): \Civi\Test\FormWrapper {
    $form = $this->getTestForm('CRM_Admin_Form_MailSettings', $submittedValues, ['id' => $id]);
    $reflection = new ReflectionProperty(\Civi\Test\FormWrapper::class, 'form');
    $reflection->setAccessible(TRUE);
    $reflection->getValue($form)->_action = CRM_Core_Action::UPDATE;
    return $form;
  }

  private function submittedValues(int $id): array {
    return [
      'name' => 'acct-renamed',
      'domain' => 'example.org',
      'protocol' => \CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP'),
      'server' => 'imap.example.org',
      'username' => 'bob@example.org',
      'is_default' => 0,
      'activity_type_id' => \CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email'),
      'activity_status' => 'Completed',
      'activity_source' => 'from',
    ];
  }

  private function declareConnection(array $extra = []): void {
    $this->initiators['fake'] = array_merge([
      'title' => 'Fake Service',
      'render' => function (\CRM_Core_Region $region, array $context, array $initiator) {
        $region->addMarkup('<div class="fake-connect"></div>');
      },
    ], $extra);
  }

  public function testNoInitiatorsLeavesFormUnchanged(): void {
    $id = $this->createMailSettings();

    $form = $this->buildForm($id);
    $form->processForm(\Civi\Test\FormWrapper::BUILT);

    $this->assertFalse($form->getTemplateVariable('mailSettingsHasInitiators'));
    $this->assertNull($form->getTemplateVariable('mailSettingsConnection'));
  }

  public function testConnectionIsReportedToTheTemplate(): void {
    $id = $this->createMailSettings();
    $this->declareConnection([
      'is_connected' => TRUE,
      'status_message' => 'Connected as bob@example.org',
      'status_severity' => 'success',
      'manage_url' => 'http://example.org/manage',
      'managed_fields' => ['password'],
    ]);

    $form = $this->buildForm($id);
    $form->processForm(\Civi\Test\FormWrapper::BUILT);

    $connection = $form->getTemplateVariable('mailSettingsConnection');
    $this->assertTrue($form->getTemplateVariable('mailSettingsHasInitiators'));
    $this->assertEquals('Connected as bob@example.org', $connection['status_message']);
    $this->assertEquals('success', $connection['status_severity']);
    $this->assertEquals('http://example.org/manage', $connection['manage_url']);
  }

  public function testUnconnectedInitiatorReportsNoConnection(): void {
    $id = $this->createMailSettings();
    $this->declareConnection();

    $form = $this->buildForm($id);
    $form->processForm(\Civi\Test\FormWrapper::BUILT);

    $this->assertTrue($form->getTemplateVariable('mailSettingsHasInitiators'),
      'The Connect button is still offered');
    $this->assertNull($form->getTemplateVariable('mailSettingsConnection'));
  }

  /**
   * The connection supplies the password at poll-time, so saving the form must not
   * overwrite the stored value with the absent field's empty default.
   */
  public function testManagedFieldIsPreservedOnSave(): void {
    $id = $this->createMailSettings();
    $this->declareConnection([
      'is_connected' => TRUE,
      'status_message' => 'Connected',
      'managed_fields' => ['password'],
    ]);

    $form = $this->buildForm($id, $this->submittedValues($id));
    $form->processForm();

    $stored = $this->getStored($id);
    $this->assertEquals('stored-secret', $stored['password'],
      'A field owned by the connection is left alone');
    $this->assertEquals('bob@example.org', $stored['username'],
      'Fields not owned by the connection still save');
  }

  /**
   * A managed boolean is the case that really bites: an absent checkbox submits as
   * FALSE rather than NULL, so without the guard it would be written as "off".
   */
  public function testManagedBooleanIsPreservedOnSave(): void {
    $id = $this->createMailSettings();
    $this->declareConnection([
      'is_connected' => TRUE,
      'status_message' => 'Connected',
      'managed_fields' => ['password', 'is_ssl'],
    ]);

    $form = $this->buildForm($id, $this->submittedValues($id));
    $form->processForm();

    $this->assertEquals(1, $this->getStored($id)['is_ssl'],
      'An absent managed checkbox must not be saved as unchecked');
  }

  /**
   * Without a connection the password field is ordinary, and clearing it clears it.
   */
  public function testUnmanagedPasswordStillSaves(): void {
    $id = $this->createMailSettings();

    $values = $this->submittedValues($id) + ['password' => 'typed-by-hand'];
    $form = $this->buildForm($id, $values);
    $form->processForm();

    $this->assertEquals('typed-by-hand', $this->getStored($id)['password']);
  }

}
