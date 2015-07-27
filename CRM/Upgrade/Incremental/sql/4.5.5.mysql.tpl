{* file to handle db changes in 4.5.5 during upgrade *}

-- https://issues.civicrm.org/jira/browse/CRM-15630

UPDATE civicrm_msg_template SET msg_html = REPLACE(msg_html, 'email=true', 'emailMode=true') WHERE msg_title = 'Petition - signature added';
