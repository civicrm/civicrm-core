{* file to handle db changes in 4.7.14 during upgrade *}

# CRM-19507 Data repair to fix bookkeeping entries related to changes in payment instruments for transactions created prior to application of CRM-19149

/* this is a complicated query, unfortunately
   contribs_with_pi_changes is a list of contributions that have had payment changes that have not had
   entity_financial_trxn records inserted to link the relevant financial_trxn records 
   to their associated financial_item records. We ensure that there are an even number of these transactions
   in order to avoid dealing with problematic records we found, 
   eg $0 contributions for free event registrations
   for each of these contributions, we need to get the most recent financial_trxn record 
   that has the links to financial_items that we need. 
   fts query provides these financial_trxn's since they are not refunds, etc.
   eft_fi_to_insert provides the list of financial_items to be inserted for the ft_without_fi id's
   */
INSERT INTO civicrm_entity_financial_trxn (entity_table, entity_id, financial_trxn_id, amount) 
SELECT eft_fi_to_insert.entity_table, eft_fi_to_insert.entity_id, fts.ft_without_fi, eft_fi_to_insert.amount
FROM (

SELECT MAX(ft.id) as ft_id, contribs_with_pi_changes.ft_without_fi AS ft_without_fi from civicrm_financial_trxn ft INNER JOIN civicrm_entity_financial_trxn eft ON ft.id=eft.financial_trxn_id AND eft.entity_table='civicrm_contribution' AND ft.from_financial_account_id IS NULL AND ft.to_financial_account_id IS NOT NULL
INNER JOIN (select eft_c.entity_id AS contrib_id, ft_c.id as ft_without_fi from civicrm_financial_trxn ft_c INNER JOIN civicrm_entity_financial_trxn eft_c ON ft_c.id=eft_c.financial_trxn_id AND eft_c.entity_table='civicrm_contribution' left join civicrm_entity_financial_trxn eft_fi ON ft_c.id=eft_fi.financial_trxn_id AND eft_fi.entity_table='civicrm_financial_item' where eft_fi.financial_trxn_id is NULL AND ft_c.from_financial_account_id is null and ft_c.to_financial_account_id is not null GROUP BY eft_c.entity_id HAVING (COUNT(*) MOD 2)=0) as contribs_with_pi_changes ON eft.entity_id=contribs_with_pi_changes.contrib_id INNER JOIN civicrm_entity_financial_trxn eft_fi ON ft.id=eft_fi.financial_trxn_id AND eft_fi.entity_table='civicrm_financial_item' GROUP BY eft.entity_id

) AS fts INNER JOIN civicrm_entity_financial_trxn eft_fi_to_insert ON fts.ft_id=eft_fi_to_insert.financial_trxn_id AND eft_fi_to_insert.entity_table='civicrm_financial_item';

