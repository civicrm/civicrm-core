{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

<table class="form-layout">
  <tbody>
    <tr class="crm-sms-schedule-form-block-now">
        <td class="label">{$form.now.label}</td>
        <td>{$form.now.html}</td>
    </tr>
    <tr>
        <td class="label">{ts}OR{/ts}</td>
        <td>&nbsp;</td>
    </tr>
    <tr class="crm-sms-schedule-form-block-start_date">
        <td class="label">{$form.start_date.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}
            <div class="description">{ts}Set a date and time when you want CiviSMS to start sending this Mass SMS.{/ts}</div>
        </td>
    </tr>
  </tbody>
</table>
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
    $('#start_date_display').change(function() {
      $('#now').prop('checked', !$(this).val());
    });
    $('#now').change(function() {
      if ($(this).prop('checked')) {
        $('#start_date_display, #start_date, #start_date_time').val('');
      } else {
        $('#start_date_display').focus();
      }
    });
  });
{/literal}
</script>
