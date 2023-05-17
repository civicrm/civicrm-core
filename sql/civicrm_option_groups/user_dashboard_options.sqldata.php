<?php
return CRM_Core_CodeGen_OptionGroup::create('user_dashboard_options', 'a/0020')
  ->addMetadata([
    'title' => ts('User Dashboard Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Groups'), 'Groups', 1, 1],
    [ts('Contributions'), 'CiviContribute', 2, 2],
    [ts('Memberships'), 'CiviMember', 3, 3],
    [ts('Events'), 'CiviEvent', 4, 4],
    [ts('My Contacts / Organizations'), 'Permissioned Orgs', 5, 5],
    [ts('Pledges'), 'CiviPledge', 7, 7],
    [ts('Personal Campaign Pages'), 'PCP', 8, 8],
    [ts('Assigned Activities'), 'Assigned Activities', 9, 9],
    [ts('Invoices / Credit Notes'), 'Invoices / Credit Notes', 10, 10],
  ]);
