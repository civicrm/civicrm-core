--  CRM-4795
--  modify type fee_level of civicrm_participant and amount_level of civicrm_contribution

    ALTER TABLE civicrm_participant MODIFY column fee_level text collate utf8_unicode_ci default NULL COMMENT 'Populate with the label (text) associated with a fee level for paid events with multiple levels. Note that we store the label value and not the key';

    ALTER TABLE civicrm_contribution MODIFY column amount_level text collate utf8_unicode_ci default NULL;

--  subtype upgrade TODOs: 
--  make changes for CRM-4970

--  modify contact_type column definition
    ALTER TABLE  `civicrm_contact` MODIFY column contact_type varchar(64) collate utf8_unicode_ci DEFAULT NULL COMMENT 'Type of Contact';
    
--  add table definiton and data for civicrm_contact_type table    
    CREATE TABLE civicrm_contact_type (
        id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Contact Type ID',
        name varchar(64)    COMMENT 'Internal name of Contact Type (or Subtype).',
        {localize field='label'}label varchar(64)       COMMENT ' localized Name of Contact Type.'{/localize},
        {localize field='description'}description text  COMMENT ' localized Optional verbose description of the type.'{/localize},
        image_URL varchar(255)  COMMENT 'URL of image if any.',
        parent_id int unsigned  COMMENT 'Optional FK to parent contact type.',
        is_active tinyint       COMMENT 'Is this entry active?',
        is_reserved tinyint     COMMENT 'Is this contact type a predefined system type',
        PRIMARY KEY ( id ),
        UNIQUE INDEX contact_type(name),      
        CONSTRAINT FK_civicrm_contact_type_parent_id FOREIGN KEY (parent_id) REFERENCES civicrm_contact_type(id)   
    )  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;

    INSERT INTO civicrm_contact_type 
        ( id, name,{localize field='label'}`label`{/localize},image_URL, parent_id, is_active,is_reserved ) 
    VALUES
        (1, 'Individual',    {localize}'Individual'{/localize},  NULL, NULL, 1,1),
        (2, 'Household',     {localize}'Household'{/localize},   NULL, NULL, 1,1),
        (3, 'Organization',  {localize}'Organization'{/localize},NULL, NULL, 1,1);
        
    
--  CRM-5218
--  added menu for contact types in navigation
    SELECT @domain_id   := min(id) FROM civicrm_domain;
    SELECT @nav_ol      := id FROM civicrm_navigation WHERE name = 'Option Lists';
    SELECT @nav_ol_wt   := max(weight) from civicrm_navigation WHERE parent_id = @nav_ol;
    INSERT INTO `civicrm_navigation`
        ( domain_id, url, label, name,permission, permission_operator, parent_id, is_active, has_separator, weight ) 
    VALUES
        (  @domain_id,'civicrm/admin/options/subtype&reset=1', 'Contact Types', 'Contact Types', 'administer CiviCRM', '', @nav_ol, '1', NULL, @nav_ol_wt+1 );
	
--  make changes for CRM-5100 
    ALTER TABLE `civicrm_relationship_type`
        ADD `contact_sub_type_a` varchar(64) collate utf8_unicode_ci DEFAULT NULL AFTER `contact_type_b`,
        ADD `contact_sub_type_b` varchar(64) collate utf8_unicode_ci DEFAULT NULL AFTER `contact_sub_type_a`;
      
--  Upgrade FCKEditor to CKEditor CRM-5226
    {if $multilingual}
        {foreach from=$locales item=locale}
        UPDATE civicrm_option_value SET label_{$locale} ='CKEditor' WHERE label_{$locale} = 'FCKEditor';
        {/foreach}
    {else}
        UPDATE civicrm_option_value SET label ='CKEditor' WHERE label = 'FCKEditor';
    {/if}
--  CRM-5106
--  Added Autocomplete search options in civicrm_preferences 'Admin Search Settings' form

    ALTER TABLE `civicrm_preferences`
        ADD `contact_autocomplete_options` VARCHAR( 255 ) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'What Autocomplete has to return';

