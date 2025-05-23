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

use Civi\Api4\SavedSearch;
use Civi\Core\Service\AutoService;
use Civi\Afform\FormDataModel;
use Civi\Api4\Afform;
use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\Utils\CoreUtil;
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
      'civi.api.prepare' => ['onApiPrepare', 200],
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   *   API preparation event.
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      [$formType, $formName] = array_pad(explode(':', (string) $apiRequest->getFormName()), 2, '');
      [$entityName, $fieldName] = array_pad(explode(':', (string) $apiRequest->getFieldName()), 2, '');

      switch ($formType) {
        case 'afform':
          if ($formName && $entityName && $fieldName) {
            $this->processAfformAutocomplete($formName, $entityName, $fieldName, $apiRequest);
          }
          return;

        case 'afformAdmin':
          $this->processAfformAdminAutocomplete($entityName, $apiRequest);
      }
    }
  }

  /**
   * Preprocess autocomplete fields for afforms
   *
   * @param string $formName
   * @param string $entityName
   * @param string $fieldName
   * @param \Civi\Api4\Generic\AutocompleteAction $apiRequest
   */
  private function processAfformAutocomplete(string $formName, string $entityName, string $fieldName, AutocompleteAction $apiRequest):void {
    // Load afform only if user has permission
    $afform = Afform::get()
      ->addWhere('name', '=', $formName)
      ->addSelect('layout')
      ->execute()->first();
    if (!$afform) {
      return;
    }
    $formDataModel = new FormDataModel($afform['layout']);
    [$entityName, $joinEntity] = array_pad(explode('+', $entityName), 2, NULL);
    $entity = $formDataModel->getEntity($entityName);
    $isId = FALSE;

    // If no model entity, it's a search display
    if (!$entity) {
      $searchDisplay = $formDataModel->getSearchDisplay($entityName);
      $savedSearch = SavedSearch::get(FALSE)
        ->addWhere('name', '=', $searchDisplay['searchName'])
        ->addSelect('api_entity', 'api_params')
        ->execute()
        ->single();
      $searchEntities = FormDataModel::getSearchEntities($savedSearch);
      $fieldEntities = FormDataModel::getSearchFieldEntityType($fieldName, $searchEntities);
      // If getSearchFieldEntityType returns > 1 entity we only need to consider the first, as the 2nd would be from a bridge join
      [$apiEntity, $explicitJoin] = array_pad(explode(' AS ', $fieldEntities[0]), 2, '');
      $formField = $searchDisplay['fields'][$fieldName]['defn'] ?? [];
      if (str_starts_with($fieldName, "$explicitJoin.")) {
        $fieldName = substr($fieldName, strlen($explicitJoin) + 1);
      }
    }
    // If using a join (e.g. Contact -> Email)
    elseif ($joinEntity) {
      $apiEntity = $joinEntity;
      $formField = $entity['joins'][$joinEntity]['fields'][$fieldName]['defn'] ?? [];
    }
    else {
      $apiEntity = $entity['type'];
      $isId = $fieldName === CoreUtil::getIdFieldName($apiEntity);
      $formField = $entity['fields'][$fieldName]['defn'] ?? [];
    }

    // Set standard fieldName so core AutocompleteFieldSubscriber can handle filters from the schema
    // @see \Civi\Api4\Event\Subscriber\AutocompleteFieldSubscriber::onApiPrepare
    $apiRequest->setFieldName("$apiEntity.$fieldName");

    // For the "Existing Entity" selector,
    // Look up the "type" fields (e.g. contact_type, activity_type_id, case_type_id, etc)
    // And apply it as a filter if specified on the form.
    if ($isId && $entity) {
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
    }

    $apiRequest->setCheckPermissions(($formField['security'] ?? NULL) !== 'FBAC');
    $apiRequest->setSavedSearch($formField['saved_search'] ?? NULL);
    $apiRequest->setDisplay($formField['search_display'] ?? NULL);
  }

  /**
   * Preprocess autocomplete fields on AfformAdmin screens
   *
   * @param string $fieldName
   * @param \Civi\Api4\Generic\AutocompleteAction $apiRequest
   */
  private function processAfformAdminAutocomplete(string $fieldName, AutocompleteAction $apiRequest):void {
    if (!\CRM_Core_Permission::check('administer afform')) {
      return;
    }
    switch ($fieldName) {
      case 'autocompleteSavedSearch':
        if (CoreUtil::isContact($apiRequest->getFilters()['api_entity'])) {
          $filter = ['Contact', $apiRequest->getFilters()['api_entity']];
          $apiRequest->addFilter('api_entity', $filter);
        }
        else {
          $apiRequest->addFilter('api_entity', $apiRequest->getFilters()['api_entity']);
        }
        $apiRequest->addFilter('is_template', FALSE);
        return;

      case 'autocompleteDisplay':
        $apiRequest->addFilter('saved_search_id.name', $apiRequest->getFilters()['saved_search_id.name']);
        $apiRequest->addFilter('type', 'autocomplete');
        return;
    }
  }

}
