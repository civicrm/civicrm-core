{* file to handle db changes in 5.57.alpha1 during upgrade *}

-- Remove unsupported recaptcha options wherever present
DELETE FROM civicrm_setting WHERE name = 'recaptchaOptions';
