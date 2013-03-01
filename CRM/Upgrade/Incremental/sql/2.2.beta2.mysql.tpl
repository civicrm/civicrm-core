 -- CRM-4104
SELECT @og_id_cs  := id FROM civicrm_option_group WHERE name = 'custom_search';
SELECT @maxValue  := max(CAST( `value` AS UNSIGNED )) FROM civicrm_option_value WHERE option_group_id = @og_id_cs;
{if $customSearchAbsentAll}
    {if $multilingual}
        INSERT INTO civicrm_option_value
	(option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active) VALUES
	(@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_ZipCodeRange',   'Zip Code Range',                              {/foreach} @maxValue + 1, 'CRM_Contact_Form_Search_Custom_ZipCodeRange',   @maxValue + 1, 1),
	(@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_DateAdded',      'Date Added to CiviCRM',                       {/foreach} @maxValue + 2, 'CRM_Contact_Form_Search_Custom_DateAdded',      @maxValue + 2, 1),
	(@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_MultipleValues', 'Custom Group Multiple Values Listing',        {/foreach} @maxValue + 3, 'CRM_Contact_Form_Search_Custom_MultipleValues', @maxValue + 3, 1),
        (@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_ContribSYBNT',   'Contributions made in Year X and not Year Y', {/foreach} @maxValue + 4, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',   @maxValue + 4, 1);
    {else}
	INSERT INTO civicrm_option_value
	(option_group_id, label, value, name, description, weight, is_active ) VALUES
	(@og_id_cs, 'CRM_Contact_Form_Search_Custom_ZipCodeRange',    @maxValue + 1, 'CRM_Contact_Form_Search_Custom_ZipCodeRange',   'Zip Code Range',                               @maxValue + 1, 1),
        (@og_id_cs, 'CRM_Contact_Form_Search_Custom_DateAdded',       @maxValue + 2, 'CRM_Contact_Form_Search_Custom_DateAdded',      'Date Added to CiviCRM',                        @maxValue + 2, 1),
        (@og_id_cs, 'CRM_Contact_Form_Search_Custom_MultipleValues',  @maxValue + 3, 'CRM_Contact_Form_Search_Custom_MultipleValues', 'Custom Group Multiple Values Listing',         @maxValue + 3, 1),
        (@og_id_cs, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',    @maxValue + 4, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',   'Contributions made in Year X and not Year Y',  @maxValue + 4, 1);
    {/if}
{else if $customSearchAbsent}
    {if $multilingual}
        INSERT INTO civicrm_option_value
        (option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active) VALUES
        (@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_MultipleValues', 'Custom Group Multiple Values Listing',        {/foreach} @maxValue + 1, 'CRM_Contact_Form_Search_Custom_MultipleValues', @maxValue + 1, 1),
        (@og_id_cs, {foreach from=$locales item=locale}'CRM_Contact_Form_Search_Custom_ContribSYBNT',   'Contributions made in Year X and not Year Y', {/foreach} @maxValue + 2, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',   @maxValue + 2, 1);
    {else}
        INSERT INTO civicrm_option_value
	(option_group_id, label, value, name, description, weight, is_active ) VALUES
	(@og_id_cs, 'CRM_Contact_Form_Search_Custom_MultipleValues', @maxValue + 1, 'CRM_Contact_Form_Search_Custom_MultipleValues', 'Custom Group Multiple Values Listing',         @maxValue + 1, 1),
        (@og_id_cs, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',   @maxValue + 2, 'CRM_Contact_Form_Search_Custom_ContribSYBNT',   'Contributions made in Year X and not Year Y',  @maxValue + 2, 1);
    {/if}
{/if}

-- Added dummy processor
INSERT IGNORE INTO `civicrm_payment_processor_type`
    (`name`, `title`, `is_active`, `is_default`, `user_name_label`, `class_name`, `billing_mode`) VALUES
    ('Dummy', 'Dummy Payment Processor', 1, 1, 'User Name', 'Payment_Dummy', 1 );