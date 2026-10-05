<?php
use CRM_MessageAdmin_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Message Templates'),
  'icon' => 'fa-envelope-open-o',
  // Not auto-detected: the Tags column's <crm-entity-tags> lives in an 'include' partial
  // (ang/crmMsgadm/tagsColumn.html) referenced from the SearchDisplay's own JSON settings,
  // not in this Afform's own layout HTML, so the usual element-scan dependency mapper
  // (AngularDependencyMapper) never sees it and never loads either module for us -
  // `crmMsgadm` for the partial's own path, `crmEntityTags` for the directive it uses.
  'requires' => ['crmMsgadm', 'crmEntityTags'],
  'server_route' => 'civicrm/admin/messageTemplates',
  'permission' => [
    'edit message templates',
    'edit user-driven message templates',
    'edit system workflow message templates',
  ],
  'permission_operator' => 'OR',
];
