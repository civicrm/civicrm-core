<?php
namespace Civi\MessageAdmin;

use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * message_admin_civicrm_searchKitTasks() runs on every SearchDisplay.getSearchTasks
 * call, whatever the search's entity.
 *
 * @group headless
 */
class SearchKitTasksTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use \Civi\Test\Api4TestTrait;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit'])
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * A front-end user without "access CiviCRM" must still get the tasks for a
   * search they can see, e.g. a button on a public Afform that runs a task.
   *
   * The hook used to ask MessageTemplate.getActions whether "revert" was allowed.
   * getActions needs "access CiviCRM", so for such a user it threw, and the whole
   * getSearchTasks request failed with a 403 - for every entity, not just
   * MessageTemplate - leaving task buttons that did nothing.
   */
  public function testSearchTasksForUserWithoutAccessCiviCRM(): void {
    $this->createTestRecord('SavedSearch', [
      'name' => 'Public_Contacts',
      'label' => 'Public Contacts',
      'api_entity' => 'Contact',
      'api_params' => ['version' => 4, 'select' => ['id', 'display_name']],
    ]);
    $this->setPermissions(['access AJAX API']);

    // checkPermissions on: the request a browser makes.
    $tasks = SearchDisplay::getSearchTasks(TRUE)
      ->setSavedSearch('Public_Contacts')
      ->execute()
      ->column('name');

    $this->assertContains('download', $tasks);
  }

  public function testRevertTaskOfferedToWorkflowTemplateEditor(): void {
    $this->createMessageTemplateSearch();
    $this->setPermissions(['access CiviCRM', 'edit system workflow message templates']);

    $this->assertContains('revert', $this->getMessageTemplateTasks());
  }

  public function testRevertTaskNotOfferedWithoutPermissionToRevert(): void {
    $this->createMessageTemplateSearch();
    $this->setPermissions(['access CiviCRM', 'edit user-driven message templates']);

    $this->assertNotContains('revert', $this->getMessageTemplateTasks());
  }

  private function createMessageTemplateSearch(): void {
    $this->createTestRecord('SavedSearch', [
      'name' => 'Message_Templates',
      'label' => 'Message Templates',
      'api_entity' => 'MessageTemplate',
      'api_params' => ['version' => 4, 'select' => ['id', 'msg_title']],
    ]);
  }

  private function getMessageTemplateTasks(): array {
    return SearchDisplay::getSearchTasks(TRUE)
      ->setSavedSearch('Message_Templates')
      ->execute()
      ->column('name');
  }

  private function setPermissions(array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

}
