{* file to handle db changes in 4.6.10 during upgrade *}

-- CRM-17384 - remove duplicate support menu which may have been added in 4.6.9
DELETE n1
FROM civicrm_navigation n1, civicrm_navigation n2
WHERE n1.name = 'Support' AND n1.domain_id = {$domainID} AND n2.name = 'Support' AND n2.domain_id = {$domainID} AND n1.id < n2.id;

-- CRM-17384 - re-add sid in case the site admin deleted the new support menu after upgrading to 4.6.9
UPDATE civicrm_navigation SET url = 'https://civicrm.org/register-your-site?src=iam&sid={ldelim}sid{rdelim}' WHERE name = 'Register your site';
UPDATE civicrm_navigation SET url = 'https://civicrm.org/become-a-member?src=iam&sid={ldelim}sid{rdelim}' WHERE name = 'Join CiviCRM';

--CRM-17357 PHP fatal error during creation of receipt PDF
{include file='../CRM/Upgrade/4.6.10.msg_template/civicrm_msg_template.tpl'}
