{crmScope extensionKey="standaloneusers"}

<div class="standalone-auth-form">
  <div class="standalone-auth-box">
      {include file='CRM/common/logo.tpl'}
      <crm-angular-js modules="crmLogin">
        <crm-login></crm-login>
      </crm-angular-js>
  </div>
</div>

{* The notification template is not loaded when the user is logged out. And we need this for CRM.alert *}
{include file="CRM/common/notifications.tpl"}

{/crmScope}
