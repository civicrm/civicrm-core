-- CRM-10794
DELETE FROM civicrm_payment_processor_type WHERE name = 'ClickAndPledge';
DELETE FROM civicrm_payment_processor WHERE payment_processor_type = 'ClickAndPledge';

