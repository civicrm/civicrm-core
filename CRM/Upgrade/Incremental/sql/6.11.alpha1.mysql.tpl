{* file to handle db changes in 6.11.alpha1 during upgrade *}

--Add Accounts Payable Account is account_relationship option value
SELECT @option_group_id_ar := MAX(id) FROM `civicrm_option_group` where name = 'account_relationship';
SELECT @maxValue := MAX(CAST(value AS UNSIGNED))  FROM `civicrm_option_value` where option_group_id = @option_group_id_ar;
SELECT @maxWeight := MAX(weight) FROM `civicrm_option_value` where option_group_id = @option_group_id_ar;

INSERT INTO `civicrm_option_value` (
`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, `is_reserved`, `is_active`, `is_default`
)
VALUES(
 @option_group_id_ar, {localize field='label'}'Accounts Payable Account is'{/localize}, @maxValue + 1, 'Accounts Payable Account is', @maxWeight + 1, 1 , 0 , 0
)
ON DUPLICATE KEY UPDATE id=id;
