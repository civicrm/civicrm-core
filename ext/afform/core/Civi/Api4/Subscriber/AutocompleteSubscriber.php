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

namespace Civi\Api4\Subscriber;

use Civi\Afform\FormDataModel;
use Civi\API\Events;
use Civi\Api4\Afform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess api autocomplete requests
 */
class AutocompleteSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', Events::W_MIDDLE],
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   *   API preparation event.
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      $formName = $apiRequest->getFormName();
      if (!str_starts_with((string) $formName, 'afform:') || !strpos((string) $apiRequest->getFieldName(), ':')) {
        return;
      }
      [$entityName, $fieldName] = explode(':', $apiRequest->getFieldName());
      // Load afform only if user has permission
      $afform = Afform::get()
        ->addWhere('name', '=', str_replace('afform:', '', $formName))
        ->addSelect('layout')
        ->setLayoutFormat('shallow')
        ->execute()->first();
      if (!$afform) {
        return;
      }
      $formDataModel = new FormDataModel($afform['layout']);
      $entity = $formDataModel->getEntity($entityName);
      $defn = [];
      if (!empty($entity['fields'][$fieldName]['defn'])) {
        $defn = \CRM_Utils_JS::decode($entity['fields'][$fieldName]['defn']);
      }
      $apiRequest->setCheckPermissions(empty($defn['skip_permissions']));
      $apiRequest->setSavedSearch($defn['saved_search'] ?? NULL);
    }
  }

}
