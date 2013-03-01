-- CRM-10641 (fix duplicate option values)

SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @maxValue            := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @clientSMSValue     := value FROM civicrm_option_value WHERE name = 'BULK SMS' AND option_group_id = @option_group_id_act;

SELECT @smsVal := value FROM civicrm_option_value  WHERE option_group_id = @option_group_id_act GROUP BY value
HAVING count(value) > 1 AND value = @clientSMSValue;

UPDATE civicrm_option_value
SET value = @maxValue + 1
WHERE value = @smsVal
AND name = 'BULK SMS' AND option_group_id = @option_group_id_act;

SELECT @newClientSMSValue     := value FROM civicrm_option_value WHERE name = 'BULK SMS' AND option_group_id = @option_group_id_act;

UPDATE civicrm_activity 
INNER JOIN civicrm_mailing ON civicrm_activity.source_record_id = civicrm_mailing.id
SET   civicrm_activity.activity_type_id = @newClientSMSValue
WHERE civicrm_activity.activity_type_id = @clientSMSValue;

-- CRM-10671 remove incomplete price set reports (inserted in 4.2 alpha 1)
SELECT @option_group_id_report := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';
DELETE from civicrm_option_value
WHERE name = 'CRM_Report_Form_Price_Lineitem' AND
option_group_id = @option_group_id_report;

DELETE from civicrm_option_value
WHERE name = 'CRM_Report_Form_Price_Contributionbased' AND
option_group_id = @option_group_id_report;

DELETE from civicrm_option_value
WHERE name = 'CRM_Report_Form_Price_Lineitemparticipant' AND
option_group_id = @option_group_id_report;