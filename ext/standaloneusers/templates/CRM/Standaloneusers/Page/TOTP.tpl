<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=totp-form>
      <img class="crm-logo" src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">

      <div class="input-wrapper">
        <label for="totpcode" name=totp class="form-label">Enter the code from your authenticator app</label>
        <input type="text" class="form-control" id="totpcode">
      </div>
      <div>
        <button id="submit" type="submit" class="btn crm-button">Submit</button>
      </div>
    </form>
  </div>
</div>

{literal}
<script>
  document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('totp-form'),
      totpcode = document.getElementById('totpcode');

    async function submit(e) {
      e.preventDefault();

      const response = await fetch(CRM.url("civicrm/"), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        //body: '_authx=Basic ' + btoa(encodeURIComponent(`${username.value}:${password.value}`))
        body: '_authx=Basic ' + encodeURIComponent(btoa(`${username.value}:${password.value}`))
      });

    }

    form.addEventListener('submit', submit);
    totpcode.addEventListener('input', (e) => {
      if (totpcode.value.match(/^[0-9]{6}$/)) {
        // 6 digits received, auto submit
        submit(e);
      }
    });

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
