{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* template for building email block*}
<div id="crm-email-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Email"{rdelim}' data-dependent-fields='["#crm-contact-actions-wrapper"]'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Add or edit email{/ts}"{/if}>
  {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="batch-edit"></span>{if empty($email)}{ts}Add email{/ts}{else}{ts}Add or edit email{/ts}{/if}
    </div>
  {/if}
  {if empty($email)}
    <div class="crm-summary-row">
      <div class="crm-label">
        {ts}Email{/ts}
        {if $privacy.do_not_email}<span class="icon privacy-flag do-not-email" title="{ts}Privacy flag: Do Not Email{/ts}"></span>{/if}
      </div>
      <div class="crm-content"></div>
    </div>
  {/if}
  {foreach from=$email key="blockId" item=item}
    {if $item.email}
    <div class="crm-summary-row {if $item.is_primary eq 1}primary{/if}">
      <div class="crm-label">
        {$item.location_type} {ts}Email{/ts}
        {if $privacy.do_not_email}<span class="icon privacy-flag do-not-email" title="{ts}Privacy flag: Do Not Email{/ts}"></span>{elseif $item.on_hold}<span class="icon privacy-flag email-hold" title="{ts}Email on hold - generally due to bouncing.{/ts}"></span>{/if}
      </div>
      <div class="crm-content crm-contact_email {if $item.is_primary eq 1}primary{/if}">
        {if !$item.on_hold and !$privacy.do_not_email}
          <a href="{crmURL p="civicrm/activity/email/add" q="action=add&reset=1&email_id=`$item.id`"}" class="crm-popup" title="{ts 1=$item.email}Send email to %1{/ts}">
            {$item.email}
          </a>
        {else}
          {$item.email}
        {/if}
        {if $item.on_hold == 2}&nbsp;({ts}On Hold - Opt Out{/ts}){elseif $item.on_hold}&nbsp;({ts}On Hold{/ts}){/if}{if $item.is_bulkmail}&nbsp;({ts}Bulk{/ts}){/if}
        {if $item.signature_text OR $item.signature_html}
        <span class="signature-link description">
          <a href="#" title="{ts}Signature{/ts}" onClick="showHideSignature( '{$blockId}' ); return false;">{ts}(signature){/ts}</a>
        </span>
        {/if}
        <div id="Email_Block_{$blockId}_signature" class="hiddenElement">
          <strong>{ts}Signature HTML{/ts}</strong><br />{$item.signature_html}<br /><br />
        <strong>{ts}Signature Text{/ts}</strong><br />{$item.signature_text|nl2br}</div>
      </div>
    </div>
    {/if}
  {/foreach}
  </div>
</div>

{literal}
<script type="text/javascript">

function showHideSignature( blockId ) {
  cj("#Email_Block_" + blockId + "_signature").show( );   

  cj("#Email_Block_" + blockId + "_signature").dialog({
      title: "Signature",
      modal: true,
      width: 900,
      height: 500,
      beforeclose: function(event, ui) {
        cj(this).dialog("destroy");
      },
      buttons: {
        "Done": function() { 
                  cj(this).dialog("destroy"); 
                } 
      } 
  });
}
</script>
{/literal}
