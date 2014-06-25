-- CRM-5628
SELECT @country_id := id from civicrm_country where name = 'Haiti';

INSERT IGNORE INTO civicrm_state_province ( country_id, abbreviation, name ) VALUES
( @country_id,  'AR',  'Artibonite' ),
( @country_id,  'CE',  'Centre'     ),
( @country_id,  'NI',  'Nippes'     ),
( @country_id,  'ND',  'Nord'       );

    UPDATE  civicrm_state_province state
INNER JOIN  civicrm_country country ON ( country.id = state.country_id )
       SET  state.name = 'Nord-Est'
     WHERE  state.name = 'Nord-Eat'
       AND  country.name = 'Haiti';

INSERT INTO civicrm_acl
    (name, deny, entity_table, entity_id, operation, object_table, object_id, acl_table, acl_id, is_active)
VALUES
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'profile create',   NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'profile edit',     NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'profile listings', NULL, NULL, NULL, 1),
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'profile view',     NULL, NULL, NULL, 1);

-- CRM-5648
SELECT @option_group_id_mt := max(id) from civicrm_option_group where name = 'mapping_type';
SELECT @max_val            := MAX(ROUND(op.value))   FROM civicrm_option_value op  WHERE op.option_group_id  = @option_group_id_mt;
SELECT @max_wt             := MAX(ROUND(val.weight)) FROM civicrm_option_value val WHERE val.option_group_id = @option_group_id_mt;

INSERT INTO civicrm_option_value
   (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`)
VALUES
   (@option_group_id_mt, {localize}'Export Grant'{/localize}, (SELECT @max_val := @max_val+1), 'Export Grant', NULL, 0, 0, (SELECT @max_wt := @max_wt+1), 0, 1, 1);