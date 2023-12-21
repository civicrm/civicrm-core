<?php
use CRM_Oembed_ExtensionUtil as E;

/**
 * Oembed.Installscript API
 *
 * @param array $params
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_oembed_installscript($params) {
  Civi::service('oembed.script')->install();
  return civicrm_api3_create_success();
}
