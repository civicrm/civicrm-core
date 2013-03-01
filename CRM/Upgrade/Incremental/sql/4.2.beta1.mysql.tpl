-- /*******************************************************
-- * CRM-10477 - Extensions updates
-- *******************************************************/
CREATE TABLE `civicrm_extension` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Local Extension ID',
     `type` enum('payment', 'search', 'report', 'module') NOT NULL   ,
     `full_name` varchar(255) NOT NULL   COMMENT 'Fully qualified extension name',
     `name` varchar(255)    COMMENT 'Short name',
     `label` varchar(255)    COMMENT 'Short, printable name',
     `file` varchar(255)    COMMENT 'Primary PHP file',
     `schema_version` varchar(63)    COMMENT 'Revision code of the database schema; the format is module-defined',
     `is_active` tinyint   DEFAULT 1 COMMENT 'Is this extension active?' ,
    PRIMARY KEY ( `id` ) ,
    UNIQUE INDEX `UI_extension_full_name`(
        `full_name`
    ), 
    INDEX `UI_extension_name`(
        `name`
    )
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- assuming first value of array $locales is always en_US
{if $multilingual}
    INSERT INTO civicrm_extension (label, full_name, name, type, file, is_active)
    SELECT ov.label_{$locales.0}, ov.value, ov.name, ov.grouping, ov.description_{$locales.0}, ov.is_active
    FROM civicrm_option_group og
    INNER JOIN civicrm_option_value ov ON og.id = ov.option_group_id
    WHERE og.name = "system_extensions";
{else}
    INSERT INTO civicrm_extension (label, full_name, name, type, file, is_active)
    SELECT ov.label, ov.value, ov.name, ov.grouping, ov.description, ov.is_active
    FROM civicrm_option_group og
    INNER JOIN civicrm_option_value ov ON og.id = ov.option_group_id
    WHERE og.name = "system_extensions";
{/if}
DELETE FROM civicrm_option_group WHERE name = "system_extensions";
-- Note: Deletion cascades to civicrm_option_value
