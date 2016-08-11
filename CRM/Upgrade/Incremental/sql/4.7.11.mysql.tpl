{* file to handle db changes in 4.7.11 during upgrade *}

-- CRM-19134 Missing French overseas departments.
INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
  (NULL, 1076, "GP", "Guadeloupe"),
  (NULL, 1076, "MQ", "Martinique"),
  (NULL, 1076, "GF", "Guyane"),
  (NULL, 1076, "RE", "La Réunion"),
  (NULL, 1076, "YT", "Mayotte");

-- CRM-18508 Display State/Province in event address in registration emails
{include file='../CRM/Upgrade/4.7.11.msg_template/civicrm_msg_template.tpl'}