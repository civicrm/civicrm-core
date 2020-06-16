{* file to handle db changes in 5.27.beta1 during upgrade *}
ALTER TABLE civicrm_option_value MODIFY COLUMN `filter` int unsigned DEFAULT 0 COMMENT 'Bitwise logic can be used to create subsets of options within an option_group for different uses.';

-- To think about: This will update ones where someone has explicitly set it to NULL for their own purposes and they don't care about the dropdowns. How likely is that? How can we tell if it's one they created since 5.26 and didn't intend to set it to NULL?
UPDATE civicrm_option_value ov
INNER JOIN civicrm_option_group og ON (ov.option_group_id = og.id AND og.name='activity_type')
SET ov.filter = 0
WHERE ov.filter IS NULL;
