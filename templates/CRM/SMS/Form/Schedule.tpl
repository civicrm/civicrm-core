{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-sms-schedule-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div class="help">
    {ts}You can schedule this Mass SMS to be sent starting at a specific date and time, OR you can request that it be sent as soon as possible by checking &quot;Send Immediately&quot;.{/ts} {help id="sending"}
</div>
{include file="CRM/Mailing/Form/Count.tpl"}

<div>
  <div>
    <div>
      {$form.send_option.html}
      <span class="start_date_elements">{$form.start_date.html}</span>
    </div>

  </div>
  <div class="description">{ts}Set a date and time when you want CiviSMS to start sending this Mass SMS.{/ts}</div>
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>

{if $preview}
<details class="crm-accordion-bold crm-plain_text_sms-accordion">
    <summary>
        {ts}Preview SMS{/ts}
    </summary>
    <div class="crm-accordion-body">
        <table class="form-layout">

          {if $preview.viewURL}
    <tr><td class="label">{if $preview.type eq 'html'}{ts}SMS HTML:{/ts}{else}{ts}SMS Text:{/ts}{/if}</td><td><iframe height="300" src="{$preview.viewURL}" width="80%"><a href="{$preview.viewURL}" onclick="window.open(this.href); return false;">{ts}SMS Text:{/ts}</a></iframe></td></tr>
          {/if}
        </table>
    </div>
</details>
{/if}

</div>

<script type="text/javascript">
{literal}
  CRM.$(function($) {

    // If someone changes the schedule date, auto-select the 'send at' option
    $(".start_date_elements input").change(function() {
      $('#send_immediate').prop('checked', false);
      $('#send_later').prop('checked', true);
    });

    // Clear scheduled date/time when send immediately is selected
    $("#send_immediate").change(function() {
      if ($(this).prop('checked')) {
        $(".start_date_elements input").val('');
        $(".start_date_elements input").siblings("a.crm-clear-link").css('visibility', 'hidden');
      }
    });

  });
{/literal}
</script>
