{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of Export Batch(s)  *}
<h3>{ts}Export Batch{/ts}</h3>
<div class="messages status">
  {icon icon="fa-info-circle"}{/icon}
  {ts}Warning: You will not be able to reopen or change the batch after it is exported. Are you sure you want to export?{/ts}
</div>
<div class="crm-block crm-form-block crm-export_batch-form-block">
  <div class = "batch-names">
    <ul>
    {foreach from=$batchNames item=batchName}
      <li>{$batchName}</li>
    {/foreach}
    </ul>
  </div>

  <table class="form-layout">
    <tr class="crm-contribution-form-block-name">
      <td class="html-adjust">
      {$form.export_format.html}
      </td>
    </tr>
  </table>

  <div class="form-item">
  {$form.buttons.html}
  </div>
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('input[name="export_format"]').filter('[value=IIF]').prop('checked', true);
    $('#_qf_Export_next').click(function(){
      $(this).hide();
      {/literal}{capture assign=tsdone}{ts}Done{/ts}{/capture}{literal}
      $('#_qf_Export_cancel').html('<i class="crm-i fa-check" role="img" aria-hidden="true"></i> {/literal}{$tsdone|escape}{literal}');
    });
  });
</script>
{/literal}

