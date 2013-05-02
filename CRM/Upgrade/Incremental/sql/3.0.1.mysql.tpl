-- CRM-CRM-5199
    UPDATE civicrm_navigation
    SET url = 'civicrm/activity/add&atype=3&action=add&reset=1&context=standalone'
        WHERE civicrm_navigation.name= 'New Email';
    UPDATE civicrm_navigation
    SET url = 'civicrm/admin/setting/search&reset=1'
        WHERE civicrm_navigation.name= 'Search Settings';