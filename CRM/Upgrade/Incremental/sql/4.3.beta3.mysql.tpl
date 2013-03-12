-- CRM-12077
DELETE cog, cov FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cov.option_group_id = cog.id
WHERE cog.name = 'account_type';