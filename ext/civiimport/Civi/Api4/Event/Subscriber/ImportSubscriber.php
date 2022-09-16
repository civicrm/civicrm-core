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
use Civi\Api4\Managed;
use Civi\Api4\UserJob;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\GenericHookEvent;
use CRM_Core_DAO_AllCoreTables;
use Civi\Api4\Import;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\API\Exception\UnauthorizedException;

/**
 * Listening class that registers each Import table as an entity.
 * @service civi.api4.importSubscriber
 */
class ImportSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * Get the events this class listens to.
   *
   * @return string[]
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_post' => 'on_hook_civicrm_post',
      'civi.api4.entityTypes' => 'on_civi_api4_entityTypes',
      'civi.api.authorize' => [['onApiAuthorize', Events::W_EARLY]],
    ];
  }

  /**
   * Register each valid import as an entity
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    $importEntities = Civi\BAO\Import::getImportTables();
    foreach ($importEntities as $userJobID => $table) {
      /** @noinspection PhpUndefinedFieldInspection */
      $event->entities['Import_' . $userJobID] = [
        'name' => 'Import_' . $userJobID,
        'title' => ts('Import Data') . ' ' . $userJobID . (empty($table['created_by']) ? '' : '(' . $table['created_by'] . ')'),
        'title_plural' => ts('Import Data') . ' ' . $userJobID,
        'description' => ts('Import Job temporary data'),
        'primary_key' => ['_id'],
        'type' => ['Import'],
        'table_name' => $table['table_name'],
        'class_args' => [$userJobID],
        'label_field' => '_id',
        'searchable' => 'secondary',
        'paths' => [
          //  'browse' => "civicrm/eck/entity/list/{$entity_type['name']}",
          //  'view' => "civicrm/eck/entity?reset=1&action=view&type={$entity_type['name']}&id=[id]",
          //  'update' => "civicrm/eck/entity/edit/{$entity_type['name']}/[subtype:name]#?{$entity_type['entity_name']}=[id]",
          //  'add' => "civicrm/eck/entity/edit/{$entity_type['name']}/[subtype:name]",
        ],
        'class' => Import::class,
        'icon' => 'fa-upload',
      ];
    }
  }

  /**
   * Callback for hook_civicrm_post().
   */
  public function on_hook_civicrm_post(PostEvent $event): void {
    if ($event->entity === 'UserJob') {
      try {
        $exists = Entity::get(FALSE)->addWhere('name', '=', 'Import_' . $event->id)->selectRowCount()->execute()->count();
        if (!$exists || $event->action === 'delete') {
          // Flush entities cache key so our new Import will load as an entity.
          Civi::cache('metadata')->set('api4.entities.info', NULL);
          CRM_Core_DAO_AllCoreTables::flush();
          Managed::reconcile(FALSE)->setModules(['civiimport'])->execute();
        }
      }
      catch (\CRM_Core_Exception $e) {
        // Log & move on.
        \Civi::log()->warning('Failed to flush cache on UserJob clear', ['exception' => $e]);
        return;
      }
    }
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function onApiAuthorize(AuthorizeEvent $event): void {
    $apiRequest = $event->getApiRequest();
    $entity = $apiRequest['entity'];
    if (strpos($entity, 'Import_') === 0) {
      $userJobID = (int) (str_replace('Import_', '', $entity));
      if (!UserJob::get(TRUE)->addWhere('id', '=', $userJobID)->selectRowCount()->execute()->count()) {
        throw new UnauthorizedException('Import access not permitted');
      }
    }
  }

}