--  Added default value checked for sort_name and email
    UPDATE `civicrm_preferences` SET `contact_autocomplete_options` = '12' WHERE `civicrm_preferences`.`id` =1 LIMIT 1;

--  Insert values for option group
    INSERT INTO 
        `civicrm_option_group` (`name`, {localize field='description'}`description`{/localize}, `is_reserved`, `is_active`) 
    VALUES 
        ('contact_autocomplete_options', {localize}'Autocomplete Contact Search'{/localize}   , 0, 1);
   
    SELECT @option_group_id_acsOpt := max(id) from civicrm_option_group where name = 'contact_autocomplete_options';

    INSERT INTO `civicrm_option_value`
        (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
    VALUES
        (@option_group_id_acsOpt, {localize}'Email Address'{/localize}  , 2, 'email',           NULL, 0, NULL, 2,  0, 0, 1, NULL, NULL),
        (@option_group_id_acsOpt, {localize}'Phone'{/localize}          , 3, 'phone',           NULL, 0, NULL, 3,  0, 0, 1, NULL, NULL),
        (@option_group_id_acsOpt, {localize}'Street Address'{/localize} , 4, 'street_address',  NULL, 4, NULL, 0,  0, 0, 1, NULL, NULL),
        (@option_group_id_acsOpt, {localize}'City'{/localize}           , 5, 'city',            NULL, 0, NULL, 5,  0, 0, 1, NULL, NULL),
        (@option_group_id_acsOpt, {localize}'State/Province'{/localize} , 6, 'state_province',  NULL, 6, NULL, 0,  0, 0, 1, NULL, NULL),
        (@option_group_id_acsOpt, {localize}'Country'{/localize}        , 7, 'country',         NULL, 0, NULL, 7,  0, 0, 1, NULL, NULL);

--  CRM-5095
    ALTER TABLE `civicrm_price_set`
        ADD `extends` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL COMMENT 'What components are using this price set?';
 
    UPDATE `civicrm_price_set` SET extends = (select id from civicrm_component where name = 'CiviEvent');

--  CRM-4160
    ALTER TABLE `civicrm_acl`
        MODIFY `operation` enum('All', 'View', 'Edit', 'Create', 'Delete', 'Grant', 'Revoke', 'Search') COLLATE utf8_unicode_ci NOT NULL   COMMENT 'What operation does this ACL entry control?';

--  CRM-5285
    UPDATE civicrm_state_province SET name = 'Haifa' where id = 3115;

--  CRM-5287
    UPDATE civicrm_state_province SET name = 'Jerusalem' where id = 3117;

--  CRM-5224
    /* Set references to obsolete UK counties to NULL */
    UPDATE `civicrm_address` SET `state_province_id` = NULL WHERE `state_province_id` IN
        ('2596', '2599', '2600', '2601', '2602', '2603', '2604', '2605', '2607', '2608', '2609', '2610', '2611',
        '2613', '2614', '2615', '2616', '2617', '2618', '2619', '2621', '2623', '2624', '2625', '2627', '2628', 
        '2629', '2630', '2631', '2632', '2633', '2636', '2637', '2638', '2640', '2641', '2642', '2644', '2645', 
        '2646', '2650', '2653', '2656', '2658', '2667', '2672', '2673', '2676', '2677', '2679', '2680', '2681', 
        '2683', '2684', '2685', '2686', '2690', '2691', '2693', '2695', '2696', '2697', '2698', '2700', '2701',
        '2701', '2702', '2703', '2704', '2706', '2707', '2708', '2710', '2711', '2713', '2714', '2716', '2717',
        '2719', '2720', '2721', '2722', '2724', '2725', '2727', '2728', '2729', '2730', '2731', '2732', '2733',
        '2736', '2737', '2739', '2740', '2741', '2745', '2751', '2753', '2754', '2755', '2756', '2758', '2759',
        '2760', '2762', '2763', '2764', '2765', '2767', '2768', '2769', '2771', '2772', '2775', '2776', '2781', 
        '2782', '2783', '2784', '2787', '2788', '2789', '2790', '2792', '2794', '2795', '2796', '2797', '2798', 
        '2799', '2800', '2801', '2802', '2803', '2805', '2806', '2807', '2808', '2809', '2810', '2816', '2817', 
        '2819', '2820', '2821', '2822', '2824', '2825', '9987', '9995', '9996', '9997', '2812', '2718', '2715' );

    /* Delete obsolete UK counties */
    DELETE FROM `civicrm_state_province` WHERE `id` IN
        ('2596', '2599', '2600', '2601', '2602', '2603', '2604', '2605', '2607', '2608', '2609', '2610', '2611',
        '2613', '2614', '2615', '2616', '2617', '2618', '2619', '2621', '2623', '2624', '2625', '2627', '2628', 
        '2629', '2630', '2631', '2632', '2633', '2636', '2637', '2638', '2640', '2641', '2642', '2644', '2645', 
        '2646', '2650', '2653', '2656', '2658', '2667', '2672', '2673', '2676', '2677', '2679', '2680', '2681', 
        '2683', '2684', '2685', '2686', '2690', '2691', '2693', '2695', '2696', '2697', '2698', '2700', '2701',
        '2701', '2702', '2703', '2704', '2706', '2707', '2708', '2710', '2711', '2713', '2714', '2716', '2717',
        '2719', '2720', '2721', '2722', '2724', '2725', '2727', '2728', '2729', '2730', '2731', '2732', '2733',
        '2736', '2737', '2739', '2740', '2741', '2745', '2751', '2753', '2754', '2755', '2756', '2758', '2759',
        '2760', '2762', '2763', '2764', '2765', '2767', '2768', '2769', '2771', '2772', '2775', '2776', '2781', 
        '2782', '2783', '2784', '2787', '2788', '2789', '2790', '2792', '2794', '2795', '2796', '2797', '2798', 
        '2799', '2800', '2801', '2802', '2803', '2805', '2806', '2807', '2808', '2809', '2810', '2816', '2817', 
        '2819', '2820', '2821', '2822', '2824', '2825', '9987', '9995', '9996', '9997', '2812', '2718', '2715' );

    /* Update the names of several existing UK counties */
    UPDATE `civicrm_state_province` SET `name`='Gwent'              WHERE `id`='2612';
    UPDATE `civicrm_state_province` SET `name`='Bristol, City of'   WHERE `id`='2620';
    UPDATE `civicrm_state_province` SET `name`='Co Londonderry'     WHERE `id`='2648';
    UPDATE `civicrm_state_province` SET `name`='Na h-Eileanan Siar' WHERE `id`='2666';
    UPDATE `civicrm_state_province` SET `name`='Glasgow City'       WHERE `id`='2674';
    UPDATE `civicrm_state_province` SET `name`='Mid Glamorgan'      WHERE `id`='2804';
    UPDATE `civicrm_state_province` SET `name`='Greater London'     WHERE `id`='9999';
    UPDATE `civicrm_state_province` SET `name`='County Durham'      WHERE `id`='2657';

    /* Create additional UK counties */
    INSERT INTO `civicrm_state_province`
        (id, `name`, `abbreviation`, `country_id`)
    VALUES
        (10013, 'Clwyd',             'CLD', 1226),
        (10014, 'Dyfed',             'DFD', 1226),
        (10015, 'South Glamorgan',   'GNS', 1226);

--  CRM-5288
    SELECT @domain_id := min(id) FROM civicrm_domain;
    SELECT @nav_contrbutionID    := id FROM civicrm_navigation WHERE name = 'Contributions';
    SELECT @nav_contribution_wt  := max(weight) from civicrm_navigation WHERE parent_id = @nav_contrbutionID;
    
    UPDATE civicrm_navigation
        SET has_separator = 1
        WHERE civicrm_navigation.parent_id= @nav_contrbutionID AND civicrm_navigation.weight = @nav_contribution_wt;

    INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
    VALUES
        ( @domain_id, 'civicrm/admin/price&reset=1&action=add', '{ts escape="sql"}New Price Set{/ts}',     'New Price Set',     'access CiviContribute,administer CiviCRM', 'AND',  @nav_contrbutionID, '1', NULL, @nav_contribution_wt+1 ),
        ( @domain_id, 'civicrm/admin/price&reset=1',            '{ts escape="sql"}Manage Price Sets{/ts}', 'Manage Price Sets', 'access CiviContribute,administer CiviCRM', 'AND',  @nav_contrbutionID, '1', NULL, @nav_contribution_wt+2 );
     
    
    SELECT @nav_contrbutionID_admin    := id FROM civicrm_navigation WHERE name = 'CiviContribute';
    SELECT @nav_contribution_wt_admin  := max(weight) from civicrm_navigation WHERE parent_id = @nav_contrbutionID_admin;
    
    UPDATE civicrm_navigation
        SET has_separator = 1
        WHERE civicrm_navigation.parent_id= @nav_contrbutionID_admin AND civicrm_navigation.weight = @nav_contribution_wt_admin;

    INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
    VALUES
        ( @domain_id, 'civicrm/admin/price&reset=1&action=add', '{ts escape="sql"}New Price Set{/ts}',     'New Price Set',     'access CiviContribute,administer CiviCRM', 'AND',  @nav_contrbutionID_admin, '1', NULL, @nav_contribution_wt_admin+1 ),
        ( @domain_id, 'civicrm/admin/price&reset=1',            '{ts escape="sql"}Manage Price Sets{/ts}', 'Manage Price Sets', 'access CiviContribute,administer CiviCRM', 'AND',  @nav_contrbutionID_admin, '1', NULL, @nav_contribution_wt_admin+2 );
    
    
    SELECT @nav_customize_admin    := id FROM civicrm_navigation WHERE name = 'Customize';
    SELECT @nav_customize_wt_admin := max(weight) from civicrm_navigation WHERE parent_id = @nav_customize_admin;
    INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
    VALUES        
        ( @domain_id, 'civicrm/admin/price&reset=1', '{ts escape="sql"}Price Sets{/ts}', 'Price Sets', 'administer CiviCRM', '',  @nav_customize_admin, '1', NULL, @nav_customize_wt_admin+1 );
    
    UPDATE civicrm_navigation
        SET url = 'civicrm/admin/price&reset=1'
        WHERE civicrm_navigation.url = 'civicrm/event/price&reset=1';
        
    CREATE TABLE civicrm_acl_contact_cache (
        id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'primary key',
        user_id int unsigned    COMMENT 'FK to civicrm_contact (could be null for anon user)',
        contact_id int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
        operation enum('All', 'View', 'Edit', 'Create', 'Delete', 'Grant', 'Revoke') NOT NULL   COMMENT 'What operation does this user have permission on?' 
        ,
        PRIMARY KEY ( id ) ,
        UNIQUE INDEX UI_user_contact_operation(user_id, contact_id, operation ) ,     
        CONSTRAINT FK_civicrm_acl_contact_cache_user_id FOREIGN KEY (user_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE,      
        CONSTRAINT FK_civicrm_acl_contact_cache_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE  
    )  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
    
-- CRM-4690
    INSERT INTO civicrm_payment_processor_type
        ( name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label,  class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur, payment_type)
    VALUES
        ( 'Realex', 'Realex Payment', NULL, 1, 0, 'Merchant ID', 'Password', NULL, 'Account', 'Payment_Realex', 'https://epage.payandshop.com/epage.cgi', NULL, NULL, NULL, 'https://epage.payandshop.com/epage-remote.cgi', NULL, NULL, NULL, 1, 0, 1);
    
-- CRM-4802
    UPDATE civicrm_payment_processor_type
        SET url_recur_default      = 'https://www.paypal.com/',
            url_recur_test_default = 'https://www.sandbox.paypal.com/',
            is_recur = 1
        WHERE name = 'PayPal';
    
    UPDATE civicrm_payment_processor
        SET is_recur  = 1,
            url_recur = 'https://www.paypal.com/'
        WHERE payment_processor_type = 'PayPal' AND is_test = 0;
    
    UPDATE civicrm_payment_processor
        SET is_recur  = 1,
            url_recur = 'https://www.sandbox.paypal.com/'
        WHERE payment_processor_type = 'PayPal' AND is_test = 1;
                    
--  CRM-5313 
--  migrate the contribution id's to participant id's in lineitem table
    UPDATE civicrm_line_item AS li
        LEFT JOIN civicrm_participant_payment AS pp ON (pp.contribution_id = li.entity_id)
        SET li.entity_id    = pp.participant_id,
            li.entity_table = 'civicrm_participant'
        WHERE pp.contribution_id = li.entity_id AND
              li.entity_table    = 'civicrm_contribution';

--  CRM-5317    	
--  copy name to value, since we want to use value instead of name.
    UPDATE  civicrm_option_value as vals
        LEFT  JOIN  civicrm_option_group as groups ON ( groups.id = vals.option_group_id )
        INNER JOIN  civicrm_price_field fields ON ( groups.name = CONCAT( 'civicrm_price_field.amount.', fields.id ) ) 
        SET  vals.value=vals.name;
       
--  CRM-5244      
    ALTER TABLE civicrm_mail_settings
        ADD `domain_id` int(10) unsigned NOT NULL COMMENT 'Which Domain is this match entry for' AFTER id;

    UPDATE civicrm_mail_settings SET domain_id = @domain_id;

-- Date migration CRM-4312    
    ALTER TABLE civicrm_custom_field
        CHANGE date_parts date_format VARCHAR( 64 )  NULL DEFAULT NULL COMMENT 'date format for custom date',        
        ADD time_format int unsigned  COMMENT 'time format for custom date' AFTER date_format;

    ALTER TABLE `civicrm_preferences_date` 
        CHANGE `format` `date_format` VARCHAR( 64 )  NULL DEFAULT NULL COMMENT 'The date type',
        ADD `time_format` VARCHAR( 64 ) NULL DEFAULT NULL COMMENT 'store time format' AFTER `date_format`;
        ALTER TABLE `civicrm_preferences_date`
            DROP `minute_increment`;
    
    DELETE FROM civicrm_preferences_date WHERE name IN ('datetime','duration','fixed','manual' );
    
    UPDATE civicrm_preferences_date
        SET date_format = ''
        WHERE name IN ( 'activityDate', 'activityDatetime', 'birth', 'custom', 'mailing', 'relative' );
                        
    UPDATE civicrm_preferences_date SET name = 'searchDate' WHERE name = 'relative';
    UPDATE civicrm_preferences_date SET name = 'activityDateTime' WHERE name = 'activityDatetime';
    UPDATE civicrm_preferences_date SET time_format = 1 WHERE name = 'activityDatetime';
--  CRM-5263    
    ALTER TABLE civicrm_country
	ADD is_province_abbreviated tinyint(4) default '0' COMMENT 'Should state/province be displayed as abbreviation for contacts from this country?';

-- message templates, CRM-3507
    ALTER TABLE civicrm_msg_template
        CHANGE msg_subject msg_subject TEXT NULL COMMENT 'Subject for email message.',
        ADD workflow_id int(10) unsigned default NULL COMMENT 'a pseudo-FK to civicrm_option_value AFTER is_active',
        ADD is_default  tinyint(4) default '1'  COMMENT 'is this the default message template for the workflow referenced by workflow_id?' AFTER workflow_id,
        ADD is_reserved tinyint(4) default NULL COMMENT 'is this the reserved message template which we ship for the workflow referenced by workflow_id?' AFTER is_default;

    {include file='../CRM/Upgrade/3.1.alpha1.msg_template/civicrm_msg_template.tpl'}
