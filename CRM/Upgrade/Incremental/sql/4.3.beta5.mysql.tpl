-- CRM-12142
-- Populate default text for premiums_nothankyou_label
UPDATE `civicrm_premiums` SET {localize field="premiums_nothankyou_label"}premiums_nothankyou_label = '{ts escape="sql"}No thank-you{/ts}'{/localize};

-- CRM-12233 Fix price field label for quick config membership signup field
UPDATE `civicrm_price_field` cpf
LEFT JOIN `civicrm_price_set` cps ON cps.id = cpf.price_set_id
SET {localize field="label"}cpf.label = '{ts escape="sql"}Membership{/ts}'{/localize}
WHERE cps.is_quick_config = 1 AND cpf.name = 'membership_amount';