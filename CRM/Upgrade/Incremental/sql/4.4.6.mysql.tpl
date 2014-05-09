{* file to handle db changes in 4.4.6 during upgrade *}

{*CRM-14499*}
CREATE INDEX index_image_url ON civicrm_contact (image_url);
