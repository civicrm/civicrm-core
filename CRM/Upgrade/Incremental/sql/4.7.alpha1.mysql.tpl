{* file to handle db changes in 4.7.alpha1 during upgrade *}

-- CRM-16354
SELECT @option_group_id_wysiwyg := max(id) from civicrm_option_group where name = 'wysiwyg_editor';

UPDATE civicrm_option_value SET name = 'Textarea', {localize field='label'}label = 'Textarea'{/localize}
  WHERE value = 1 AND option_group_id = @option_group_id_wysiwyg;

DELETE FROM civicrm_option_value WHERE name IN ('Joomla Default Editor', 'Drupal Default Editor')
  AND option_group_id = @option_group_id_wysiwyg;

UPDATE civicrm_option_value SET is_active = 1, is_reserved = 1 WHERE option_group_id = @option_group_id_wysiwyg;

--CRM-16719
SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Details Report'{/localize}
  WHERE value = 'activity' AND option_group_id = @option_group_id_report;

UPDATE civicrm_option_value SET {localize field="label"}label = 'Activity Summary Report'{/localize}
  WHERE value = 'activitySummary' AND option_group_id = @option_group_id_report;

--CRM-16853 PCP Owner Notification

{include file='../CRM/Upgrade/4.7.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-13283
CREATE TABLE IF NOT EXISTS `civicrm_status_pref` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique Status Preference ID',
     `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this Status Preference for',
     `name` varchar(255) NOT NULL   COMMENT 'Name of the status check this preference references.',
     `hush_until` date   DEFAULT NULL COMMENT 'expires ignore_severity.  NULL never hushes.',
     `ignore_severity` int unsigned   DEFAULT 1 COMMENT 'Hush messages up to and including this severity.',
     `prefs` varchar(255)    COMMENT 'These settings are per-check, and can\'t be compared across checks.',
     `check_info` varchar(255)    COMMENT 'These values are per-check, and can\'t be compared across checks.'
,
    PRIMARY KEY ( `id` )

    ,     INDEX `UI_status_pref_name`(
        name
  )

,          CONSTRAINT FK_civicrm_status_pref_domain_id FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain`(`id`)
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
