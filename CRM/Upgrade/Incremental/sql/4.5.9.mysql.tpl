{* file to handle db changes in 4.5.9 during upgrade *}
-- CRM-15928
INSERT INTO civicrm_setting
(domain_id, contact_id, is_domain, group_name, name, value)
VALUES
({$domainID}, NULL, 1, 'CiviCRM Preferences', 'allow_profile_html_snippet', '{serialize}0{/serialize}');
