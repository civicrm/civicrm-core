<?php
return CRM_Core_CodeGen_OptionGroup::create('mail_approval_status', 'a/0054')
  ->addMetadata([
    'title' => ts('CiviMail Approval Status'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Approved'), 'Approved', 1, 'is_default' => 1, 'is_reserved' => 1, 'component_id' => 4, 'domain_id' => 1],
    [ts('Rejected'), 'Rejected', 2, 'is_reserved' => 1, 'component_id' => 4, 'domain_id' => 1],
    [ts('None'), 'None', 3, 'is_reserved' => 1, 'component_id' => 4, 'domain_id' => 1],
  ]);
