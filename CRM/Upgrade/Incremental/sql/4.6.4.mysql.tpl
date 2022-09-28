{* file to handle db changes in 4.6.4 during upgrade *}
UPDATE civicrm_uf_group SET group_type = 'Contact,Organization' WHERE civicrm_uf_group.name = 'on_behalf_organization' AND group_type <> 'Contact,Organization';
