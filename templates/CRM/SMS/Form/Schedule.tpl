{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>

{if $preview}
<div class="crm-accordion-wrapper crm-plain_text_sms-accordion collapsed">
    <div class="crm-accordion-header">
        {ts}Preview SMS{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <table class="form-layout">

          {if $preview.viewURL}
    <tr><td class="label">{if $preview.type eq 'html'}{ts}SMS HTML:{/ts}{else}{ts}SMS Text:{/ts}{/if}</td><td><iframe height="300" src="{$preview.viewURL}" width="80%"><a href="{$preview.viewURL}" onclick="window.open(this.href); return false;">{ts}SMS Text:{/ts}</a></iframe></td></tr>
          {/if}
        </table>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
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
