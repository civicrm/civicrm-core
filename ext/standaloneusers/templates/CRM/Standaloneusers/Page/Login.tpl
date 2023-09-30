<style>
{literal}
/***Structure****
    Variables (comment out your subtheme)
        - Finsbury Park
        - Jerry Seinfeld
        - Shoreditch (soon)
        - Aah (soon)
    Resets
    Base
****************/

/***************
    Variables
****************/

/* Finsbury Park

:root {
    --roundness: 0.25rem;
    --font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans, Ubuntu,Cantarell,"Helvetica Neue",Helvetica,Arial,sans-serif,"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
    --text-colour: #000;
    --text-size: 0.9rem;
    --error-colour: #aa0c0c;
    --label-colour: #000;
    --background-colour: #ededed;
    --box-border: 1px #cdcdcd solid;
    --box-padding: 2rem 1.75rem;
    --box-shadow: none;
    --box-roundness: 0.25rem;
    --box-background: #fff;
    --input-border: 1px solid #ccc;
    --input-padding: 0.5rem;
    --input-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    --button-border: 1px solid #bbb;
    --button-shadow: 0 1px 2px rgba(0,0,0,0.05);
    --button-padding: 5px 15px;
    --button-text-colour: #3e3e3e;
    --button-background: #f0f0f0;
}

/* Shoreditch

:root {
    --roundness: 2px;
    --font-family: "Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif;
    --text-colour: #232429;
    --text-size: 0.9rem;
    --error-colour: #cf3458;
    --label-colour: #464354;
    --background-colour: #f3f6f7;
    --box-border: 0 transparent solid;
    --box-padding: 20px;
    --box-shadow: 0 3px 18px 0 rgba(48,40,40,0.25);
    --box-roundness: 2px;
    --box-background: #fff;
    --input-border: 1px solid #c2cfd8;
    --input-padding: 5px 10px;
    --input-shadow: inset 0 0 3px 0 rgba(0,0,0,0.2);
    --button-border: 0 solid transparent;
    --button-shadow: none;
    --button-padding: 8px 28px;
    --button-text-colour: #fff;
    --button-background: #0071bd;
}

/* Aah */

:root {
    --roundness: 3px;
    --font-family: Lato,Helvetica,Arial,sans-serif;
    --text-colour: #222;
    --text-size: 0.9rem;
    --error-colour: #a00;
    --warning-colour: #fbb862;
    --success-colour: #86c66c;
    --label-colour: #464354;
    --background-colour: rgb(242,242,237);
    --box-border: 0 transparent solid;
    --box-padding: 1.6rem;
    --box-shadow: none;
    --box-roundness: 0;
    --box-background: #fff;
    --input-border: 1px solid rgba(0,0,0,.2);
    --input-padding: 5px 10px;
    --input-shadow: inset 0 0 3px 0 rgba(0,0,0,0.2);
    --button-border: 0 solid transparent;
    --button-shadow: 0 0 6px rgba(0,0,0,.2);
    --button-padding: .4rem 1.6rem;
    --button-text-colour: #fff;
    --button-background: #2c98ed;
    --button-text-shadow: none;
}

/* Ffresh

:root {
    --roundness: 2rem;
    --font-family: Lato,Helvetica,Arial,sans-serif;
    --text-colour: #222;
    --text-size: 1rem;
    --error-colour: #a00;
    --label-colour: #464354;
    --background-colour: #2c98ed;
    --box-border: 0 transparent solid;
    --box-padding: 1.6rem;
    --box-shadow: 0 0 10px 0 rgba(0,0,0,0.2);
    --box-roundness: 1.75rem;
    --box-background: #fff;
    --input-border: 2px solid #2c98ed;
    --input-padding: 0.75rem;
    --input-shadow: none;
    --button-border: 0 solid transparent;
    --button-shadow: none;
    --button-padding: 0.75rem 2rem;
    --button-text-colour: #fff;
    --button-background: #2c98ed;
}

/***************
    Base
****************/

body {
    background-color: var(--background-colour);
    font-family: var(--font-family);
    color: var(--text-colour);
    font-size: var(--text-size);
}
#crm-container.standalone-entry * {
    box-sizing: border-box;
}
a {
    text-decoration: none;
    font-size: 90%;
}
a:hover, a:focus {
    text-decoration: underline;
}
.flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/***************
    UI Elements
****************/

#crm-container.standalone-entry .mid-block {
    margin: 0;
    background-color: var(--box-background);
    border: var(--box-border);
    border-radius: var(--box-roundness);
    padding: var(--box-padding);
    box-shadow: var(--box-shadow);
}
#crm-container.standalone-entry img {
    width: 100%;
    max-width: 400px;
    margin-bottom: 2rem;
}
#crm-container.standalone-entry label {
    display: inline-block;
    max-width: 100%;
    margin-bottom: 5px;
    font-weight: 700;
    color: var(--label-colour);
}
#crm-container.standalone-entry input {
    display: block;
    width: 100%;
    color: #555;
    background-color: #fff;
    background-image: none;
    margin-bottom: 0.75rem;
    padding: var(--input-padding);
    font-size: var(--text-size);
    border-radius: var(--roundness);
    border: var(--input-border);
    box-shadow: var(--input-shadow);
}
#crm-container.standalone-entry input:focus,
#crm-container.standalone-entry input:focus-visible {
    border: 1px solid #66afe9;
}
#crm-container.standalone-entry .btn {
    display: inline-block;
    margin:0;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    background-image: none;
    font-size: var(--text-size);
    background-color: var(--button-background);
    color: var(--button-text-colour);
    border: var(--button-border);
    padding: var(--button-padding);
    border-radius: var(--roundness);
    font-family: var(--font-family);
    box-shadow: var(--button-shadow);
    text-shadow: var(--button-text-shadow);
}
#crm-container.standalone-entry .btn:hover,
#crm-container.standalone-entry .btn:focus {
    filter: brightness(80%);
}
#crm-container.standalone-entry .float-right {
    float: right;
    font-size: 90%;
    margin-top: 0.2rem;
}
#crm-container.standalone-entry .form-alert {
    color: var(--error-colour);
    margin: 1rem 0;
}


#loggedOutNotice {
  text-align: center;
  font-weight: bold;
  padding: var(--box-padding);
  background-color: var(--success-colour);
  margin: 1rem 0;
  border-radius: var(--box-roundness);
}
#anonAccessDenied {
  text-align: center;
  font-weight: bold;
  padding: var(--box-padding);
  background-color: var(--warning-colour);
  margin: 1rem 0;
  border-radius: var(--box-roundness);
}

@media  (min-width: 768px) {
    #crm-container.standalone-entry {
        width: 60vw;
        margin: 20vh auto 0;
    }
}
@media  (min-width: 960px) {
    #crm-container.standalone-entry {
        width: 30vw;
    }
}
{/literal}
</style>

<div id="crm-container" class="crm-container standalone-entry">
  <div class="mid-block">
    <img src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">
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
      <a href="request.html">Forgotten password?</a>
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
        password = document.getElementById('passwordInput'),
        loggedOutNotice = document.getElementById('loggedOutNotice');

  // Special messages.
  if (window.location.search === '?justLoggedOut') {
    loggedOutNotice.style.display = '';
    console.log("successful logout");
  }
  else if (window.location.search === '?anonAccessDenied') {
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
