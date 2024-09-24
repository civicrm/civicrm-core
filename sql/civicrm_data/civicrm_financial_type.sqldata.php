<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_financial_type')
  ->addValueTable(['label', 'name', 'is_deductible'], [
    [ts('Donation'), 'Donation', 1],
    [ts('Member Dues'), 'Member Dues', 1],
    [ts('Campaign Contribution'), 'Campaign Contribution', 0],
    [ts('Event Fee'), 'Event Fee', 0],
  ])
  ->addDefaults([
    'is_reserved' => 0,
    'is_active' => 1,
  ]);
