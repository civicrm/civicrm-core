<?php
return CRM_Core_CodeGen_OptionGroup::create('from_email_address', 'a/0029')
  ->addMetadata([
    'title' => ts('From Email Address'),
    'description' => ts('By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: "Client Services" <clientservices@example.org>.'),
  ])
  ->addValues([
    [
      'label' => '"FIXME" <info@EXAMPLE.ORG>',
      'value' => 1,
      'name' => '"FIXME" <info@EXAMPLE.ORG>',
      'is_default' => 1,
      'description' => ts('Default domain email address and from name.'),
      'domain_id' => new CRM_Utils_SQL_Literal('@domainID'),
    ],
  ]);
