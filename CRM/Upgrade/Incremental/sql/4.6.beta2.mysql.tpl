{* file to handle db changes in 4.6.beta2 during upgrade *}
-- CRM-16018
ALTER TABLE  `civicrm_membership_block` CHANGE  `membership_types`  `membership_types` VARCHAR( 1024 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT  'Membership types to be exposed by this block.';

-- CRM-15578 Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CivMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests'
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
WHERE cm.id IS NOT NULL
