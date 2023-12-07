<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form>
      <img class="crm-logo" src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">
      {if $justLoggedOut}<div class="help message info">{ts}You have been logged out.{/ts}</div>{/if}
      {if $anonAccessDenied}<div class="help message warning">{ts}You do not have permission to access that, you may
        need to login.{/ts}</div>{/if}
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

    const submitBtn = document.getElementById('loginSubmit'),
      username = document.getElementById('usernameInput'),
      password = document.getElementById('passwordInput');

    submitBtn.addEventListener('click', async e => {
      e.preventDefault();

      const response = await fetch(CRM.url("civicrm/authx/login"), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        //body: '_authx=Basic ' + btoa(encodeURIComponent(`${username.value}:${password.value}`))
        body: '_authx=Basic ' + encodeURIComponent(btoa(`${username.value}:${password.value}`))
      });
      if (!response.ok) {
        const contentType = response.headers.get("content-type");
        let msg = 'Unexpected error';
        if (!contentType || !contentType.includes("application/json")) {
          // Non-JSON response; an error.
          msg = await response.text();
          // Example error string: 'HTTP 401 Invalid credential'
          msg = msg.replace(/^HTTP \d{3} /, '');
        }
        else {
          let responseObj = await response.json();
          console.log("responseObj with error", responseObj);
        }
        alert(`Sorry, that didn‘t work. ${msg}`);
      }
      else {
        // OK response (it includes contact_id and user_id in JSON, but we don't need those)

        // reload the page
        // if we were trying to access a specific url, we will be taken to it
        // if we reload the /civicrm/login we will be redirected to the home page
        // (or an alternative url if we make that configurable)
        location.reload();
      }
    });
  });
</script>
{/literal}
