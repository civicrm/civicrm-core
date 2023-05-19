<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_financial_type')
  ->addValueTable(['name', 'is_deductible'], [
    [ts('Donation'), 1],
    [ts('Member Dues'), 1],
    [ts('Campaign Contribution'), 0],
    [ts('Event Fee'), 0],
  ])
  ->addDefaults([
    'is_reserved' => 0,
    'is_active' => 1,
  ]);
