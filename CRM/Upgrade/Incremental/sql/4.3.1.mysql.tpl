-- CRM-12351
UPDATE civicrm_dedupe_rule_group SET title = name WHERE title IS NULL; 