{* file to handle db changes in 4.5.beta2 during upgrade *}

{include file='../CRM/Upgrade/4.5.beta2.msg_template/civicrm_msg_template.tpl'}

--CRM-14948 To delete list of outdated Russian provinance
DELETE FROM `civicrm_state_province` WHERE `name` IN ('Komi-Permyatskiy avtonomnyy okrug','Taymyrskiy (Dolgano-Nenetskiy)','Evenkiyskiy avtonomnyy okrug','Koryakskiy avtonomnyy okrug','Ust\'-Ordynskiy Buryatskiy','Aginskiy Buryatskiy avtonomnyy');

--CRM-14948 To update new list of new Russian provinance
UPDATE `civicrm_state_province` SET `name`='Perm krai',`abbreviation`='PEK',`country_id`= 1177 WHERE `id` = 4270;

UPDATE `civicrm_state_province` SET `name`='Kamchatka Krai',`country_id`= 1177 WHERE `id` = 4252;

UPDATE `civicrm_state_province` SET `name`='Zabaykalsky Krai',`abbreviation`='ZSK',`country_id`= 1177 WHERE `id` = 4247;

-- CRM-14918
UPDATE `civicrm_line_item` cl
INNER JOIN civicrm_membership_payment cmp ON cmp.contribution_id = cl.entity_id
SET cl.entity_table = 'civicrm_membership',
 cl.entity_id = cmp.membership_id
WHERE cl.entity_table = 'civicrm_contribution';