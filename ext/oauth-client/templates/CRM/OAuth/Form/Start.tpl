<div class="help">
  {foreach from=$blurbs item=blurb}{$blurb}{/foreach}
</div>

{if isset($form.civiconnect_approved)}
  <div>
    <input id="civiconnect_approved" name="civiconnect_approved" type="checkbox" value="1"  class="crm-form-checkbox required"/>
    <label for="civiconnect_approved"> Enable CiviConnect
      <span class="crm-marker" title="{ts}This field is required.{/ts}">*</span>
    </label>
    (<a target="_blank" href="{$civiconnect_terms|escape}">{ts}Terms and Conditions{/ts}</a>)
  </div>
{/if}
{if isset($form.do_not_ask_again)}
  <div>
    <input id="do_not_ask_again" name="do_not_ask_again" type="checkbox" value="1" class="crm-form-checkbox" />
    <label for="do_not_ask_again">{ts}Do not show this message again{/ts}</label>
  </div>
{/if}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

<script type="text/javascript">
  if (window.opener && window.opener !== window) {
    CRM.$('.crm-button-type-cancel').click(function () {
      window.close();
      return false;
    });
  }
</script>
