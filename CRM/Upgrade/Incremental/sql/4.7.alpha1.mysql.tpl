{if $multilingual}
  {foreach from=$locales item=locale}
     ALTER TABLE civicrm_relationship_type ADD label_a_b_{$locale} varchar(64);
     ALTER TABLE civicrm_relationship_type ADD label_b_a_{$locale} varchar(64);
     ALTER TABLE civicrm_relationship_type ADD description_{$locale} varchar(255);

     UPDATE civicrm_relationship_type SET label_a_b_{$locale} = label_a_b;
     UPDATE civicrm_relationship_type SET label_b_a_{$locale} = label_b_a;
     UPDATE civicrm_relationship_type SET description_{$locale} = description;
  {/foreach}

  ALTER TABLE civicrm_relationship_type DROP label_a_b;
  ALTER TABLE civicrm_relationship_type DROP label_b_a;
  ALTER TABLE civicrm_relationship_type DROP description;
{else}
