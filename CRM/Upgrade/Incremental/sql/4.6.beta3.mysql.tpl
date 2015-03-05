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
