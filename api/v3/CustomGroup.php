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

/**
 * This api exposes CiviCRM custom group.
 *
 * @package CiviCRM_APIv3
 */

/**
 * This entire function consists of legacy handling, probably for a form that no longer exists.
 * APIv3 is where code like this goes to die...
 *
 * @param array $params
 *   For legacy reasons, 'extends' can be passed as an array (for setting Participant column_value)
 *
 * @return array
 */
function civicrm_api3_custom_group_create($params) {
  if (isset($params['extends']) && is_string($params['extends'])) {
    $params['extends'] = explode(',', $params['extends']);
  }
  if (!isset($params['id']) && (!isset($params['extends'][0]) || !trim($params['extends'][0]))) {

    return civicrm_api3_create_error("First item in params['extends'] must be a class name (e.g. 'Contact').");
  }
  if (!isset($params['extends_entity_column_value']) && isset($params['extends'][1])) {
    $extendsEntity = $params['extends'][0] ?? NULL;
    $participantEntities = [
      'ParticipantRole',
      'ParticipantEventName',
      'ParticipantEventType',
    ];
    if (in_array($extendsEntity, $participantEntities)
    ) {
      $params['extends_entity_column_id'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $extendsEntity, 'value', 'name');
    }
    $params['extends_entity_column_value'] = $params['extends'][1];
    if (in_array($extendsEntity, $participantEntities)) {
      $params['extends'] = 'Participant';
    }
    else {
      $params['extends'] = $extendsEntity;
    }
  }
  elseif (isset($params['extends']) && (!isset($params['extends'][1]) || empty($params['extends'][1]))) {
    $params['extends'] = $params['extends'][0];
  }
  if (isset($params['extends_entity_column_value']) && !is_array($params['extends_entity_column_value'])) {
    // BAO fails if this is a string, but API getFields says this must be a string, so we'll do a double backflip
    $params['extends_entity_column_value'] = CRM_Utils_Array::explodePadded($params['extends_entity_column_value']);
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'CustomGroup');
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_group_create_spec(&$params) {
  $params['extends']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['style']['api.default'] = 'Inline';
  $params['is_active']['api.default'] = 1;
}

/**
 * Use this API to delete an existing group.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_custom_group_delete($params) {
  $values = new CRM_Core_DAO_CustomGroup();
  $values->id = $params['id'];
  if (!$values->find(TRUE)) {
    return civicrm_api3_create_error('Error while deleting custom group');
  }
  $result = CRM_Core_BAO_CustomGroup::deleteGroup($values, TRUE);
  return $result ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting custom group');
}

/**
 * API to get existing custom fields.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_custom_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * CRM-15191 - Hack to ensure the cache gets cleared after updating a custom group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_custom_group_setvalue($params) {
  require_once 'api/v3/Generic/Setvalue.php';
  $result = civicrm_api3_generic_setValue(["entity" => 'CustomGroup', 'params' => $params]);
  if (empty($result['is_error'])) {
    Civi::rebuild(['system' => TRUE])->execute();
  }
  return $result;
}

function civicrm_api3_custom_group_getoptions($params) {
  $result = civicrm_api3_generic_getoptions(['entity' => 'CustomGroup', 'params' => $params]);
  // This provides legacy support for APIv3, which also needs the ParticipantEventName etc pseudo-selectors
  if ($params['field'] === 'extends') {
    $options = CRM_Core_SelectValues::customGroupExtends();
    $options = CRM_Core_PseudoConstant::formatArrayOptions($params['context'] ?? NULL, $options);
    if (!empty($params['sequential'])) {
      $options = CRM_Utils_Array::makeNonAssociative($options);
    }
    $result['values'] = $options;
  }
  return $result;
}
