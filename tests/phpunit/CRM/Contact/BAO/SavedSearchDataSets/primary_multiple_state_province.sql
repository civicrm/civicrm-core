INSERT INTO civicrm_mapping (id,name,description,mapping_type_id) Values
(114, NULL, NULL, NULL);
INSERT INTO `civicrm_mapping_field` (`id`, `mapping_id`, `name`, `contact_type`, `column_number`, `location_type_id`, `phone_type_id`, `im_provider_id`, `relationship_type_id`, `relationship_direction`, `grouping`, `operator`, `value`, `website_type_id`) VALUES
(2846, 114, 'state_province', 'Contact', 0, NULL, NULL, NULL, NULL, NULL, 1, 'IN', '1501,2704', NULL);
INSERT INTO `civicrm_saved_search` (`id`, `form_values`, `mapping_id`, `search_custom_id`) VALUES
(333, 'a:5:{s:6:"mapper";a:1:{i:1;a:1:{i:0;a:3:{i:0;s:7:"Contact";i:1;s:14:"state_province";i:2;s:1:" ";}}}s:8:"operator";a:1:{i:1;a:1:{i:0;s:2:"IN";}}s:5:"value";a:1:{i:1;a:1:{i:0;s:9:"1501,2074";}}s:8:"radio_ts";s:6:"ts_all";s:4:"task";s:0:"";}', '114', NULL);
INSERT INTO `civicrm_group` (`id`, `name`, `title`, `description`, `source`, `saved_search_id`, `is_active`, `visibility`) VALUES (98, 'core_2874', 'Core 2874 test group', NULL, NULL, 333, 1, 'User and User Admin Only');
