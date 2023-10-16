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
 * This api exposes CiviCRM tags.
 *
 * Tags are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * @note this api is for working with tags themselves. To add/remove tags from
 * a contact or other entity, use the EntityTag api.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a tag.
 *
 * Tags are used to classify CRM entities (including Contacts, Groups and Actions).
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_tag_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Tag');
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 */
function _civicrm_api3_tag_create_spec(&$params) {
  $params['used_for']['api.default'] = 'civicrm_contact';
  $params['id']['api.aliases'] = ['tag'];
}

/**
 * Delete an existing Tag.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_tag_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a Tag.
 *
 * This api is used for finding an existing tag.
 * Either id or name of tag are required parameters for this api.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_tag_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
