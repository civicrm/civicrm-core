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

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AutocompleteAction;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess api autocomplete requests
 * @service
 * @internal
 */
class AutocompleteFieldSubscriber extends AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', 150],
    ];
  }

  /**
   * Apply any filters set in the schema for autocomplete fields
   *
   * In order for this to work, the `$fieldName` param needs to be in
   * the format `EntityName.field_name`. Anything not in that format
   * will be ignored, with the expectation that any extension making up
   * its own notation for identifying fields (e.g. Afform) can implement
   * its own `PrepareEvent` handler to do filtering. If their callback
   * runs earlier than this one, it can optionally `setFieldName` to the
   * standard recognized here to get the benefit of both custom filters
   * and the ones from the schema.
   * @see \Civi\Api4\Subscriber\AfformAutocompleteSubscriber::processAfformAutocomplete
   *
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event): void {
    $apiRequest = $event->getApiRequest();
    if (is_object($apiRequest) && is_a($apiRequest, 'Civi\Api4\Generic\AutocompleteAction')) {
      [$formType, $formName] = array_pad(explode(':', (string) $apiRequest->getFormName()), 2, '');
      [$entityName, $fieldName] = array_pad(explode('.', (string) $apiRequest->getFieldName(), 2), 2, '');

      if (!$fieldName) {
        return;
      }
      try {
        $fieldSpec = civicrm_api4($entityName, 'getFields', [
          'checkPermissions' => FALSE,
          'where' => [['name', '=', $fieldName]],
        ])->single();

        // Auto-add filters defined in schema
        foreach ($fieldSpec['input_attrs']['filter'] ?? [] as $key => $value) {
          $apiRequest->addFilter($key, $value);
        }

        // Use FK key from fieldSpec, e.g. custom Autocomplete field keys by 'value' not 'id'
        if (!$apiRequest->getKey() && !empty($fieldSpec['fk_column'])) {
          $apiRequest->setKey($fieldSpec['fk_column']);
        }

        if ($formType === 'qf') {
          $this->autocompleteProfilePermissions($apiRequest, $formName, $fieldSpec);
        }
      }
      catch (\Exception $e) {
        // Ignore anything else. Extension using their own $fieldName notation can do their own handling.
      }
    }
  }

  /**
   * This function opens up permissions for APIv4 Autocompletes to be used on public-facing profile forms.
   *
   * This is far from perfect because it tries to bridge two very different architectures.
   * APIv4 Autocomplete callbacks receive the name of the form and the name of the field for validation purposes.
   * This works for Afforms (see AfformAutocompleteSubscriber) but QuickForms lack a central API
   * for looking up which fields belong to which form and whether a form is accessible to the current user.
   *
   * So this involves some verbose hard-coding and some guesswork...
   */
  private function autocompleteProfilePermissions(AutocompleteAction $apiRequest, string $formName, array $fieldSpec): void {
    // This only supports "Autocomplete-Select" custom field options for now.
    // Be careful if opening this up to other types of entities, it could lead to unwanted permission bypass!
    if ($apiRequest->getEntityName() !== 'OptionValue') {
      return;
    }
    // For lack of any smarter way to do this, here's a <ugh> hard-coded list of public forms that allow profile fields
    $publicForms = [
      'CRM_Event_Form_Registration_Register' => 'civicrm_event',
      'CRM_Contribute_Form_ContributionBase' => 'civicrm_contribution_page',
      'CRM_Profile_Form' => NULL,
    ];
    // Verify this form is one of the whitelisted public forms (or a subclass of it)
    foreach (array_keys($publicForms) as $publicForm) {
      if (is_a($formName, $publicForm, TRUE)) {
        $formClass = $publicForm;
        $entityTableName = $publicForms[$publicForm];
      }
    }
    if (!isset($formClass)) {
      return;
    }
    $fieldName = !empty($fieldSpec['custom_field_id']) ? 'custom_' . $fieldSpec['custom_field_id'] : $fieldSpec['name'];

    // Verify this field belongs to an active profile embedded on the specified form
    $profileFields = \Civi\Api4\UFField::get(FALSE)
      ->addSelect('uf_group_id', 'is_searchable', 'uf_join.entity_table', 'uf_join.entity_id')
      ->addJoin('UFJoin AS uf_join', 'INNER', ['uf_group_id', '=', 'uf_join.uf_group_id'])
      ->addWhere('field_name', '=', $fieldName)
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('uf_group_id.is_active', '=', TRUE)
      ->addWhere('uf_join.is_active', '=', TRUE)
      ->addWhere('uf_join.entity_table', $entityTableName ? '=' : 'IS NULL', $entityTableName)
      ->execute();
    // Validate entity_id
    foreach ($profileFields as $profileField) {
      // For profiles embedded on an event/contribution page, verify the page is active.
      // It would be nice if we could do a full-stack permission check here to see if the current
      // user may to use the form for the given entity_id (e.g. is the event currently open)
      // but that logic is stuck in the form layer and there's no api for it.
      // Since this function only deals custom field option values, it's "secure enough" to verify
      // the page is active.
      if ($entityTableName) {
        $enabled = civicrm_api4(CoreUtil::getApiNameFromTableName($entityTableName), 'get', [
          'checkPermissions' => FALSE,
          'select' => ['row_count'],
          'where' => [
            ['is_active', '=', TRUE],
            ['id', '=', $profileField['uf_join.entity_id']],
          ],
        ]);
        if ($enabled->count()) {
          $apiRequest->setCheckPermissions(FALSE);
        }
      }
      // Standalone profiles - verify the user has permission to use them
      else {
        if (
          \CRM_Core_Permission::check('profile create') ||
          ($profileField['is_searchable'] && \CRM_Core_Permission::check('profile view'))
        ) {
          $apiRequest->setCheckPermissions(FALSE);
        }
      }
    }
  }

}
