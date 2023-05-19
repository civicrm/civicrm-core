<?php
return CRM_Core_CodeGen_OptionGroup::create('user_dashboard_options', 'a/0020')
  ->addMetadata([
    'title' => ts('User Dashboard Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Groups'), 'Groups', 1],
    [ts('Contributions'), 'CiviContribute', 2],
    [ts('Memberships'), 'CiviMember', 3],
    [ts('Events'), 'CiviEvent', 4],
    [ts('My Contacts / Organizations'), 'Permissioned Orgs', 5],
    // Wait, I like the number 6. What's wrong with 6?
    [ts('Pledges'), 'CiviPledge', 7],
    [ts('Personal Campaign Pages'), 'PCP', 8],
    [ts('Assigned Activities'), 'Assigned Activities', 9],
    [ts('Invoices / Credit Notes'), 'Invoices / Credit Notes', 10],
  ])
  ->syncColumns('fill', ['value' => 'weight']);
