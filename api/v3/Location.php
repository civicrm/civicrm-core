<?php

/**
 *  Functions to inform caller that Location is obsolete and Address, Phone, Email, Website should be used
 */
function civicrm_api3_location_create($params) {
  return civicrm_api3_create_error("API (Location, Create) does not exist, use the Address/Phone/Email/Website API instead", array('obsoleted' => TRUE));
}

/**
 * @param $params
 *
 * @return array
 */
function civicrm_api3_location_get($params) {
  return civicrm_api3_create_error("API (Location, Get) does not exist, use the Address/Phone/Email/Website API instead", array('obsoleted' => TRUE));
}

/**
 * @param $params
 *
 * @return array
 */
function civicrm_api3_location_delete($params) {
  return civicrm_api3_create_error("API (Location, Delete) does not exist, use the Address/Phone/Email/Website API instead", array('obsoleted' => TRUE));
}

