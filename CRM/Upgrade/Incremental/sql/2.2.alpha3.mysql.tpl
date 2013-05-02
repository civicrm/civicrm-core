-- Do not make any changes to this file once alpha3 is tagged
-- CRM-3546 CRM-2105 CRM-3248 CRM-3869 CRM-4013
 
 {if $multilingual}
   INSERT INTO civicrm_option_group (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active) VALUES ('mail_protocol', {foreach from=$locales item=locale}'Mail Protocol',{/foreach} 0, 1 );
 {else}
   INSERT INTO civicrm_option_group (name, description, is_reserved, is_active ) VALUES ('mail_protocol', 'Mail Protocol', 0, 1 );
 {/if}
 
 UPDATE civicrm_option_group SET is_reserved = 0, is_active = 1 WHERE name IN( 'mail_protocol', 'visibility', 'greeting_type', 'phone_type' );
 UPDATE civicrm_option_group SET is_active   = 1 WHERE name = 'encounter_medium';
 SELECT @option_group_id_mp := max(id) from civicrm_option_group where name = 'mail_protocol';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,     {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,       weight) VALUES
    (@option_group_id_mp, {foreach from=$locales item=locale}'IMAP',{/foreach}          1,     'IMAP',     1),
    (@option_group_id_mp, {foreach from=$locales item=locale}'Maildir',{/foreach}       2,     'Maildir',  2),
    (@option_group_id_mp, {foreach from=$locales item=locale}'POP3',{/foreach}          3,     'POP3',     3);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,     label,       value, name,       weight) VALUES
    (@option_group_id_mp, 'IMAP' ,     1,     'IMAP',     1),
    (@option_group_id_mp, 'Maildir',   2,     'Maildir',  2),
    (@option_group_id_mp, 'POP3'   ,   3,     'POP3',     3);
{/if}

ALTER TABLE `civicrm_domain` 
  MODIFY version varchar(32) COMMENT 'The civicrm version this instance is running';

-- CRM-3989
ALTER TABLE `civicrm_pcp_block`
  ADD notify_email varchar(255) DEFAULT NULL COMMENT 'If set, notification is automatically emailed to this email-address on create/update Personal Campaign Page';

-- PCP userDashboard Option

 SELECT @option_group_id_udOpt  := max(id) from civicrm_option_group where name = 'user_dashboard_options';
 SELECT @maxValue  := max(value) from civicrm_option_value where option_group_id=@option_group_id_udOpt ;

{if $multilingual}
  INSERT INTO civicrm_option_value 
    (option_group_id, {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name, weight, is_reserved, is_active) VALUES
    (@option_group_id_udOpt, {foreach from=$locales item=locale}'Personal Campaign Pages',{/foreach} @maxValue + 1,'PCP', @maxValue + 1, 0, 1);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,        label,                    value,          name,  weight, is_reserved, is_active) VALUES
    (@option_group_id_udOpt, 'Personal Campaign Pages', @maxValue + 1, 'PCP', @maxValue + 1, 0, 1 );
{/if}

-- PCP Status Option Group
{if $multilingual}
  INSERT INTO civicrm_option_group 
    (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active) VALUES 
    ('pcp_status', {foreach from=$locales item=locale}'PCP Status',{/foreach} 0, 1);

  SELECT @option_group_id_pcp  := max(id) from civicrm_option_group where name = 'pcp_status';
 
  INSERT INTO civicrm_option_value
    (option_group_id, {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name, weight, is_reserved, is_active) VALUES
    (@option_group_id_pcp, {foreach from=$locales item=locale}'Waiting Review',{/foreach} 1, 'Waiting Review', 1, 1, 1 ),
    (@option_group_id_pcp, {foreach from=$locales item=locale}'Approved',{/foreach}       2, 'Approved',       2, 1, 1 ),
    (@option_group_id_pcp, {foreach from=$locales item=locale}'Not Approved',{/foreach}   3, 'Not Approved',   3, 1, 1 );
{else}
  INSERT INTO civicrm_option_group 
    ( name, description, is_reserved, is_active) VALUES
    ('pcp_status','PCP Status', 0, 1 );

  SELECT @option_group_id_pcp  := max(id) from civicrm_option_group where name = 'pcp_status';

  INSERT INTO civicrm_option_value 
    (option_group_id, label, value, name, weight, is_reserved, is_active) VALUES
    (@option_group_id_pcp, 'Waiting Review', 1, 'Waiting Review',  1, 1, 1 ),
    (@option_group_id_pcp, 'Approved'      , 2, 'Approved'      ,  2, 1, 1 ),
    (@option_group_id_pcp, 'Not Approved'  , 3, 'Not Approved'  ,  3, 1, 1 );
{/if}