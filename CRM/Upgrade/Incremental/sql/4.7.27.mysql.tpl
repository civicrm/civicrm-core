{* file to handle db changes in 4.7.27 during upgrade *}

-- CRM-21268 Missing French overseas departments.
 INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
   (NULL, 1076, "WF", "Wallis-et-Futuna"),
   (NULL, 1076, "NC", "Nouvelle-Calédonie"),

-- CRM-21283 Add in missing parishes and regions of Barbados and Antigua and Barbuda
  (NULL, 1009, "03", "Saint George"),
  (NULL, 1009, "04", "Saint John"),
  (NULL, 1009, "05", "Saint Mary"),
  (NULL, 1009, "06", "Saint Paul"),
  (NULL, 1009, "07", "Saint Peter"),
  (NULL, 1009, "08", "Saint Philip"),
  (NULL, 1009, "10", "Barbuda"),
  (NULL, 1009, "11", "Redonda"),
  (NULL, 1018, "01", "Christ Church"),
  (NULL, 1018, "02", "Saint Andrew"),
  (NULL, 1018, "03", "Saint George"),
  (NULL, 1018, "04", "Saint James"),
  (NULL, 1018, "05", "Saint John"),
  (NULL, 1018, "06", "Saint Joseph"),
  (NULL, 1018, "07", "Saint Lucy"),
  (NULL, 1018, "08", "Saint Michael"),
  (NULL, 1018, "09", "Saint Peter"),
  (NULL, 1018, "10", "Saint Philip"),
  (NULL, 1018, "11", "Saint Thomas");
