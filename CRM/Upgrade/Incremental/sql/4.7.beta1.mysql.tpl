{* file to handle db changes in 4.7.beta1 during upgrade *}

-- CRM-17503 PayPal Express processor type can support recurring payments
UPDATE civicrm_payment_processor_type pp
LEFT JOIN civicrm_payment_processor p ON p.payment_processor_type_id = pp.id
SET pp.is_recur = 1, p.is_recur = 1
WHERE pp.name='PayPal_Express';
