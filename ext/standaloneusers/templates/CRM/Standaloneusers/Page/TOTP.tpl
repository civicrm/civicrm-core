<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=totp-form>
      {include file='CRM/common/logo.tpl'}
      {$statusMessages}

      <div class="input-wrapper">
        <label for="totpcode" name=totp class="form-label">{ts}Enter the code from your authenticator app{/ts}</label>
        <input type="text" class="form-control" id="totpcode" maxlength=6>
      </div>
      <div>
        <button id="submit" type="submit" class="btn crm-button">{ts}Submit{/ts}</button>
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
      e.preventDefault();
      let errorMsg = 'Unexpected error';
      try {
        const response = await CRM.api4('User', 'login', { mfaClass: 'TOTP', mfaData: totpcodeInput.value });
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
