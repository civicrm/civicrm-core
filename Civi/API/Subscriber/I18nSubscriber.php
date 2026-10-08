<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\API\Subscriber;

use Civi\API\Events;
use Civi\Core\Locale;
use Civi\Api4\Translation;
use Civi\Api4\Generic\AbstractAction;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class I18nSubscriber
 * @package Civi\API\Subscriber
 */
class I18nSubscriber implements EventSubscriberInterface {

  /**
   * Used for rolling back language to its original setting after the api call.
   *
   * @var array
   *   Array(string $requestId => \Civi\Core\Locale $locale).
   */
  protected $originalLocale = [];

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api.prepare' => ['onApiPrepare', Events::W_MIDDLE],
      'civi.api.respond' => ['onApiRespond', Events::W_LATE],
      'civi.api.prepare' => ['onApiPrepareTranslation', Events::W_LATE],
    ];
  }

  /**
   * Support multi-lingual requests
   *
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiPrepare(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    $params = $apiRequest['params'];
    if ($apiRequest['version'] < 4) {
      $language = $params['options']['language'] ?? $params['option.language'] ?? NULL;
    }
    else {
      $language = $params['language'] ?? NULL;
    }
    if ($language) {
      $newLocale = Locale::negotiate($language);
      if ($newLocale) {
        $this->originalLocale[$apiRequest['id']] = Locale::detect();
        $newLocale->apply();
      }
    }
  }

  /**
   * Reset language to the default.
   *
   * @param \Civi\API\Event\Event $event
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiRespond(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    if (!empty($this->originalLocale[$apiRequest['id']])) {
      $this->originalLocale[$apiRequest['id']]->apply();
      unset($this->originalLocale[$apiRequest['id']]);
    }
  }

  /**
   * convert update queries to update civicrm_translatable instead of the direct field in the entity
   * we can't do it in CRM_Core_I18n_Schema::rewriteQuery as it's way less reliable
   *
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiPrepareTranslation(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    // FIXME: deal with api3 ?
    if (!$apiRequest instanceof AbstractAction || $apiRequest->getVersion() !== 4) {
      return;
    }

    // TODO: should we add a check to avoid this for non i18n installation
    $locale = \CRM_Core_I18n::getLocale();
    
    $entity = $apiRequest->getEntityName();
    $action = $apiRequest->getActionName();
    if (!in_array($action, ['update', 'save'], TRUE)) {
      return;
    }
    $infos = self::getTranslatable($entity);
    if (!$infos) {
      return;
    }
    ['table' => $table, 'fields' => $translatable] = $infos;

    $rows = [];
    if ($action === 'update') {
      $values = $apiRequest->getValues();
      $i18nFieldUsed = array_intersect_key($values, array_flip($translatable));
      if (!$i18nFieldUsed) {
        return;
      }
      $ids = self::getTargetIds($apiRequest, $values);
      if (!$ids) {
        // can't update
        return;
      }
      foreach ($ids as $id) {
        foreach ($i18nFieldUsed as $field => $value) {
          $rows[] = [
            'entity_table' => $table,
            'entity_field' => $field,
            'entity_id' => $id,
            'language' => $locale,
            'string' => $value,
          ];
        }
      }
      $remaining = array_diff_key($values, $i18nFieldUsed);
      $apiRequest->setValues($remaining);
      if (!$remaining) {
        // nothing left to update in the original table, ensure nothing is done
        // FIXME: could it cause some issues ? is there a better way ?
        $apiRequest->setWhere([['id', '=', 0]]);
      }
    }
    // save
    else {
      $records = $apiRequest->getRecords();
      \Civi::log()->debug('records-- ' . print_r($records,1));
      foreach ($records as $i => $record) {
        if (empty($record['id'])) {
          // new row, don't add in civicrm_translatable
          // but should we in some cases ? 
          continue;
        }
        foreach (array_intersect_key($record, array_flip($translatable)) as $field => $value) {
          $rows[] = [
            'entity_table' => $table,
            'entity_field' => $field,
            'entity_id' => $record['id'],
            'language' => $locale,
            'string' => $value,
          ];
          unset($records[$i][$field]);
        }
      }
      $apiRequest->setRecords($records);
    }

    if ($rows) {
      Translation::save(FALSE)
        ->setRecords($rows)
        ->setMatch(['entity_table', 'entity_field', 'entity_id', 'language'])
        ->execute();
    }

  }

  // FIXME: move to CRM_Core_I18n_SchemaStructure ?
  private static function getTranslatable(string $entity): ?array {
    $table = \Civi\Api4\Utils\CoreUtil::getTableName($entity);
    if (!$table) {
      return NULL;
    }
    $columns = \CRM_Core_I18n_SchemaStructure::columns()[$table] ?? NULL;
    if (!$columns) {
      return NULL;
    }
    return ['table' => $table, 'fields' => array_keys($columns)];
  }

  private static function getTargetIds(AbstractAction $request, array $values): array {
    $where = $request->getWhere();
    $ids = [];

    if ($where) {
      $ids = civicrm_api4($request->getEntityName(), 'get', [
        'checkPermissions' => FALSE,
        'select' => ['id'],
        'where' => $where,
      ])->column('id');
    }

    // id could be in values instead of the where
    if (!empty($values['id'])) {
      $ids = $where
        ? array_values(array_intersect($ids, [$values['id']]))
        : [$values['id']];
    }

    return array_unique($ids);
  }

}
