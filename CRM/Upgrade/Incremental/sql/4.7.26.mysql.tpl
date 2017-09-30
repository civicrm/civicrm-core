{* file to handle db changes in 4.7.26 during upgrade *}

-- CRM-20892 Change created_date default so that we can add a modified_date column
ALTER TABLE civicrm_mailing CHANGE created_date created_date timestamp NULL  DEFAULT NULL COMMENT 'Date and time this mailing was created.';

-- CRM-21234 Missing subdivisions of Tajikistan.
 INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
   (NULL, 1209, "DU", "Dushanbe"),
   (NULL, 1209, "RA", "Nohiyahoi Tobei Jumhurí");
