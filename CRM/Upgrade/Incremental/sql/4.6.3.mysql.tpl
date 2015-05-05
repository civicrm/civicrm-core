{* file to handle db changes in 4.6.3 during upgrade *}
-- CRM-16307 fix CRM-15578 typo - Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CiviMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';

--CRM-16391: Rename 'Type' to 'Financial Type' in Contribution and Pledge batch entry profiles
UPDATE civicrm_uf_field
SET label = 'Financial Type'
WHERE field_type = 'Contribution' AND field_name='financial_type';

-- CRM-16392: Rename 'type' to 'Membership type' on membership batch entry profile
UPDATE civicrm_uf_field
SET label = 'Membership Type'
WHERE field_type = 'Membership' AND field_name='membership_type';
