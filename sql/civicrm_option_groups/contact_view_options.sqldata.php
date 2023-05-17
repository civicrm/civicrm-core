<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_view_options', 'a/0016')
  ->addMetadata([
    'title' => ts('Contact View Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [

    // NOTE: The original SQL had this inaccurate comment. It is unclear if the comment is inaccurate
    // because the situation changed for the better or the worse.
    //
    // -- note that these are not ts'ed since they are used for logic in most cases and not display
    // -- they are used for display only in the prefernces field settings

    [ts('Activities'), 'activity', 1, 1],
    [ts('Relationships'), 'rel', 2, 2],
    [ts('Groups'), 'group', 3, 3],
    [ts('Notes'), 'note', 4, 4],
    [ts('Tags'), 'tag', 5, 5],
    [ts('Change Log'), 'log', 6, 6],
    [ts('Contributions'), 'CiviContribute', 7, 7],
    [ts('Memberships'), 'CiviMember', 8, 8],
    [ts('Events'), 'CiviEvent', 9, 9],
    [ts('Cases'), 'CiviCase', 10, 10],
    [ts('Pledges'), 'CiviPledge', 13, 13],
    [ts('Mailings'), 'CiviMail', 14, 14],
  ]);
