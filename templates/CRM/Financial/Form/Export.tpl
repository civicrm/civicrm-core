{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* Confirmation of Export Batch(s)  *}
<h3>{ts}Export Batch{/ts}</h3>
<div class="messages status">
  <div class="icon inform-icon"></div>
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
  cj(function(){
    cj('input[name="export_format"]').filter('[value=IIF]').attr('checked', true);
    cj('#_qf_Export_next').click(function(){
      cj(this).hide();
      cj('#_qf_Export_cancel').val('{/literal}{ts}Done{/ts}{literal}');
    });
  });
</script>
{/literal}

