<p>{ts}Which Multi-Factor Authentication methods do you require?{/ts}</p>

<div>
  <select name=mfas >
    {foreach from=$mfas item="mfa"}
      <option {if $mfa['selected']}selected{/if} value="{$mfa['name']}">{$mfa['label']}</option>
    {/foreach}
  </select>

  <button id="mfas-submit" class="btn crm-button">{ts}Save{/ts}</button>
</div>

<script>{literal}
document.addEventListener('DOMContentLoaded', () => {
  const submit = document.querySelector('#mfas-submit');
  const select = document.querySelector('select[name="mfas"]');
  submit.addEventListener('click', async e => {
    e.preventDefault();
    submit.disabled = true;

    const mfas = select.value;
    const r = await CRM.api4('Setting', 'set', {values: {standalone_mfa_enabled: mfas}});

    submit.disabled = false;
  });
});{/literal}
</script>


