{* file to handle db changes in 4.5.beta2 during upgrade *}

--CRM-14948 To delete list of outdated Russian provinance
DELETE FROM `civicrm_state_province` WHERE `name` IN ('Komi-Permyatskiy avtonomnyy okrug','Taymyrskiy (Dolgano-Nenetskiy)','Evenkiyskiy avtonomnyy okrug','Koryakskiy avtonomnyy okrug','Ust\'-Ordynskiy Buryatskiy','Aginskiy Buryatskiy avtonomnyy');

--CRM-14948 To update new list of new Russian provinance
UPDATE `civicrm_state_province` SET `name`='Permskiy kray',`abbreviation`='PEK',`country_id`= 1177 WHERE `id` = 4270;

UPDATE `civicrm_state_province` SET `name`='Kamchatskiy kray',`country_id`= 1177 WHERE `id` = 4252;

UPDATE `civicrm_state_province` SET `name`='Zabaykal skiy kray',`abbreviation`='ZSK',`country_id`= 1177 WHERE `id` = 4247;