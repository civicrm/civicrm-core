{crmScope extensionKey="standaloneusers"}
<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=login-form>
      <img class="crm-logo" src="{$logoUrl}" alt="{ts escape='htmlattribute'}Logo for CiviCRM, with an intersecting blue and green triangle{/ts}">
      {$statusMessages}
      <div class="input-wrapper">
        <label for="usernameInput" name=username class="form-label">{ts}Username{/ts}</label>
        <input type="text" class="form-control crm-form-text" id="usernameInput" >
      </div>
      <div class="input-wrapper">
        <label for="passwordInput" class="form-label">{ts}Password{/ts}</label>
        <input type="password" class="form-control crm-form-text" id="passwordInput">
      </div>
      <div id="error" style="display:none;" class="form-alert">{ts}Your username and password do not match{/ts}</div>
      <div class="login-or-forgot">
        <a href="{$forgottenPasswordURL}">{ts}Forgotten password?{/ts}</a>
        <button id="loginSubmit" type="submit" class="btn btn-primary crm-button">{ts}Log In{/ts}</button>
      </div>
    </form>
  </div>
</div>

{literal}
<script>
  document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('login-form'),
      username = document.getElementById('usernameInput'),
      password = document.getElementById('passwordInput');

    form.addEventListener('submit', async e => {
      e.preventDefault();

      let errorMsg = '{/literal}{ts escape="js"}Unexpected error{/ts}{literal}';
      try {
        let originalUrl = location.href;
        const response = await CRM.api4('User', 'login', {
          username: username.value,
          password: password.value,
          originalUrl
        });
        if (response.url) {
          window.location = response.url;
          return;
        }
        errorMsg = response.publicError || "{/literal}{ts escape="js"}Unexpected error{/ts}{literal}";
      }
      catch (e) {
        console.error('caught', e);
      }
      alert(errorMsg);
    });
  });
</script>
{/literal}
{/crmScope}
