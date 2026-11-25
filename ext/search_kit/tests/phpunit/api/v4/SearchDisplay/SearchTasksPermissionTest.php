<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * SearchDisplay.getSearchTasks with permissions on, for users who may update
 * contacts but have no back-office access.
 *
 * @group headless
 */
class SearchTasksPermissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
  use \Civi\Test\Api4TestTrait;

  /**
   * @var callable|null
   */
  private $authorizeListener;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $searchID = $this->createTestRecord('SavedSearch', [
      'name' => 'SearchTasksPermissionTest',
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id']],
    ])['id'];
    $this->createTestRecord('SearchDisplay', [
      'saved_search_id' => $searchID,
      'name' => 'SearchTasksPermissionTest_table',
      'type' => 'table',
      'settings' => ['actions' => TRUE, 'columns' => [['type' => 'field', 'key' => 'id']]],
    ]);
    $contactID = $this->createTestRecord('Individual')['id'];
    \CRM_Core_Session::singleton()->set('userID', $contactID);
  }

  public function tearDown(): void {
    if ($this->authorizeListener) {
      \Civi::dispatcher()->removeListener('civi.api.authorize', $this->authorizeListener);
      $this->authorizeListener = NULL;
    }
    \CRM_Core_Session::singleton()->set('userID', NULL);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   * An extension that offers a contact task to front-end users has to let them
   * call Contact.getActions and getFields, since SearchKit needs both to build
   * the task list. getActions then lists "update", which Contact checks per row.
   * The relationship types behind "Add Relationship" need "access CiviCRM", so
   * loading them failed the whole request with a 403 and the user got no tasks.
   */
  public function testRelationshipTaskNotOfferedWithoutAccessToRelationshipTypes(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access AJAX API'];
    $this->authorizeListener = function (\Civi\API\Event\AuthorizeEvent $event) {
      if ($event->getEntityName() === 'Contact' && in_array($event->getActionName(), ['getActions', 'getFields'], TRUE)) {
        $event->authorize();
        $event->stopPropagation();
      }
    };
    \Civi::dispatcher()->addListener('civi.api.authorize', $this->authorizeListener, \Civi\API\Events::W_EARLY);

    $tasks = $this->getContactSearchTasks();

    $this->assertContains('download', $tasks);
    $this->assertNotContains('contact.relationship', $tasks);
  }

  public function testRelationshipTaskOfferedToContactEditor(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts'];

    $tasks = $this->getContactSearchTasks();

    $this->assertContains('contact.relationship', $tasks);
  }

  private function getContactSearchTasks(): array {
    return SearchDisplay::getSearchTasks(TRUE)
      ->setSavedSearch('SearchTasksPermissionTest')
      ->setDisplay('SearchTasksPermissionTest_table')
      ->execute()
      ->column('name');
  }

}
