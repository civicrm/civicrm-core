<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=login-form>
      <img class="crm-logo" src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">
      {if $justLoggedOut}<div class="help message info">{ts}You have been logged out.{/ts}</div>{/if}
      {if $anonAccessDenied}<div class="help message warning">{ts}You do not have permission to access that, you may
        need to login.{/ts}</div>{/if}
      {if $sessionLost}<div class="help message warning">{ts}Your session timed out.{/ts}</div>{/if}
      <div class="input-wrapper">
        <label for="usernameInput" name=username class="form-label">Username</label>
        <input type="text" class="form-control" id="usernameInput">
      </div>
      <div class="input-wrapper">
        <label for="passwordInput" class="form-label">Password</label>
        <input type="password" class="form-control" id="passwordInput">
      </div>
      <div id="error" style="display:none;" class="form-alert">Your username and password do not match</div>
      <div class="login-or-forgot">
        <a href="{$forgottenPasswordURL}">Forgotten password?</a>
        <button id="loginSubmit" type="submit" class="btn btn-secondary crm-button">Submit</button>
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

      let errorMsg = 'Unexpected error';
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
        errorMsg = response.publicError || "Unexpected error";
      }
      catch (e) {
        console.error('caught', e);
      }
      alert(errorMsg);
    });
  });
</script>
{/literal}
