{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* template for building communication preference block*}
<div id="crm-communication-pref-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_CommunicationPreferences"{rdelim}' data-dependent-fields='["#crm-phone-content", "#crm-email-content", ".address.crm-inline-edit:not(.add-new)", "#crm-contact-actions-wrapper"]'{/if}>
  <div class="crm-clear crm-inline-block-content"{if $permission EQ 'edit'} title="{ts}Edit communication preferences{/ts}"{/if}>
    {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="crm-i fa-pencil"></span> {ts}Edit communication preferences{/ts}
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Privacy{/ts}</div>
      <div class="crm-content crm-contact-privacy_values font-red upper">
        {foreach from=$privacy item=priv key=index}
          {if $priv}{$privacy_values.$index}<br/>{/if}
        {/foreach}
        {if $is_opt_out}{ts}No Bulk Emails (User Opt Out){/ts}{/if}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Preferred Method(s){/ts}</div>
      <div class="crm-content crm-contact-preferred_communication_method_display">
        {$preferred_communication_method_display}
      </div>
    </div>
    {if $preferred_language}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Preferred Language{/ts}</div>
      <div class="crm-content crm-contact-preferred_language">
        {$preferred_language}
      </div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Email Format{/ts}</div>
      <div class="crm-content crm-contact-preferred_mail_format">
        {$preferred_mail_format}
      </div>
    </div>
    {if isset($communication_style_display)}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Communication Style{/ts}</div>
      <div class="crm-content crm-contact-communication_style_display">
        {$communication_style_display}
      </div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Email Greeting{/ts}</div>
      <div class="crm-content crm-contact-email_greeting_display">
        {$email_greeting_display}
        {if !empty($email_greeting_custom)}<span class="crm-custom-greeting">({ts}Customized{/ts})</span>{/if}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Postal Greeting{/ts}</div>
      <div class="crm-content crm-contact-postal_greeting_display">
        {$postal_greeting_display}
        {if !empty($postal_greeting_custom)}<span class="crm-custom-greeting" >({ts}Customized{/ts})</span>{/if}
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Addressee{/ts}</div>
      <div class="crm-content crm-contact-addressee_display">
        {$addressee_display}
        {if !empty($addressee_custom)}<span class="crm-custom-greeting">({ts}Customized{/ts})</span>{/if}
      </div>
    </div>
  </div>
</div>
