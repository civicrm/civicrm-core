{* file to handle db changes in 5.44.alpha1 during upgrade *}

{* Polulate RelationshipCache.case_id column *}
UPDATE `civicrm_relationship_cache` rc, `civicrm_relationship` r SET rc.case_id = r.case_id WHERE r.case_id IS NOT NULL AND r.id = rc.relationship_id;

{* Remove Connections admin menu item *}
DELETE FROM `civicrm_navigation` WHERE url = 'civicrm/a/#/cxn';
