-- CRM-7171

ALTER TABLE `civicrm_mailing`
   ADD `scheduled_date` datetime default NULL COMMENT 'Date and time this mailing was scheduled.',
   ADD `approver_id` int(10) unsigned default NULL COMMENT 'FK to Contact ID who approved this mailing',
   ADD CONSTRAINT `FK_civicrm_mailing_approver_id` FOREIGN KEY (`approver_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
   ADD `approval_date` datetime default NULL COMMENT 'Date and time this mailing was approved.',
   ADD `approval_status_id` int unsigned default NULL COMMENT 'The status of this mailing. values: none, approved, rejected',
   ADD `approval_note` longtext default NULL COMMENT 'Note behind the decision.',
   ADD `visibilty` enum('User and User Admin Only','Public User Pages') default 'User and User Admin Only' COMMENT 'In what context(s) is the mailing contents visible (online viewing)';

UPDATE  `civicrm_navigation` SET  `permission` =  'access CiviMail,create mailings,approve mailings,schedule mailings', `permission_operator` =  'OR' WHERE  name = 'Mailings';

UPDATE  `civicrm_navigation` SET  `permission` =  'access CiviMail,create mailings', `permission_operator` =  'OR' WHERE  name = 'Draft and Unscheduled Mailings';

UPDATE  `civicrm_navigation` SET  `permission` =  'access CiviMail,approve mailings', `permission_operator` =  'OR' WHERE  name = 'Scheduled and Sent Mailings';

--CRM-7180, Change Participant Listing Templates menu title`

UPDATE `civicrm_navigation` SET `label` = '{ts escape="sql"}Participant Listing Options{/ts}', `name`= 'Participant Listing Options' WHERE name = 'Participant Listing Templates';

--CRM--7197
{if $dropMailingIndex}
ALTER TABLE civicrm_mailing_job
DROP FOREIGN KEY parent_id,
DROP INDEX parent_id ,
ADD CONSTRAINT FK_civicrm_mailing_job_parent_id
FOREIGN KEY (parent_id) REFERENCES civicrm_mailing_job (id) ON DELETE CASCADE;
{/if}
-- CRM-7206
UPDATE  civicrm_membership_type
   SET  relationship_type_id = NULL,  relationship_direction = NULL
 WHERE  relationship_type_id = 'Array' OR relationship_type_id IS NULL;

-- CRM-7171, Rules Mailing integration
{if $multilingual}
    INSERT INTO civicrm_option_group
        ( name,                   {foreach from=$locales item=locale}description_{$locale},   {/foreach} is_reserved, is_active)
    VALUES
        ( 'mail_approval_status', {foreach from=$locales item=locale}'CiviMail Approval Status',       {/foreach} 0, 1 );
{else}
    INSERT INTO civicrm_option_group
        (name, description, is_reserved, is_active )
    VALUES
        ('mail_approval_status', 'CiviMail Approval Status', 0, 1 );
{/if}

SELECT @mailCompId  := max(id) FROM civicrm_component where name = 'CiviMail';
SELECT @option_group_id_approvalStatus := max(id) from civicrm_option_group where name = 'mail_approval_status';

{if $multilingual}
    INSERT INTO civicrm_option_value
    (option_group_id, {foreach from=$locales item=locale}label_{$locale}, {/foreach} name, value, weight, is_active, component_id, is_default )

    VALUES
        (@option_group_id_approvalStatus, {foreach from=$locales item=locale}'Approved', {/foreach} 'Approved', 1, 1, 1, @mailCompId, 1 ),
        (@option_group_id_approvalStatus, {foreach from=$locales item=locale}'Rejected', {/foreach} 'Rejected', 2, 2, 1, @mailCompId, 0 ),
        (@option_group_id_approvalStatus, {foreach from=$locales item=locale}'None', {/foreach} 'None', 3, 3, 1, @mailCompId, 0 );

{else}
    INSERT INTO civicrm_option_value
    (option_group_id, label, name, value, weight, is_active, component_id, is_default )

    VALUES
        (@option_group_id_approvalStatus , '{ts escape="sql"}Approved{/ts}', 'Approved', 1,  1,   1, @mailCompId, 1 ),
        (@option_group_id_approvalStatus , '{ts escape="sql"}Rejected{/ts}', 'Rejected', 2,  2,   1, @mailCompId, 0 ),
  (@option_group_id_approvalStatus , '{ts escape="sql"}None{/ts}',     'None',    3,  3,   1, @mailCompId, 0 );
{/if}

-- CRM-7170
UPDATE civicrm_report_instance SET form_values = '{literal}a:39:{s:6:"fields";a:5:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:11:"bounce_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:19:"bounce_type_name_op";s:2:"eq";s:22:"bounce_type_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:17:"custom_9_relative";s:1:"0";s:13:"custom_9_from";s:0:"";s:11:"custom_9_to";s:0:"";s:12:"custom_10_op";s:2:"in";s:15:"custom_10_value";a:0:{}s:12:"custom_11_op";s:3:"has";s:15:"custom_11_value";s:0:"";s:11:"description";s:26:"Bounce Report for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}'  WHERE  report_id = 'Mailing/bounce';

UPDATE civicrm_report_instance SET form_values = '{literal}a:25:{s:6:"fields";a:5:{s:4:"name";s:1:"1";s:11:"queue_count";s:1:"1";s:15:"delivered_count";s:1:"1";s:12:"bounce_count";s:1:"1";s:10:"open_count";s:1:"1";}s:15:"is_completed_op";s:2:"eq";s:18:"is_completed_value";s:1:"1";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:9:"status_op";s:3:"has";s:12:"status_value";s:8:"Complete";s:11:"is_test_min";s:0:"";s:11:"is_test_max";s:0:"";s:10:"is_test_op";s:3:"lte";s:13:"is_test_value";s:1:"0";s:19:"start_date_relative";s:9:"this.year";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:17:"end_date_relative";s:9:"this.year";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:11:"description";s:31:"Summary statistics for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}'  WHERE  report_id = 'Mailing/summary';

UPDATE civicrm_report_instance SET form_values = '{literal}a:37:{s:6:"fields";a:4:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:17:"custom_9_relative";s:1:"0";s:13:"custom_9_from";s:0:"";s:11:"custom_9_to";s:0:"";s:12:"custom_10_op";s:2:"in";s:15:"custom_10_value";a:0:{}s:12:"custom_11_op";s:3:"has";s:15:"custom_11_value";s:0:"";s:11:"description";s:49:"Display contacts who opened emails from a mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}'  WHERE  report_id = 'Mailing/opened';

UPDATE civicrm_report_instance SET form_values = '{literal}a:37:{s:6:"fields";a:5:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:5:"email";s:1:"1";s:3:"url";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:17:"custom_9_relative";s:1:"0";s:13:"custom_9_from";s:0:"";s:11:"custom_9_to";s:0:"";s:12:"custom_10_op";s:2:"in";s:15:"custom_10_value";a:0:{}s:12:"custom_11_op";s:3:"has";s:15:"custom_11_value";s:0:"";s:11:"description";s:32:"Display clicks from each mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}'  WHERE  report_id = 'Mailing/clicks';

-- CRM-7115
UPDATE  civicrm_payment_processor
   SET  is_recur = 1,
     payment_processor_type =  'AuthNet'
 WHERE  payment_processor_type = 'AuthNet_AIM';

UPDATE  civicrm_payment_processor_type
   SET  is_recur = 1,
        name = 'AuthNet',
        title = '{ts escape="sql"}Authorize.Net{/ts}'
 WHERE  name = 'AuthNet_AIM';

ALTER TABLE `civicrm_contribution_recur` ADD `payment_processor_id` INT( 10 ) UNSIGNED NULL COMMENT 'Foreign key to civicrm_payment_processor.id';
ALTER TABLE `civicrm_contribution_recur` ADD CONSTRAINT `FK_civicrm_contribution_recur_payment_processor_id` FOREIGN KEY (`payment_processor_id`) REFERENCES `civicrm_payment_processor` (`id`) ON DELETE SET NULL;

-- Pickup payment processor and fill payment processor id in recur contrib table.

    UPDATE  civicrm_contribution_recur recur
INNER JOIN  civicrm_contribution contrib ON ( contrib.contribution_recur_id = recur.id )
INNER JOIN  civicrm_entity_financial_trxn eft ON ( eft.entity_id = contrib.id AND entity_table = 'civicrm_contribution' )
INNER JOIN  civicrm_financial_trxn trxn ON ( trxn.id = eft.financial_trxn_id )
INNER JOIN  civicrm_payment_processor processor ON ( processor.payment_processor_type = trxn.payment_processor
                                                     AND  processor.is_test = recur.is_test )
       SET  recur.payment_processor_id = processor.id;

-- done w/ CRM-7115

-- CRM-7137
 ALTER TABLE `civicrm_membership`
   ADD `contribution_recur_id` int(10) unsigned default NULL COMMENT 'Conditional foreign key to civicrm_contribution_recur.id.',
   ADD CONSTRAINT `FK_civicrm_membership_contribution_recur_id` FOREIGN KEY (`contribution_recur_id`) REFERENCES `civicrm_contribution_recur` (`id`) ON DELETE SET NULL;

 ALTER TABLE `civicrm_membership_type`
   ADD `auto_renew` TINYINT (4) NULL DEFAULT '0',
   ADD `autorenewal_msg_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_msg_template.id',
   ADD CONSTRAINT `FK_civicrm_membership_autorenewal_msg_id` FOREIGN KEY (`autorenewal_msg_id`) REFERENCES `civicrm_msg_template` (`id`) ON DELETE SET NULL;

-- CRM-7137

  {include file='../CRM/Upgrade/3.3.2.msg_template/civicrm_msg_template.tpl'}