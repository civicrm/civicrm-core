<?php

namespace E2E\AfformMock;

use Civi;
use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * A token default on a form that loads an existing contact.
 *
 * @group e2e
 */
class AfformTokenDefaultKeepsLoadedBrowserTest extends Civi\Test\MinkBase {

  private string $formName;

  private int $contactId;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit'])
      ->apply();
  }

  protected function setUp(): void {
    parent::setUp();
    $this->formName = 'mockTokenDefaultLoaded' . random_int(0, 1000000);
    $this->contactId = Contact::create(FALSE)
      ->setValues(['contact_type' => 'Individual', 'first_name' => 'Ada', 'last_name' => 'Tokendefault', 'nick_name' => 'Stored'])
      ->execute()->single()['id'];
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'name' => $this->formName,
        'title' => 'Token default loaded form',
        'server_route' => 'civicrm/' . strtolower($this->formName),
        'permission' => ['access CiviCRM'],
        'layout' => <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" data="{}" name="Individual1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
    <af-field name="nick_name" defn="{afform_default: 'Dear [Individual1.0.first_name]'}" />
  </fieldset>
</af-form>
EOHTML,
      ])
      ->execute();
  }

  /**
   * The loaded contact's own nickname is shown, not the default.
   */
  public function testLoadedNicknameIsKept(): void {
    $session = $this->openForm('#?Individual1=' . $this->contactId);
    $this->assertTrue($session->wait(10000, "{$this->input('first_name')}.value === 'Ada'"), 'Contact loaded');
    $this->assertSame('Stored', $session->evaluateScript("{$this->input('nick_name')}.value"));
  }

  /**
   * A loaded contact without a nickname keeps it empty.
   */
  public function testLoadedEmptyNicknameIsKept(): void {
    Contact::update(FALSE)->addWhere('id', '=', $this->contactId)->addValue('nick_name', '')->execute();
    $session = $this->openForm('#?Individual1=' . $this->contactId);
    $this->assertTrue($session->wait(10000, "{$this->input('first_name')}.value === 'Ada' && CRM.\$.active === 0"), 'Contact loaded');
    $this->assertSame('', $session->evaluateScript("{$this->input('nick_name')}.value"));
  }

  /**
   * The loaded contact's own choices in a multiple select are shown, not the default.
   */
  public function testLoadedMultipleChoicesAreKept(): void {
    CustomGroup::create(FALSE)
      ->setValues(['name' => 'token_default_group', 'title' => 'Token default group', 'extends' => 'Individual'])
      ->addChain('field', CustomField::create(FALSE)->setValues([
        'custom_group_id' => '$id',
        'name' => 'token_tags',
        'label' => 'Token tags',
        'data_type' => 'String',
        'html_type' => 'Select',
        'serialize' => 1,
        'option_values' => ['Ada' => 'Ada', 'Stored' => 'Stored'],
      ]))
      ->execute();
    Contact::update(FALSE)->addWhere('id', '=', $this->contactId)->addValue('token_default_group.token_tags', ['Stored'])->execute();
    Afform::update(FALSE)
      ->setLayoutFormat('html')
      ->addWhere('name', '=', $this->formName)
      ->addValue('layout', <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" data="{}" name="Individual1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <fieldset af-fieldset="Individual1">
    <af-field name="first_name" />
    <af-field name="nick_name" />
    <af-field name="token_default_group.token_tags" defn="{afform_default: '[Individual1.0.first_name]'}" />
  </fieldset>
</af-form>
EOHTML)
      ->execute();
    $session = $this->openForm('#?Individual1=' . $this->contactId);
    $this->assertTrue($session->wait(10000, "{$this->input('first_name')}.value === 'Ada' && CRM.\$.active === 0"), 'Contact loaded');
    $this->assertSame('["Stored"]', $session->evaluateScript('JSON.stringify(angular.element(document.querySelector(\'af-field[name="token_default_group.token_tags"]\')).isolateScope().getSetValue())'));
  }

  /**
   * Control: on a new contact the default follows the typed first name.
   */
  public function testDefaultFollowsTheFirstNameControl(): void {
    $session = $this->openForm('');
    $session->getPage()->find('css', 'af-field[name="first_name"] input')->setValue('Ada');
    $this->assertTrue($session->wait(5000, "{$this->input('nick_name')}.value === 'Dear Ada'"), 'Default follows the first name');
  }

  /**
   * Clearing the selected contact makes the default follow the first name again.
   */
  public function testDefaultFollowsAfterClearingTheContact(): void {
    Afform::update(FALSE)
      ->setLayoutFormat('html')
      ->addWhere('name', '=', $this->formName)
      ->addValue('layout', <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" data="{}" name="Individual1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <fieldset af-fieldset="Individual1">
    <af-field name="id" />
    <af-field name="first_name" />
    <af-field name="nick_name" defn="{afform_default: 'Dear [Individual1.0.first_name]'}" />
  </fieldset>
</af-form>
EOHTML)
      ->execute();
    $session = $this->openForm('#?Individual1=' . $this->contactId);
    $this->assertTrue($session->wait(10000, "{$this->input('nick_name')}.value === 'Stored'"), 'Contact loaded');
    $this->assertTrue($session->wait(10000, "!document.querySelector('af-field[name=id] .select2-choice').classList.contains('select2-default')"), 'Contact shown as selected');
    $session->getPage()->find('css', 'af-field[name="id"] .select2-search-choice-close')->click();
    $this->assertTrue($session->wait(5000, "{$this->input('first_name')}.value === ''"), 'Contact cleared');
    $session->getPage()->find('css', 'af-field[name="first_name"] input')->setValue('Bea');
    $this->assertTrue($session->wait(5000, "{$this->input('nick_name')}.value === 'Dear Bea'"), 'Default follows the first name');
  }

  private function openForm(string $fragment): \Behat\Mink\Session {
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/' . strtolower($this->formName)) . $fragment);
    $this->assertTrue($session->wait(10000, "!!{$this->input('nick_name')}"), 'Form loaded');
    return $session;
  }

  private function input(string $field): string {
    return "document.querySelector('af-field[name=$field] input')";
  }

  protected function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    Contact::delete(FALSE)->addWhere('id', '=', $this->contactId)->setUseTrash(FALSE)->execute();
    CustomGroup::delete(FALSE)->addWhere('name', '=', 'token_default_group')->execute();
    parent::tearDown();
  }

}
