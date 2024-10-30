{* file to handle db changes in 5.62.alpha1 during upgrade *}

-- Ensure all PriceSet.extends values are properly serialized
UPDATE `civicrm_price_set` SET `extends` = CONCAT("", `extends`, "")
WHERE `extends` IS NOT NULL AND `extends` != '' AND `extends` NOT LIKE "%";

{* https://github.com/civicrm/civicrm-core/pull/26055 *}
UPDATE civicrm_mapping SET name = CONCAT('mapping_', id) WHERE name IS NULL;
UPDATE civicrm_mapping m1, civicrm_mapping m2
SET m2.name = CONCAT(m2.name, '_', m2.id)
WHERE m2.name = m1.name AND m2.id > m1.id;
