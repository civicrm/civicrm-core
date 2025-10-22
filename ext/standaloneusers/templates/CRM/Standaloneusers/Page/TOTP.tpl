<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=totp-form>
      {include file='CRM/common/logo.tpl'}
      {$statusMessages}

      <div class="input-wrapper">
        <label for="totpcode" name=totp class="form-label">{ts}Enter the code from your authenticator app{/ts}</label>
        <input type="text" class="form-control" id="totpcode" maxlength=6 autocomplete="off" >
      </div>
      <div>
        <button id="submit" type="submit" class="btn crm-button"><i id="submit-icon" class="crm-i fa-check" role="img" aria-hidden="true" ></i>
        {ts}Submit{/ts}</button>
      </div>
    </form>
  </div>
</div>

{literal}
<script>
  document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('totp-form'),
      totpcodeInput = document.getElementById('totpcode');

    async function submit(e) {
      const iconClasses = document.getElementById('submit-icon').classList;
      const submitButton = document.getElementById('submit');

      submitButton.disabled = true;
      iconClasses.replace('fa-check', 'fa-spinner');
      iconClasses.add('fa-spin');
      e.preventDefault();
      let errorMsg = 'Unexpected error';
      try {
        const response = await CRM.api4('User', 'login', { mfaClass: 'TOTP', mfaData: totpcodeInput.value });
        if (response.url) {
          // Successful login. If we started the login with rememberMe then we
          // will receive a JWT on successful login. We store this on
          // localStorage to skip MFA in future.
          if (response.rememberJWT) {
            localStorage.setItem('rememberJWT', response.rememberJWT);
          }
          else {
            localStorage.removeItem('rememberJWT');
          }
          window.location = response.url;
          return;
        }
        submitButton.disabled = false;
        iconClasses.replace('fa-spinner', 'fa-check');
        iconClasses.remove('fa-spin');
        errorMsg = response.publicError || "Unexpected error";
      }
      catch (e) {
        console.error('caught', e);
      }
      alert(errorMsg);
      totpcodeInput.value = '';
    }

    form.addEventListener('submit', submit);
    totpcodeInput.addEventListener('input', e => {
      if (totpcodeInput.value.match(/^\d{6}$/)) {
        submit(e);
      }
    });

    // Get ready for user to type code.
    totpcodeInput.focus();

  });
</script>
{/literal}
