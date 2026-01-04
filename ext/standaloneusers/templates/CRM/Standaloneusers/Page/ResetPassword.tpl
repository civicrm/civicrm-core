<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    {include file='CRM/common/logo.tpl'}
    <crm-angular-js modules="crmResetPassword">
    <crm-reset-password
        hibp="{$hibp|escape}"
        token="{$token|escape}" ></crm-reset-password>
    </crm-angular-js>
  </div>
</div>
