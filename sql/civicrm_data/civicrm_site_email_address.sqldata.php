<?php
return CRM_Core_CodeGen_SqlData::create('civicrm_site_email_address')
  ->addValues([
    [
      'display_name' => 'FIXME',
      'email' => 'info@EXAMPLE.ORG',
      'is_default' => 1,
      'description' => ts('Default domain email address and from name.'),
      'domain_id' => new CRM_Utils_SQL_Literal('@domainID'),
    ],
  ]);
