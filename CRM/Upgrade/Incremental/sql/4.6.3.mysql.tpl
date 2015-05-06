{* file to handle db changes in 4.6.3 during upgrade *}
-- CRM-16307 fix CRM-15578 typo - Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CiviMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';

--CRM-16320
{include file='../CRM/Upgrade/4.6.3.msg_template/civicrm_msg_template.tpl'}

-- CRM-16452 Missing administrative divisions for Georgia
INSERT INTO civicrm_state_province (country_id, abbreviation, name)
  VALUES
    (1081, "AB", "Abkhazia"),
    (1081, "AJ", "Adjara"),
    (1081, "TB", "Tbilisi"),
    (1081, "GU", "Guria"),
    (1081, "IM", "Imereti"),
    (1081, "KA", "Kakheti"),
    (1081, "KK", "Kvemo Kartli"),
    (1081, "MM", "Mtskheta-Mtianeti"),
    (1081, "RL", "Racha-Lechkhumi and Kvemo Svaneti"),
    (1081, "SZ", "Samegrelo-Zemo Svaneti"),
    (1081, "SJ", "Samtskhe-Javakheti"),
    (1081, "SK", "Shida Kartli");
