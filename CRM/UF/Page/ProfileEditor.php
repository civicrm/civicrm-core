<?php

require_once 'CRM/Core/Page.php';

/**
 * This class is not a real page -- it contains helpers for rendering the profile-selector and profile-editor
 * widgets
 */
class CRM_UF_Page_ProfileEditor extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @throws \Exception
   */
  public function run() {
    CRM_Core_Error::fatal('This is not a real page!');
  }

  /**
   * Register profile scripts.
   */
  public static function registerProfileScripts() {
    static $loaded = FALSE;
    if ($loaded || CRM_Core_Resources::isAjaxMode()) {
      return;
    }
    $loaded = TRUE;

    CRM_Core_Resources::singleton()
      ->addSettingsFactory(function () {
        $ufGroups = civicrm_api3('UFGroup', 'get', [
          'sequential' => 1,
          'is_active' => 1,
          'options' => ['limit' => 0],
        ]);
        //CRM-16915 - insert 'module' param for the profile used by CiviEvent.
        if (CRM_Core_Permission::check('manage event profiles') && !CRM_Core_Permission::check('administer CiviCRM')) {
          foreach ($ufGroups['values'] as $key => $value) {
            $ufJoin = CRM_Core_BAO_UFGroup::getUFJoinRecord($value['id']);
            if (in_array('CiviEvent', $ufJoin) || in_array('CiviEvent_Additional', $ufJoin)) {
              $ufGroups['values'][$key]['module'] = 'CiviEvent';
            }
          }
        }
        return [
          'PseudoConstant' => [
            'locationType' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'),
            'websiteType' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id'),
            'phoneType' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'),
          ],
          'initialProfileList' => $ufGroups,
          'contactSubTypes' => CRM_Contact_BAO_ContactType::subTypes(),
          'profilePreviewKey' => CRM_Core_Key::get('CRM_UF_Form_Inline_Preview', TRUE),
        ];
      })
      ->addScriptFile('civicrm', 'packages/backbone/json2.js', 100, 'html-header', FALSE)
      ->addScriptFile('civicrm', 'packages/backbone/backbone.js', 120, 'html-header')
      ->addScriptFile('civicrm', 'packages/backbone/backbone.marionette.js', 125, 'html-header', FALSE)
      ->addScriptFile('civicrm', 'packages/backbone/backbone.collectionsubset.js', 125, 'html-header', FALSE)
      ->addScriptFile('civicrm', 'packages/backbone-forms/distribution/backbone-forms.js', 130, 'html-header', FALSE)
      ->addScriptFile('civicrm', 'packages/backbone-forms/distribution/adapters/backbone.bootstrap-modal.min.js', 140, 'html-header', FALSE)
      ->addScriptFile('civicrm', 'packages/backbone-forms/distribution/editors/list.min.js', 140, 'html-header', FALSE)
      ->addStyleFile('civicrm', 'packages/backbone-forms/distribution/templates/default.css', 140, 'html-header')
      ->addScript('CRM.BB = Backbone.noConflict();', 300, 'html-header')
      ->addScriptFile('civicrm', 'packages/jquery/plugins/jstree/jquery.jstree.js', 0, 'html-header', FALSE)
      ->addStyleFile('civicrm', 'packages/jquery/plugins/jstree/themes/default/style.css', 0, 'html-header')
      ->addStyleFile('civicrm', 'css/crm.designer.css', 140, 'html-header')
      ->addScriptFile('civicrm', 'js/crm.backbone.js', 150)
      ->addScriptFile('civicrm', 'js/model/crm.schema-mapped.js', 200)
      ->addScriptFile('civicrm', 'js/model/crm.uf.js', 200)
      ->addScriptFile('civicrm', 'js/model/crm.designer.js', 200)
      ->addScriptFile('civicrm', 'js/model/crm.profile-selector.js', 200)
      ->addScriptFile('civicrm', 'js/view/crm.designer.js', 200)
      ->addScriptFile('civicrm', 'js/view/crm.profile-selector.js', 200)
      ->addScriptFile('civicrm', 'js/jquery/jquery.crmProfileSelector.js', 250)
      ->addScriptFile('civicrm', 'js/crm.designerapp.js', 250);

    CRM_Core_Region::instance('page-header')->add([
      'template' => 'CRM/UF/Page/ProfileTemplates.tpl',
    ]);
  }

  /**
   * Register entity schemas for use in the editor's palette.
   *
   * @param array $entityTypes
   *   Strings, e.g. "IndividualModel", "ActivityModel".
   */
  public static function registerSchemas($entityTypes) {
    // TODO in cases where registerSchemas is called multiple times for same entity, be more efficient
    CRM_Core_Resources::singleton()->addSettingsFactory(function () use ($entityTypes) {
      return [
        'civiSchema' => CRM_UF_Page_ProfileEditor::getSchema($entityTypes),
      ];
    });
  }

  /**
   * AJAX callback.
   */
  public static function getSchemaJSON() {
    $entityTypes = explode(',', $_REQUEST['entityTypes']);
    CRM_Utils_JSON::output(self::getSchema($entityTypes));
  }

  /**
   * Get a list of Backbone-Form models
   *
   * @param array $entityTypes
   *   Model names ("IndividualModel").
   *
   * @throws CRM_Core_Exception
   * @return array; keys are model names ("IndividualModel") and values describe 'sections' and 'schema'
   * @see js/model/crm.core.js
   * @see js/model/crm.mappedcore.js
   */
  public static function getSchema($entityTypes) {
    // FIXME: Depending on context (eg civicrm/profile/create vs search-columns), it may be appropriate to
    // pick importable or exportable fields

    $entityTypes = array_unique($entityTypes);
    $availableFields = NULL;
    $civiSchema = [];
    foreach ($entityTypes as $entityType) {
      if (!$availableFields) {
        $availableFields = CRM_Core_BAO_UFField::getAvailableFieldsFlat();
      }
      switch ($entityType) {
        case 'IndividualModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Individual',
            ts('Individual'),
            $availableFields
          );
          break;

        case 'OrganizationModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Organization',
            ts('Organization'),
            $availableFields
          );
          break;

        case 'HouseholdModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Household',
            ts('Household'),
            $availableFields
          );
          break;

        case 'ActivityModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Activity',
            ts('Activity'),
            $availableFields
          );
          break;

        case 'ContributionModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Contribution',
            ts('Contribution'),
            $availableFields
          );
          break;

        case 'MembershipModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Membership',
            ts('Membership'),
            $availableFields
          );
          break;

        case 'ParticipantModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Participant',
            ts('Participant'),
            $availableFields
          );
          break;

        case 'CaseModel':
          $civiSchema[$entityType] = self::convertCiviModelToBackboneModel(
            'Case',
            ts('Case'),
            $availableFields
          );
          break;

        default:
          throw new CRM_Core_Exception("Unrecognized entity type: $entityType");
      }
    }

    // Adding the oddball "formatting" field here because there's no other place to put it
    foreach (['Individual', 'Organization', 'Household'] as $type) {
      if (isset($civiSchema[$type . 'Model'])) {
        $civiSchema[$type . 'Model']['schema'] += [
          'formatting' => [
            'type' => 'Markup',
            'title' => ts('Free HTML'),
            'civiFieldType' => 'Formatting',
            'section' => 'formatting',
          ],
        ];
        $civiSchema[$type . 'Model']['sections'] += [
          'formatting' => [
            'title' => ts('Formatting'),
            'is_addable' => FALSE,
          ],
        ];
      }
    }

    return $civiSchema;
  }

  /**
   * FIXME: Move to somewhere more useful
   * FIXME: Do real mapping of "types"
   *
   * @param string $extends
   *   Entity type; note: "Individual" means "Individual|Contact"; "Household" means "Household|Contact".
   * @param string $title
   *   A string to use in section headers.
   * @param array $availableFields
   *   List of fields that are allowed in profiles, e.g. $availableFields['my_field']['field_type'].
   * @return array
   *   with keys 'sections' and 'schema'
   * @see js/model/crm.core.js
   * @see js/model/crm.mappedcore.js
   */
  public static function convertCiviModelToBackboneModel($extends, $title, $availableFields) {
    $locationFields = CRM_Core_BAO_UFGroup::getLocationFields();

    // schema in format array($fieldName => $fieldSchema)
    // sections in format array($sectionName => $section)
    $result = [
      'schema' => [],
      'sections' => [],
    ];

    // build field list
    foreach ($availableFields as $fieldName => $field) {
      switch ($extends) {
        case 'Individual':
        case 'Organization':
        case 'Household':
          if ($field['field_type'] != $extends && $field['field_type'] != 'Contact'
            //CRM-15595 check if subtype
            && !in_array($field['field_type'], CRM_Contact_BAO_ContactType::subTypes($extends))
          ) {
            continue 2;
          }
          break;

        default:
          if ($field['field_type'] != $extends) {
            continue 2;
          }
      }
      // FIXME: type set to "Text"
      $result['schema'][$fieldName] = [
        'type' => 'Text',
        'title' => $field['title'],
        'civiFieldType' => $field['field_type'],
      ];
      if (in_array($fieldName, $locationFields)) {
        $result['schema'][$fieldName]['civiIsLocation'] = TRUE;
      }
      if ($fieldName == 'url') {
        $result['schema'][$fieldName]['civiIsWebsite'] = TRUE;
      }
      if (in_array($fieldName, ['phone', 'phone_and_ext'])) {
        // FIXME what about phone_ext?
        $result['schema'][$fieldName]['civiIsPhone'] = TRUE;
      }
    }

    // build section list
    $result['sections']['default'] = [
      'title' => $title,
      'is_addable' => FALSE,
    ];

    $customGroup = CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity($extends);
    $customGroup->orderBy('weight');
    $customGroup->is_active = 1;
    $customGroup->find();
    while ($customGroup->fetch()) {
      $sectionName = 'cg_' . $customGroup->id;
      $section = [
        'title' => ts('%1: %2', [1 => $title, 2 => $customGroup->title]),
        'is_addable' => $customGroup->is_reserved ? FALSE : TRUE,
        'custom_group_id' => $customGroup->id,
        'extends_entity_column_id' => $customGroup->extends_entity_column_id,
        'extends_entity_column_value' => CRM_Utils_Array::explodePadded($customGroup->extends_entity_column_value),
        'is_reserved' => $customGroup->is_reserved ? TRUE : FALSE,
      ];
      $result['sections'][$sectionName] = $section;
    }

    // put fields in their sections
    $fields = CRM_Core_BAO_CustomField::getFields($extends);
    foreach ($fields as $fieldId => $field) {
      $sectionName = 'cg_' . $field['custom_group_id'];
      $fieldName = 'custom_' . $fieldId;
      if (isset($result['schema'][$fieldName])) {
        $result['schema'][$fieldName]['section'] = $sectionName;
        $result['schema'][$fieldName]['civiIsMultiple'] = (bool) CRM_Core_BAO_CustomField::isMultiRecordField($fieldId);
      }
    }
    return $result;
  }

}
