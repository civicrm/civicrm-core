<?php
use CRM_Iframe_ExtensionUtil as E;

/**
 * Iframe.Installscript API
 *
 * @param array $params
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_iframe_installscript($params) {
  Civi::service('iframe.script')->install();
  return civicrm_api3_create_success();
}
