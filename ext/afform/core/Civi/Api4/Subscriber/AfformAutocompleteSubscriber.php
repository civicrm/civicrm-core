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

use Civi\Core\Service\AutoService;
use Civi\Afform\FormDataModel;
use Civi\API\Events;
use Civi\Api4\Afform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess api autocomplete requests
 * @service
 * @internal
 */
class AfformAutocompleteSubscriber extends AutoService implements EventSubscriberInterface {

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
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
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
        ->execute()->first();
      if (!$afform) {
        return;
      }
      $formDataModel = new FormDataModel($afform['layout']);
      $entity = $formDataModel->getEntity($entityName);

      // Look up the "type" fields (e.g. contact_type, activity_type_id, case_type_id, etc)
      $typeFields = [];
      if ($entity['type'] === 'Contact') {
        $typeFields = ['contact_type', 'contact_sub_type'];
      }
      else {
        $extends = array_column(\CRM_Core_BAO_CustomGroup::getCustomGroupExtendsOptions(), 'grouping', 'id');
        $typeFields = (array) ($extends[$entity['type']] ?? NULL);
      }
      // If entity has a type set in the values, auto-apply that to filters
      foreach ($typeFields as $typeField) {
        if (!empty($entity['data'][$typeField])) {
          $apiRequest->addFilter($typeField, $entity['data'][$typeField]);
        }
      }

      $apiRequest->setCheckPermissions($entity['security'] !== 'FBAC');
      $apiRequest->setSavedSearch($entity['fields'][$fieldName]['defn']['saved_search'] ?? NULL);
    }
  }

}
