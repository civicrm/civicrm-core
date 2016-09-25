{* file to handle db changes in 4.6.beta3 during upgrade *}

{*--CRM-15979 - differentiate between standalone mailings, A/B tests, and A/B final-winner *}
ALTER TABLE  `civicrm_mailing` ADD mailing_type varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT  'differentiate between standalone mailings, A/B tests, and A/B final-winner';

UPDATE `civicrm_mailing`cm
LEFT JOIN civicrm_mailing_abtest ab
ON cm.id = ab.mailing_id_a
  OR cm.id = ab.mailing_id_b
  OR cm.id = ab.mailing_id_c
  SET `mailing_type` = CASE
    WHEN cm.id IN (ab.mailing_id_a,ab.mailing_id_b) THEN 'experiment'
    WHEN cm.id IN (ab.mailing_id_c) THEN 'winner'
    ELSE 'standalone'
  END
WHERE cm.id IS NOT NULL;

-- CRM-16059
UPDATE civicrm_state_province SET name = 'Dobrich' WHERE name = 'Dobric';
UPDATE civicrm_state_province SET name = 'Yambol' WHERE name = 'Jambol';
UPDATE civicrm_state_province SET name = 'Kardzhali' WHERE name = 'Kardzali';
UPDATE civicrm_state_province SET name = 'Kyustendil' WHERE name = 'Kjstendil';
UPDATE civicrm_state_province SET name = 'Lovech' WHERE name = 'Lovec';
UPDATE civicrm_state_province SET name = 'Smolyan' WHERE name = 'Smoljan';
UPDATE civicrm_state_province SET name = 'Shumen' WHERE name = 'Sumen';
UPDATE civicrm_state_province SET name = 'Targovishte' WHERE name = 'Targoviste';
UPDATE civicrm_state_province SET name = 'Vratsa' WHERE name = 'Vraca';
