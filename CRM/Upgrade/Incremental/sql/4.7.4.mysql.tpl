{* file to handle db changes in 4.7.4 during upgrade *}
{include file='../CRM/Upgrade/4.7.4.msg_template/civicrm_msg_template.tpl'}

// CRM-18037 - update preferred mail format to set as default
UPDATE `civicrm_contact` SET `preferred_mail_format` = 'Both' WHERE `preferred_mail_format` IS NULL;
