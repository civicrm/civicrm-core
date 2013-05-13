-- CRM-4374

SELECT @og_id_at   := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val_at := max(round(value)) from civicrm_option_value where option_group_id=@og_id_at;
SELECT @max_wt_at  := max(weight) from civicrm_option_value where option_group_id=@og_id_at;

SELECT @og_id_mp   := max(id) from civicrm_option_group where name = 'mail_protocol';
SELECT @max_val_mp := max(round(value)) from civicrm_option_value where option_group_id=@og_id_mp;
SELECT @max_wt_mp  := max(weight) from civicrm_option_value where option_group_id=@og_id_mp;

SELECT @civicase_component_id := id from civicrm_component where name = 'CiviCase';

 {if $multilingual}
    INSERT INTO civicrm_option_value
      (option_group_id, {foreach from=$locales item=locale}label_{$locale},{/foreach}  value, name, filter, weight, is_reserved, is_active, component_id) VALUES
      (@og_id_at, {foreach from=$locales item=locale}'Change Case Start Date',{/foreach} @max_val_at + 1, 'Change Case Start Date', 0, @max_wt_at + 1, 1, 1, @civicase_component_id),
      (@og_id_mp, {foreach from=$locales item=locale}'Localdir',{/foreach} @max_val_mp + 1, 'Localdir', 0, @max_wt_mp + 1, 1, 1, NULL);
 {else}
    INSERT INTO `civicrm_option_value`
      (`option_group_id`, `label`, `value`, `name`, `filter`, `weight`, `is_reserved`, `is_active`, component_id) VALUES
      (@og_id_at, 'Change Case Start Date', @max_val_at + 1, 'Change Case Start Date', 0, @max_wt_at + 1, 1, 1, @civicase_component_id ),
      (@og_id_mp, 'Localdir',               @max_val_mp + 1, 'Localdir',               0, @max_wt_mp + 1, 1, 1, NULL);
 {/if}

--CRM-4373
--Adding new custom search for FullText Search.

SELECT @og_id_cs  := id FROM civicrm_option_group WHERE name = 'custom_search';
SELECT @maxValue  := max(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @og_id_cs;

 {if $multilingual}
     INSERT INTO civicrm_option_value
        (option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active) VALUES
           (@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_FullText', 'Full-text Search', {/foreach} @maxValue + 1, 'CRM_Contact_Form_Search_Custom_FullText', @maxValue + 1, 1);
 {else}
     INSERT INTO civicrm_option_value
         (option_group_id, label, value, name, description, weight, is_active ) VALUES
             (@og_id_cs, 'CRM_Contact_Form_Search_Custom_FullText', @maxValue + 1, 'CRM_Contact_Form_Search_Custom_FullText', 'Full-text Search', @maxValue + 1, 1);
 {/if}
