-- CRM-3507: upgrade message templates (if changed)
{include file='../CRM/Upgrade/3.1.alpha2.msg_template/civicrm_msg_template.tpl'}

--  CRM-5263

UPDATE civicrm_country SET is_province_abbreviated = 1 
WHERE name IN ('Canada', 'United States');

--  CRM-5106
--  Updating weight for Autocomplete search options

SELECT @option_group_id_acsOpt := max(id) from civicrm_option_group where name = 'contact_autocomplete_options';

DELETE FROM `civicrm_option_value` WHERE option_group_id = @option_group_id_acsOpt;

INSERT INTO `civicrm_option_value`
    (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES
    (@option_group_id_acsOpt, {localize}'Email Address'{/localize}  , 2, 'email',          NULL, 0, NULL, 2,  0, 0, 1, NULL, NULL),
    (@option_group_id_acsOpt, {localize}'Phone'{/localize}          , 3, 'phone',          NULL, 0, NULL, 3,  0, 0, 1, NULL, NULL),
    (@option_group_id_acsOpt, {localize}'Street Address'{/localize} , 4, 'street_address', NULL, 0, NULL, 4,  0, 0, 1, NULL, NULL),
    (@option_group_id_acsOpt, {localize}'City'{/localize}           , 5, 'city',           NULL, 0, NULL, 5,  0, 0, 1, NULL, NULL),
    (@option_group_id_acsOpt, {localize}'State/Province'{/localize} , 6, 'state_province', NULL, 0, NULL, 6,  0, 0, 1, NULL, NULL),
    (@option_group_id_acsOpt, {localize}'Country'{/localize}        , 7, 'country',        NULL, 0, NULL, 7,  0, 0, 1, NULL, NULL);