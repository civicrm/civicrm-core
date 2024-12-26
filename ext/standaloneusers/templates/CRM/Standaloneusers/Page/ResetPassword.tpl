<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <img class="crm-logo" src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">
    <crm-angular-js modules="crmResetPassword">
    <crm-reset-password
        hibp="{$hibp|escape}"
        token="{$token|escape}" ></crm-reset-password>
    </crm-angular-js>
  </div>
</div>
