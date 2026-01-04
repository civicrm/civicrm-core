<?php
// @see ext/civi_event/managed/OptionValues_custom_data_type.mgd.php
// Note: When adding options to this group, the 'name' *must* begin with the exact name of the base entity,
// as that's the (very lo-tech) way these options are matched with their base entity.
// Wrong: 'name' => 'ActivitiesByStatus'
// Right: 'name' => 'ActivityByStatus'
return CRM_Core_CodeGen_OptionGroup::create('custom_data_type', 'a/0034')
  ->addMetadata([
    'title' => ts('Custom Data Type'),
  ]);
