{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for building email block*}
<div id="crm-email-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Email"{rdelim}' data-dependent-fields='["#crm-contact-actions-wrapper"]'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts escape='htmlattribute'}Add or edit email{/ts}"{/if}>
  {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="crm-i fa-pencil" aria-hidden="true"></span> {if empty($email)}{ts}Add email{/ts}{else}{ts}Add or edit email{/ts}{/if}
    </div>
  {/if}
  {if empty($email)}
    <div class="crm-summary-row">
      <div class="crm-label">
        {ts}Email{/ts}
        {if $privacy.do_not_email}{privacyFlag field=do_not_email}{/if}
      </div>
      <div class="crm-content"></div>
    </div>
  {/if}
  {foreach from=$email key="blockId" item=item}
    {if $item.email}
    <div class="crm-summary-row {if !empty($item.is_primary)}primary{/if}">
      <div class="crm-label">
        {$item.location_type} {ts}Email{/ts}
        {privacyFlag field=do_not_email condition=$privacy.do_not_email}{privacyFlag field=on_hold condition=$item.on_hold}
      </div>
      <div class="crm-content crm-contact_email">
        {if !$item.on_hold and !$privacy.do_not_email}
          {if $mailingOutboundOption == 2} {* Outbound email is disabled, use a mailto link *}
            <a href="mailto:{$item.email}" title="{ts escape='htmlattribute' 1=$item.email}Send email to %1{/ts}">
            {$item.email}
            </a>
          {else}
            <a href="{crmURL p="civicrm/activity/email/add" q="action=add&reset=1&email_id=`$item.id`"}" class="crm-popup" title="{ts escape='htmlattribute' 1=$item.email}Send email to %1{/ts}">
            {$item.email}
            </a>
          {/if}
        {else}
          {$item.email}
        {/if}
        {crmAPI var='civi_mail' entity='Extension' action='get' full_name="civi_mail" is_active=1}
        {if $item.on_hold == 2}&nbsp;({ts}On Hold - Opt Out{/ts})&nbsp;{ts}{$item.hold_date|truncate:10:''|crmDate}{/ts}{elseif $item.on_hold}&nbsp;{if $civi_mail.count}<a href="{crmURL p="civicrm/contact/view/bounces" f="?email_id=`$item.id`"}" class="crm-popup" title="{ts escape='htmlattribute' 1=$item.email}Email Bounce History{/ts}">{/if}({ts}On Hold{/ts})&nbsp;{ts}{$item.hold_date|truncate:10:''|crmDate}{/ts}{if $civi_mail.count}&nbsp;<i class="crm-i fa-list-alt" role="img" aria-hidden="true"></i></a>{/if}{/if}{if $item.is_bulkmail}&nbsp;({ts}Bulk{/ts}){/if}
        {if !empty($item.signature_text) OR !empty($item.signature_html)}
        <span class="signature-link description">
          <a href="#" title="{ts escape='htmlattribute'}Signature{/ts}" onClick="showHideSignature( '{$blockId}' ); return false;">{ts}(signature){/ts}</a>
        </span>
        {/if}
        <div id="Email_Block_{$blockId}_signature" class="hiddenElement">
          <strong>{ts}Signature HTML{/ts}</strong><br />{if !empty($item.signature_html)}{$item.signature_html}{/if}<br /><br />
        <strong>{ts}Signature Text{/ts}</strong><br />{if !empty($item.signature_text)}{$item.signature_text|nl2br}{/if}</div>
      </div>
    </div>
      {include file="CRM/Contact/Page/Inline/BlockCustomData.tpl" entity='email' customGroups=$item.custom identifier=$blockId}
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
