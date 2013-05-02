-- This file contains upgrade queries for db changes introduced after 2.2.beta2

-- CRM-4167

ALTER TABLE `civicrm_event`
  ADD `allow_same_participant_emails` tinyint(4) default '0' COMMENT 'if true - allows the user to register multiple registrations from same email address.';

-- CRM-4166

INSERT INTO  `civicrm_payment_processor_type` 
  (name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label, class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur ) VALUES
  ('Elavon','{ts escape="sql"}Elavon Payment Processor{/ts}','{ts escape="sql"}Elavon / Nova Virtual Merchant{/ts}',1,0,'{ts escape="sql"}SSL Merchant ID {/ts}','{ts escape="sql"}SSL User ID{/ts}','{ts escape="sql"}SSL PIN{/ts}',NULL,'Payment_Elavon','https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',NULL,NULL,NULL,'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',NULL,NULL,NULL,1,0)
  ON DUPLICATE KEY UPDATE civicrm_payment_processor_type.name='Elavon';

-- CRM-4165

SELECT @og_id_at := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val  := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @og_id_at;
SELECT @max_wt   := MAX(op.weight) FROM civicrm_option_value op WHERE op.option_group_id = @og_id_at;
SELECT @mem_comp_id := id from civicrm_component where name = 'CiviMember';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach}  value, name, filter, weight, is_reserved, component_id) VALUES
    (@og_id_at, {foreach from=$locales item=locale}'Membership Renewal Reminder', 'offline membership renewal reminder.',{/foreach}  @max_val + 1, 'Membership Renewal Reminder', 1, @max_wt + 1, 1, @mem_comp_id);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id, label, value, name, filter, weight, description, is_reserved, component_id) VALUES
    (@og_id_at, 'Membership Renewal Reminder', @max_val + 1, 'Membership Renewal Reminder', 1, @max_wt + 1, 'offline membership renewal reminder.', 1, @mem_comp_id);
{/if}

-- CRM-3546
{if $customDataType }
    {if $multilingual}
    INSERT INTO civicrm_option_group (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active) VALUES 
    ('custom_data_type', {foreach from=$locales item=locale}'Custom Data Type',{/foreach} 0, 1 );

    SELECT @option_group_id_cdt := id from civicrm_option_group where name = 'custom_data_type';
    INSERT INTO civicrm_option_value
        (option_group_id,  {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name, weight) VALUES
        (@option_group_id_cdt, {foreach from=$locales item=locale}'Participant Role', {/foreach} 1, 'ParticipantRole', 1),
        (@option_group_id_cdt, {foreach from=$locales item=locale}'Participant Event Name',{/foreach} 2, 'ParticipantEventName', 2);
    {else}
    INSERT INTO `civicrm_option_group` 
        (`name`, `description`, `is_reserved`, `is_active`) VALUES 
        ('custom_data_type' , 'Custom Data Type', 0, 1);

    SELECT @option_group_id_cdt := id from civicrm_option_group where name = 'custom_data_type';

    INSERT INTO 
       `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
    VALUES
       (@option_group_id_cdt, 'Participant Role', '1', 'ParticipantRole', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL ),
       (@option_group_id_cdt, 'Participant Event Name', '2', 'ParticipantEventName', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL );
    {/if}
{/if}