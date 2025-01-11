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

namespace Civi\Search;

use Civi\Afform\FormDataModel;

/**
 * Class AfformSearchMetadataInjector
 * @package Civi\Search
 */
class AfformSearchMetadataInjector {

  /**
   * Injects settings data into search displays embedded in afforms
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('searchSettings')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        $displayTags = array_column(\Civi\Search\Display::getDisplayTypes(['name']), 'name');

        if ($displayTags) {
          foreach (pq(implode(',', $displayTags), $doc) as $component) {
            $searchName = pq($component)->attr('search-name');
            $displayName = pq($component)->attr('display-name');
            if ($searchName) {
              // Fetch search display if name is provided
              if (is_string($displayName) && strlen($displayName)) {
                $searchDisplayGet = \Civi\Api4\SearchDisplay::get(FALSE)
                  ->addWhere('name', '=', $displayName)
                  ->addWhere('saved_search_id.name', '=', $searchName);
              }
              // Fall-back to the default display
              else {
                $displayName = NULL;
                $searchDisplayGet = \Civi\Api4\SearchDisplay::getDefault(FALSE)
                  ->setSavedSearch($searchName);
              }
              try {
                $display = $searchDisplayGet
                  ->addSelect('settings', 'saved_search_id.api_entity', 'saved_search_id.api_params')
                  ->execute()->single();
                $savedSearch = \CRM_Utils_Array::filterByPrefix($display, 'saved_search_id.');
              }
              catch (\CRM_Core_Exception $e) {
                return;
              }
              pq($component)->attr('settings', htmlspecialchars(\CRM_Utils_JS::encode($display['settings'] ?? []), ENT_COMPAT));
              pq($component)->attr('api-entity', htmlspecialchars($savedSearch['api_entity'], ENT_COMPAT));
              pq($component)->attr('search', htmlspecialchars(\CRM_Utils_JS::encode($searchName), ENT_COMPAT));
              pq($component)->attr('display', htmlspecialchars(\CRM_Utils_JS::encode($displayName), ENT_COMPAT));

              // Add entity names to the fieldset so that afform can populate field metadata
              $fieldset = pq($component)->parents('[af-fieldset]');
              if ($fieldset->length) {
                $entityList = FormDataModel::getSearchEntities($savedSearch);
                $fieldset->attr('api-entities', htmlspecialchars(\CRM_Utils_JS::encode($entityList), ENT_COMPAT));
                // Add field metadata for aggregate fields because they are not in the schema.
                // Normal entity fields will be handled by AfformMetadataInjector
                foreach (Meta::getCalcFields($savedSearch['api_entity'], $savedSearch['api_params']) as $fieldInfo) {
                  foreach (pq("af-field[name='{$fieldInfo['name']}']", $doc) as $afField) {
                    \Civi\Afform\AfformMetadataInjector::setFieldMetadata($afField, $fieldInfo);
                  }
                }
              }
            }
          }
        }
      });
    $e->angular->add($changeSet);
  }

}
