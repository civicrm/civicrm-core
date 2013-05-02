-- CRM-11354 Fix empty permissions for report instances
UPDATE civicrm_report_instance SET permission = 'access CiviReport'
WHERE report_id = 'survey/detail' and permission = '';

UPDATE civicrm_report_instance SET permission = 'access CiviMail'
WHERE report_id = 'mailing/detail' and permission = '';

UPDATE civicrm_report_instance SET permission = 'access CiviMember'
WHERE report_id = 'member/contributionDetail' and permission = '';

UPDATE civicrm_report_instance SET permission = 'access CiviGrant'
WHERE report_id = 'grant/statistics' and permission = '';

UPDATE civicrm_report_instance SET permission = 'access CiviReport'
WHERE permission = '0' OR permission = '' OR permission IS NULL;