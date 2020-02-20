{* file to handle db changes in 5.24.alpha1 during upgrade *}

{* Mark authorize.net as legacy as APIs are no longer supported *}
UPDATE `civicrm_payment_processor_type` SET title = "Authorize.Net (legacy)" WHERE title = "Authorize.Net";
