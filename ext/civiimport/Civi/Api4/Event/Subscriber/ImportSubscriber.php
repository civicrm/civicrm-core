<?php

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
namespace Civi\Api4\Event\Subscriber;

use Civi;
use Civi\API\Event\AuthorizeEvent;
use Civi\API\Events;
use Civi\Api4\Entity;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Managed;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\UserJob;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use CRM_Core_DAO_AllCoreTables;
use Civi\Api4\Import;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\API\Exception\UnauthorizedException;
use CRM_Civiimport_ExtensionUtil as E;

/**
 * Listening class that registers each Import table as an entity.
 *
 * @service civi.api4.importSubscriber
 */
class ImportSubscriber extends AutoService implements EventSubscriberInterface {

  /**
   * Get the events this class listens to.
   *
   * @return string[]
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_post' => 'on_hook_civicrm_post',
      'hook_civicrm_pre' => 'on_hook_civicrm_pre',
      'civi.api4.entityTypes' => 'on_civi_api4_entityTypes',
      'civi.api.authorize' => [['onApiAuthorize', Events::W_EARLY]],
      'api.schema_map.build' => 'on_schema_map_build',
      'civi.import.bundledActions' => 'getBundledActions',
    ];
  }

  /**
   * Register each valid import as an entity
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   *
   * @noinspection PhpUnused
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    $importEntities = Civi\BAO\Import::getImportTables();
    foreach ($importEntities as $userJobID => $table) {
      /** @noinspection PhpUndefinedFieldInspection */
      $event->entities['Import_' . $userJobID] = [
        'name' => 'Import_' . $userJobID,
        'title' => $table['title'],
        'title_plural' => $table['title'],
        'description' => $table['description'],
        'primary_key' => ['_id'],
        'type' => ['Import'],
        'table_name' => $table['table_name'],
        'class_args' => [$userJobID],
        'label_field' => '_id',
        'searchable' => 'secondary',
        'class' => Import::class,
        'icon' => 'fa-upload',
      ];
    }
  }

  /**
   * Register FK join from import table to entity being imported
   *
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public static function on_schema_map_build(SchemaMapBuildEvent $event): void {
    $schema = $event->getSchemaMap();
    $importEntities = Civi\BAO\Import::getImportTables();
    $jobTypes = array_column(\CRM_Core_BAO_UserJob::getTypes(), 'entity', 'id');
    foreach ($importEntities as $importEntity) {
      $fkEntity = $importEntity['entity'] ?? $jobTypes[$importEntity['job_type']] ?? NULL;
      $fkTable = $fkEntity ? CoreUtil::getTableName($fkEntity) : NULL;
      if ($fkEntity && $fkTable) {
        $table = $schema->getTableByName($importEntity['table_name']);
        $link = new Civi\Api4\Service\Schema\Joinable\Joinable($fkTable, 'id', '_entity_id');
        $link->setBaseTable($importEntity['table_name']);
        $link->setJoinType(Joinable::JOIN_TYPE_ONE_TO_MANY);
        $table->addTableLink('_entity_id', $link);
      }
    }
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @noinspection PhpUnused
   */
  public function on_hook_civicrm_pre(PreEvent $event): void {
    if ($event->entity === 'UserJob' && $event->action === 'edit') {
      if ($this->isTableChange($event)) {
        $this->flushEntityMetadata();
      }
    }
  }

  /**
   * Get the import table from event data.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   *
   * @return string|null
   */
  private function getImportTableFromEvent(GenericHookEvent $event): ?string {
    if (isset($event->object)) {
      $metadata = json_decode((string) $event->object->metadata, TRUE);
      if (!is_array($metadata)) {
        return NULL;
      }
      return $metadata['DataSource']['table_name'] ?? NULL;
    }
    return $event->params['metadata']['DataSource']['table_name'] ?? NULL;
  }

  /**
   * Callback for hook_civicrm_post().
   */
  public function on_hook_civicrm_post(PostEvent $event): void {
    if ($event->entity === 'UserJob') {
      if ($event->action === 'delete' || ($this->getImportTableFromEvent($event) && !$this->ImportEntityExists($event))) {
        $this->flushEntityMetadata();
      }
    }
  }

  /**
   * Is the update changing the associated temp table.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   *
   * @return bool
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  private function isTableChange(GenericHookEvent $event): bool {
    $newTable = $this->getImportTableFromEvent($event);
    $userJob = UserJob::get(FALSE)
      ->addWhere('id', '=', $event->id)
      ->addSelect('metadata')->execute()->first();
    $savedTable = $userJob['metadata']['DataSource']['table_name'] ?? NULL;
    return $newTable !== $savedTable;
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @noinspection PhpUnused
   */
  public function onApiAuthorize(AuthorizeEvent $event): void {
    $apiRequest = $event->getApiRequest();
    $entity = $apiRequest['entity'];
    if (str_starts_with($entity, 'Import_') && !in_array($event->getActionName(), ['getFields', 'getActions', 'checkAccess'], TRUE)) {
      $userJobID = (int) (str_replace('Import_', '', $entity));
      if (!UserJob::get(TRUE)->addWhere('id', '=', $userJobID)->selectRowCount()->execute()->count()) {
        throw new UnauthorizedException('Import access not permitted');
      }
      if ($event->getActionName() === 'get') {
        // User can access UserJob::get so authorize them to access the imports as well
        $event->authorize();
      }
    }
  }

  /**
   * Flush entities cache key so our new Import will load as an entity.
   */
  protected function flushEntityMetadata(): void {
    try {
      unset(Civi::$statics['civiimport_tables']);
      Civi::cache('metadata')->delete('api4.entities.info');
      Civi::cache('metadata')->delete('api4.schema.map');
      Civi::cache('metadata')->delete('civiimport_tables');
      CRM_Core_DAO_AllCoreTables::flush();
      Managed::reconcile(FALSE)->setModules(['civiimport'])->execute();
    }
    catch (\CRM_Core_Exception $e) {
      // Log & move on.
      \Civi::log()->warning('Failed to flush cache on UserJob clear', ['exception' => $e]);
      return;
    }
  }

  /**
   * Does the pseudo-entity for the import exist yet.
   *
   * @param \Civi\Core\Event\PostEvent $event
   *
   * @return int
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function ImportEntityExists(PostEvent $event): int {
    return Entity::get(FALSE)
      ->addWhere('name', '=', 'Import_' . $event->id)
      ->selectRowCount()
      ->execute()
      ->count();
  }

  public function getBundledActions(GenericHookEvent $event): void {
    // for now we check permissions so this always runs as the logged in user ....
    // Tags, send workflow messages are also ones that could be added..
    foreach (\Civi\Api4\Group::get()->execute() as $group) {
      $event->actions['Contact']['add_to_group.' . $group['name']] = [
        'category' => E::ts('Add to group'),
        'label' => ts('Add to %1', [1 => $group['title']]),
        'api' => \Civi\Api4\GroupContact::save()
          ->addRecord([
            'status' => 'Added',
            'group_id' => $group['id'],
            'contact_id' => '$id',
          ])->setMatch(['contact_id', 'group_id']),
      ];
    }
  }

}
