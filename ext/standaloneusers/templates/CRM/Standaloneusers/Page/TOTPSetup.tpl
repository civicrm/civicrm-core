<div class="standalone-auth-form">
  <div class="standalone-auth-box">
    <form id=totp-form>
      {include file='CRM/common/logo.tpl'}
      {$statusMessages}

      <h1>{ts}Set up Multi-Factor Authentication{/ts}</h1>
      <div class="input-wrapper">
        <label for="seed" name=totpseed class="form-label">{ts}Copy this seed into your authenticator app{/ts}</label>
        <input readonly type="text" class="form-control" id="seed" value="{$totpseed}"/>
        <div style="padding:1rem;display:grid;place-content:center;">{$totpqr}</div>
      </div>

      <div class="input-wrapper">
        <label for="totpcode" name=totpcode class="form-label">{ts}Enter the code from your authenticator app{/ts}</label>
        <input type="text" class="form-control" id="totpcode">
      </div>
      <div>
        <button id="submit" type="submit" class="btn crm-button">{ts}Check and setup{/ts}</button>
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
      form.querySelectorAll('input, button').forEach(el => el.disabled = true);
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
      form.querySelectorAll('input, button').forEach(el => el.disabled = false);
    }

    form.addEventListener('submit', submit);
    totpcodeInput.addEventListener('input', e => {
      if (totpcodeInput.value.match(/^\d{6}$/)) {
        submit(e);
      }
    });
    totpcodeInput.focus();

  });
</script>
{/literal}
