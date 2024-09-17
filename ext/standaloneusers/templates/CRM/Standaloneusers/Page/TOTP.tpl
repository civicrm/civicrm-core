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
    }

    form.addEventListener('submit', submit);
    totpcodeInput.addEventListener('input', e => {
      if (totpcodeInput.value.match(/^\d{6}$/)) {
        submit(e);
      }
    });

  });
</script>
{/literal}
