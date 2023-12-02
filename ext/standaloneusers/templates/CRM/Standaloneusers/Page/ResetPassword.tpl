{include file='CRM/Standaloneusers/Page/_contentHead.tpl'}
<crm-angular-js modules="crmResetPassword">
  <crm-reset-password
    hibp="{$hibp|escape}"
    token="{$token|escape}" ></crm-reset-password>
</crm-angular-js>
{include file='CRM/Standaloneusers/Page/_contentFoot.tpl'}
