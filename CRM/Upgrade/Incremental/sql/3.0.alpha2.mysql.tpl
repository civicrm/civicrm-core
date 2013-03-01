 -- CRM-4866

    SELECT @domain_id := min(id) FROM civicrm_domain;
    SELECT @nav_pl    := id FROM civicrm_navigation WHERE name = 'Pledges';
    SELECT @nav_pl_wt := max(weight) from civicrm_navigation WHERE parent_id = @nav_pl;

    INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
    VALUES
	( @domain_id, 'civicrm/contact/view/pledge&reset=1&action=add&context=standalone', '{ts escape="sql"}New Pledge{/ts}','New Pledge',  NULL, '', @nav_pl, '1', NULL, @nav_pl_wt+1 );
