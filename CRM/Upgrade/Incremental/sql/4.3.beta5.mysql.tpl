-- CRM-12142
-- Populate default text for premiums_nothankyou_label
UPDATE `civicrm_premiums` SET {localize field="premiums_nothankyou_label"}premiums_nothankyou_label = '{ts escape="sql"}No thank-you{/ts}'{/localize};