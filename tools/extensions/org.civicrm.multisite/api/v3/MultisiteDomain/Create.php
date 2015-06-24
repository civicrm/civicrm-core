<?php

/**
 * Create a new domain - with a domain group
 * This is not fully developed & need to work on creating admin menus etc
 *
 * @param array $params
 *
 * @return array
 * @example DomainCreate.php
 * {@getfields domain_create}
 */
function civicrm_api3_multisite_domain_create($params) {
  $domain = civicrm_api('domain', 'getsingle', array(
    'version' => 3,
    'current_domain' => TRUE,
  ));
  $fullParams = array_merge($domain, $params);
  $fullParams['domain_version'] = $domain['version'];
  $fullParams['version'] = 3;
  unset($fullParams['id']);
  if(empty($params['group_id'])){
    $fullParams['api.group.create'] = array(
      'title' => !empty($params['group_name']) ? $params['group_name'] : $params['name'],
    );
    $domainGroupID = '$value.api.group.create.id';
  }
  else{
    $domainGroupID = $params['group_id'];
  }

  $fullParams['api.setting.create'] = array(
      'is_enabled' => TRUE,
      'domain_group_id' => $domainGroupID,
   );

  return civicrm_api('domain', 'create', $fullParams);
}
/*
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
/**
 * @param array $params
 */
function _civicrm_api3_multisite_domain_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  $params['group_title'] = array(
    'title' => 'name of group to be created',
    );
  $params['group_id'] = array(
    'title' => 'id of existing group for domain',
    'description' => 'If not populated another will be created using the name'
  );
  $params['contact_id'] = array(
    'title' => 'id of existing contact for domain',
    'description' => 'If not populated another will be created using the name'
  );
}
