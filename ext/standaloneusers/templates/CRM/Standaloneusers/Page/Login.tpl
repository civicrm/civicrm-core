{include file='CRM/Standaloneusers/Page/_contentHead.tpl'}
    <div class="message info" style="display:none;" id="loggedOutNotice">{ts}You have been logged out.{/ts}</div>
    <div class="message warning" style="display:none;" id="anonAccessDenied">{ts}You may need to login to access that.{/ts}</div>
    <form>
      <div>
        <label for="usernameInput" name=username class="form-label">Username</label>
        <input type="text" class="form-control" id="usernameInput" >
      </div>
      <div>
        <label for="passwordInput" class="form-label">Password</label>
        <input type="password" class="form-control" id="passwordInput">
      </div>
      <div id="error" style="display:none;" class="form-alert">Your username and password do not match</div>
      <div class="flex">
      <a href="{$forgottenPasswordURL}">Forgotten password?</a>
      <button id="loginSubmit" type="submit" class="btn btn-secondary crm-button">Submit</button>
      </div>
    </form>
  {include file='CRM/Standaloneusers/Page/_contentFoot.tpl'}
{literal}
<script>
document.addEventListener('DOMContentLoaded', () => {

  const submitBtn = document.getElementById('loginSubmit'),
        username = document.getElementById('usernameInput'),
        password = document.getElementById('passwordInput'),
        loggedOutNotice = document.getElementById('loggedOutNotice');

  // Get special messages from url params
  const url = new URL(window.location);

  if (url.searchParams.get('justLoggedOut')) {
    loggedOutNotice.style.display = '';
    console.log("successful logout");
  }
  if ('anon' === url.searchParams.get('accessDenied')) {
    anonAccessDenied.style.display = '';
  }

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
        msg = msg.replace(/^HTTP \d{3} /,'');
      }
      else {
        let responseObj = await response.json();
        console.log("responseObj with error", responseObj);
      }
      alert(`Sorry, that didn‘t work. ${msg}`);
    }
    else {
      // OK response (it includes contact_id and user_id in JSON, but we don't need those)
      window.location = '/civicrm/';
    }
  });
});
/* (function($) { */
/*     var request = new XMLHttpRequest(); */
/*     request.open("POST", ); */
/*     request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); */
/*     request.responseType = "json"; */
/*     request.onreadystatechange = function() { */
/*       console.log(request.response); */
/*       if (request.readyState == 4) { */
/*         if (request.status == 200) { */
/*           if (request.response.user_id > 0) { */
/*             window.location.href = "/civicrm?reset=1"; */
/*           } else { */
/*             // probably won't ever be here? */
/*             alert("Success but fail because ???"); */
/*             console.log(request.response); */
/*           } */
/*         } else { */
/*           // todo - send errors back to the form via whatever forms framework we'll be using */
/*           alert("Fail with status code " + request.status + " " + request.statusText); */
/*           console.log(request.response); */
/*         } */
/*       } */
/*     }; */
/*     var data = '_authx=Basic ' + btoa(encodeURIComponent($('#username').val()) + ':' + $('#password').val()); */
/*     request.send(data); */
/*   }); */
</script>
{/literal}
