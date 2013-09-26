{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
<div class="crm-block crm-form-block crm-search-form-block">
<table class="form-layout">
    <tr>
        <td>{$form.mailing_name.label}<br />
            {$form.mailing_name.html|crmAddClass:big} {help id="id-mailing_name"}
        </td>
    </tr>
    <tr>
        <td>
      <label>{if $sms eq 1}{ts}SMS Date{/ts}{else}{ts}Mailing Date{/ts}{/if}</label>
  </td>
    </tr>
    <tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="mailing" from='_from' to='_to'}
    </tr>
    <tr>
        <td colspan="1">{$form.sort_name.label}<br />
            {$form.sort_name.html|crmAddClass:big} {help id="id-create_sort_name"}
            <br/><br/>
            <div class="crm-search-form-block-is_archive">
            {$form.is_archived.label}<br/>
            {$form.is_archived.html}
            <span class="crm-clear-link">(<a href="#r">{ts}clear{/ts}</a>)</span>
            </div>
        </td>
        {if $form.mailing_status}
           <td width="100%"><label>{if $sms eq 1}{ts}SMS Status{/ts}{else}{ts}Mailing Status{/ts}{/if}</label><br />
           <div class="listing-box" style="width: auto; height: 100px">
             {foreach from=$form.mailing_status item="mailing_status_val"}
             <div class="{cycle values="odd-row,even-row"}">
               {$mailing_status_val.html}
             </div>
            {/foreach}
            <div class='odd-row'>
              {$form.status_unscheduled.html}
            </div>
           </div><br />
           </td>
        {/if}
    </tr>

    {* campaign in mailing search *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignContext="componentSearch" campaignTrClass='' campaignTdClass=''}

    <tr>
        <td>{$form.buttons.html}</td><td colspan="2"></td>
    </tr>
</table>
</div>

{literal}
<script type="text/javascript">
  cj(document).ready( function( ) {
    var archiveOption = cj("input[name^='is_archived']:radio");
    cj('#status_unscheduled').change(function() {
      if (cj(this).prop('checked') ) {
        archiveOption.attr('checked',false);
        archiveOption.attr('readonly',true);
      } else {
        archiveOption.attr('readonly',false);
      }
    }).trigger('change');
    archiveOption.change(function() {
      if (cj("input[name^='is_archived']:radio:checked").length) {
        disableDraft();
      } else {
        cj('#status_unscheduled').attr('readonly',false); 
      }
    }).trigger('change');
    cj(".crm-search-form-block-is_archive .crm-clear-link a").click(function() {
      archiveOption.attr('checked',false);
      cj('#status_unscheduled').attr('readonly',false); 
    });
  });

  function disableDraft() {
    cj('#status_unscheduled').attr('checked',false); 
    cj('#status_unscheduled').attr('readonly',true); 
  } 
</script>
{/literal}
