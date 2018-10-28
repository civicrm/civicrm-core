{* file to handle db changes in 5.7.alpha1 during upgrade *}

UPDATE civicrm_navigation SET name = "Search" WHERE name = "Search..." AND domain_id = {$domainID};
UPDATE civicrm_navigation SET icon = "crm-i fa-search" WHERE name = "Search" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-address-book-o" WHERE name = "Contacts" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-money" WHERE name = "Contributions" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-calendar" WHERE name = "Events" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-envelope-o" WHERE name = "Mailings" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-users" WHERE name = "Memberships" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-star-o" WHERE name = "Campaigns" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-folder-open-o" WHERE name = "Cases" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-exchange" WHERE name = "Grants" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-cog" WHERE name = "Administer" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-life-ring" WHERE name = "Support" AND domain_id = {$domainID} AND icon IS NULL;
UPDATE civicrm_navigation SET icon = "crm-i fa-bar-chart" WHERE name = "Reports" AND domain_id = {$domainID} AND icon IS NULL;
