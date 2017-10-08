{* file to handle db changes in 4.7.27 during upgrade *}

-- CRM-21268 Missing French overseas departments.
 INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
   (NULL, 1076, "WF", "Wallis-et-Futuna"),
   (NULL, 1076, "NC", "Nouvelle-Calédonie");
