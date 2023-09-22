{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="crm-demographic-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-dependent-fields='["#crm-contactname-content"]' data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_Demographics"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Edit demographics{/ts}"{/if}>
    {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="crm-i fa-pencil" aria-hidden="true"></span> {ts}Edit demographics{/ts}
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">{ts}Gender{/ts}</div>
      <div class="crm-content crm-contact-gender_display">{$gender_display}</div>
    </div>

    <div class="crm-summary-row">
      <div class="crm-label">{ts}Date of Birth{/ts}</div>
      <div class="crm-content crm-contact-birth_date_display">
        {assign var="date_format" value=$fields.birth_date.smarty_view_format}
        {if $birth_date}
          {$birth_date|crmDate:$date_format}
        {/if}
      </div>
    </div>
      {if !empty($is_deceased)}
        {if !empty($deceased_date)}
          <div class="crm-summary-row">
            <div class="crm-label">{ts}Date Deceased{/ts}</div>
            <div class="crm-content crm-contact-deceased_date_display">
              {assign var="date_format" value = $fields.birth_date.smarty_view_format}
              {$deceased_date|crmDate:$date_format}
              {if $birth_date}({ts}Age{/ts} {if $age.y}{ts count=$age.y plural='%count years'}%count year{/ts}{elseif $age.m}{ts count=$age.m plural='%count months'}%count month{/ts}{/if}){/if}
            </div>
          </div>
        {else}
          <div class="crm-summary-row">
            <div class="crm-label"></div>
            <div class="crm-content crm-contact-deceased_message"><span class="font-red upper">{ts}Contact is Deceased{/ts}</span></div>
          </div>
        {/if}
      {else}
        <div class="crm-summary-row">
          <div class="crm-label">{ts}Age{/ts}</div>
          <div class="crm-content crm-contact-age_display">{if $age.y}{ts count=$age.y plural='%count years'}%count year{/ts}{elseif $age.m}{ts count=$age.m plural='%count months'}%count month{/ts}{/if}</div>
        </div>
      {/if}
    </div>
  </div>
