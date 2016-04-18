{* file to handle db changes in 4.7.7 during upgrade *}
-- Fix weight interchange of `Extensions` and `Connections` navigation menu
SELECT @parent_id := id from `civicrm_navigation` where name = 'System Settings' AND domain_id = {$domainID};
UPDATE
  `civicrm_navigation` AS nav1
  JOIN `civicrm_navigation` AS nav2 ON
  nav1.name = 'Connections'
  AND nav2.name = 'Manage Extensions'
  AND nav2.has_separator = 1
  AND nav1.parent_id = @parent_id
  AND nav1.weight > nav2.weight
SET
  nav1.weight = nav2.weight,
  nav2.weight = nav1.weight;
