-- CRM-11847
UPDATE `civicrm_dedupe_rule_group`
  SET name = 'IndividualGeneral'
  WHERE name = 'IndividualComplete';
  
-- CRM-11791
INSERT IGNORE INTO `civicrm_relationship_type` ( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
  VALUES
  ( 'Partner of', '{ts escape="sql"}Partner of{/ts}', 'Partner of', '{ts escape="sql"}Partner of{/ts}', '{ts escape="sql"}Partner relationship.{/ts}', 'Individual', 'Individual', 0 );

-- CRM-11886
UPDATE `civicrm_navigation`
  SET permission = 'view own manual batches,view all manual batches'
  WHERE
  name = 'Open Batches' OR
  name = 'Closed Batches' OR
  name = 'Exported Batches' OR
  name = 'Accounting Batches';

UPDATE `civicrm_navigation`
  SET permission = 'create manual batch'
  WHERE
  name = 'Accounting Batches';

-- CRM-11891
SELECT @contributionlastID := max(id) from civicrm_navigation where name = 'Contributions' AND domain_id = {$domainID};
SELECT @importWeight := weight from civicrm_navigation where name = 'Import Contributions' and parent_id = @contributionlastID;

-- since 'Bulk Data Entry' was renamed to 'Batch Data Entry'
UPDATE `civicrm_navigation` SET label = '{ts escape="sql"}Batch Data Entry{/ts}', name = 'Batch Data Entry'
WHERE url = 'civicrm/batch&reset=1';

UPDATE `civicrm_navigation`
  SET `weight` = `weight`+2
  WHERE `parent_id` = @contributionlastID
  AND (`weight` > @importWeight OR `name` = 'Accounting Batches');

UPDATE `civicrm_navigation`
  SET `weight` = @importWeight+1
  WHERE `name` = 'Batch Data Entry';