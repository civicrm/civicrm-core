{* file to handle db changes in 4.7.27 during upgrade *}

-- CRM-20892 Change created_date default so that we can add a modified_date column
ALTER TABLE civicrm_mailing CHANGE created_date created_date timestamp NULL  DEFAULT NULL COMMENT 'Date and time this mailing was created.';
