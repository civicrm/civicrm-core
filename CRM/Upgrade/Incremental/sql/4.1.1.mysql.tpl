-- CRM-9699

{if $addDedupeEmail}
    ALTER TABLE `civicrm_mailing` ADD `dedupe_email` TINYINT( 4 ) NULL DEFAULT '0' COMMENT 'Remove duplicate emails?';
{/if}

{if $worldRegionEmpty}
    INSERT INTO `civicrm_worldregion` (`id`, `name`) VALUES
    (1, 'Europe and Central Asia'),
    (2, 'America South, Central, North and Caribbean'),
    (3, 'Middle East and North Africa'),
    (4, 'Asia-Pacific'),
    (5, 'Africa West, East, Central and Southern'),
    (99, 'unassigned');
{/if}

SELECT @region_id   := max(id) from civicrm_worldregion where name = "Africa West, East, Central and Southern";

INSERT INTO `civicrm_country` (`name`, `iso_code`, `country_code`, `idd_prefix`, `ndd_prefix`, `region_id`, `is_province_abbreviated`, `address_format_id`) VALUES ('South Sudan', 'SS', NULL, NULL, NULL, @region_id, 0, NULL) ON DUPLICATE KEY UPDATE iso_code='SS';

