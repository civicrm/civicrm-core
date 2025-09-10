{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the communication preferences in all the three types of contact *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

 <div class="crm-inline-edit-form">
    <div class="crm-inline-button">
      {include file="CRM/common/formButtons.tpl" location=''}
    </div>
    <div class="crm-clear">
      {foreach key=key item=item from=$commPreference}
      <div class="crm-summary-row">
        <div class="crm-label">{$form.$key.label}
          {help id=$key file="CRM/Contact/Form/Contact.hlp"}
        </div>
        <div class="crm-content">
          {foreach key=k item=i from=$item}
            {$form.$key.$k.html}<br/>
          {/foreach}
        </div>
      </div>
      {if $key eq 'privacy'}
      <div class="crm-summary-row">
        <div class="crm-label">&nbsp;</div>
        <div class="crm-content">{$form.is_opt_out.html} {$form.is_opt_out.label} {help id="is_opt_out" file="CRM/Contact/Form/Contact.hlp"}
        </div>
      </div>
      {/if}
      {/foreach}
      <div class="crm-summary-row">
        <div class="crm-label">
          {$form.preferred_language.label}
        </div>
        <div class="crm-content">
          {$form.preferred_language.html}
        </div>
      </div>

      {if !empty($form.communication_style_id)}
      <div class="crm-summary-row">
        <div class="crm-label">
          {$form.communication_style_id.label} {help id="communication_style" file="CRM/Contact/Form/Contact.hlp"}
        </div>
        <div class="crm-content">
          {$form.communication_style_id.html}
        </div>
      </div>
      {/if}

      {if !empty($form.email_greeting_id)}
      <div class="crm-summary-row">
        <div class="crm-label">{$form.email_greeting_id.label}</div>
        <div class="crm-content">
          <span id="email_greeting" {if !empty($email_greeting_display)} class="hiddenElement"{/if}>
            {$form.email_greeting_id.html|crmAddClass:big}
          </span>
          {if !empty($email_greeting_display)}
            <div data-id="email_greeting" class="replace-plain big" title="{ts escape='htmlattribute'}Click to edit{/ts}">
              {$email_greeting_display}
            </div>
          {/if}
          {if !empty($form.email_greeting_custom)}
            <span id="email_greeting_id_html" class="hiddenElement">
              <br/>{$form.email_greeting_custom.html|crmAddClass:big}
            </span>
          {/if}
         </div>
       </div>
      {/if}


      {if !empty($form.postal_greeting_id)}
      <div class="crm-summary-row">
        <div class="crm-label">{$form.postal_greeting_id.label}</div>
        <div class="crm-content">
          <span id="postal_greeting" {if !empty($postal_greeting_display)} class="hiddenElement"{/if}>
            {$form.postal_greeting_id.html|crmAddClass:big}
          </span>
          {if !empty($postal_greeting_display)}
            <div data-id="postal_greeting" class="replace-plain big" title="{ts escape='htmlattribute'}Click to edit{/ts}">
              {$postal_greeting_display}
            </div>
          {/if}
          {if !empty($form.postal_greeting_custom)}
            <span id="postal_greeting_id_html" class="hiddenElement">
              <br/>{$form.postal_greeting_custom.html|crmAddClass:big}
            </span>
          {/if}
        </div>
      </div>
      {/if}

      {if !empty($form.addressee_id)}
      <div class="crm-summary-row">
        <div class="crm-label">{$form.addressee_id.label}</div>
        <div class="crm-content">
          <span id="addressee" {if !empty($addressee_display)} class="hiddenElement"{/if}>
            {$form.addressee_id.html|crmAddClass:big}
          </span>
          {if !empty($addressee_display)}
            <div data-id="addressee" class="replace-plain big" title="{ts escape='htmlattribute'}Click to edit{/ts}">
              {$addressee_display}
            </div>
          {/if}
          {if !empty($form.addressee_custom)}
            <span id="addressee_id_html" class="hiddenElement">
              <br/>{$form.addressee_custom.html|crmAddClass:big}
            </span>
          {/if}
         </div>
       </div>
      {/if}

    </div>
 </div>
{include file="CRM/Contact/Form/Edit/CommunicationPreferences.js.tpl"}
