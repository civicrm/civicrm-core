<?php

/**
 * Retrieve one or more phones.
 *
 * This function has been declared there instead than in api/v3/Phone.php
 * for no specific reasons, beside to demonstrate this feature
 * (that might be useful in your module, eg if you want to implement a
 * civicrm_api ('Phone','Dial') that you would then simply put in
 * your module under api/v3/Phone/Dial.php.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   details of found phones else error
 */
function civicrm_api3_phone_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
