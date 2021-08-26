{* file to handle db changes in 5.42.alpha1 during upgrade *}
ALTER TABLE civicrm_membership_type
ADD COLUMN currency VARCHAR(3) DEFAULT NULL
{*COLLATE getInUseCollation*}
COMMENT '3 character string, value from config setting or input via user.',
AFTER minimum_fee;
