{* file to handle db changes in 5.52.alpha1 during upgrade *}
--  Update any recurring contributions to have the same amount
-- as the recurring template contribution if it exists.
-- Some of these got out of sync over recent changes.
UPDATE civicrm_contribution_recur r
INNER JOIN civicrm_contribution c ON contribution_recur_id = r.id
AND c.is_template = 1
SET amount = total_amount
WHERE total_amount IS NOT NULL;
