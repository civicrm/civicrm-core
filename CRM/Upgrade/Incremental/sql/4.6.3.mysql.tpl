{* file to handle db changes in 4.6.3 during upgrade *}
-- CRM-16307 fix CRM-15578 typo - Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CiviMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';

--CRM-16320
{include file='../CRM/Upgrade/4.6.3.msg_template/civicrm_msg_template.tpl'}

-- CRM-16452 Missing administrative divisions for Georgia
SELECT @country_id := id from civicrm_country where name = 'Georgia' AND iso_code = 'GE';
INSERT INTO civicrm_state_province (country_id, abbreviation, name)
  VALUES
    (@country_id, "AB", "Abkhazia"),
    (@country_id, "AJ", "Adjara"),
    (@country_id, "TB", "Tbilisi"),
    (@country_id, "GU", "Guria"),
    (@country_id, "IM", "Imereti"),
    (@country_id, "KA", "Kakheti"),
    (@country_id, "KK", "Kvemo Kartli"),
    (@country_id, "MM", "Mtskheta-Mtianeti"),
    (@country_id, "RL", "Racha-Lechkhumi and Kvemo Svaneti"),
    (@country_id, "SZ", "Samegrelo-Zemo Svaneti"),
    (@country_id, "SJ", "Samtskhe-Javakheti"),
    (@country_id, "SK", "Shida Kartli");
