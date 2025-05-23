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

<details class="crm-accordion-bold crm-commPrefs-accordion">
 <summary>
    {$title}
  </summary>
<div id="commPrefs" class="crm-accordion-body">
    <table class="form-layout-compressed" >
        {if array_key_exists('communication_style_id', $form)}
          <tr><td colspan='4'>
            <span class="label">{$form.communication_style_id.label} {help id="id-communication_style" file="CRM/Contact/Form/Contact.hlp"}</span>
            <span class="value">{$form.communication_style_id.html}</span>
          </td><tr>
        {/if}
        <tr>
          {if array_key_exists('email_greeting_id', $form)}
            <td>{$form.email_greeting_id.label}</td>
          {/if}
          {if array_key_exists('postal_greeting_id', $form)}
            <td>{$form.postal_greeting_id.label}</td>
          {/if}
          {if array_key_exists('addressee_id', $form)}
            <td>{$form.addressee_id.label}</td>
          {/if}
          {if array_key_exists('email_greeting_id', $form) OR array_key_exists('postal_greeting_id', $form) OR array_key_exists('addressee_id', $form)}
            <td>&nbsp;&nbsp;{help id="id-greeting" file="CRM/Contact/Form/Contact.hlp"}</td>
          {/if}
        </tr>
        <tr>
            {if array_key_exists('email_greeting_id', $form)}
                <td>
                    <span id="email_greeting" {if !empty($email_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.email_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($email_greeting_display) and $action eq 2}
                      <div data-id="email_greeting" class="replace-plain" title="{ts escape='htmlattribute'}Click to edit{/ts}">
                        {$email_greeting_display}
                      </div>
                    {/if}
                </td>
            {/if}
            {if array_key_exists('postal_greeting_id', $form)}
                <td>
                    <span id="postal_greeting" {if !empty($postal_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.postal_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($postal_greeting_display) and $action eq 2}
                      <div data-id="postal_greeting" class="replace-plain" title="{ts escape='htmlattribute'}Click to edit{/ts}">
                        {$postal_greeting_display}
                      </div>
                    {/if}
                </td>
            {/if}
            {if array_key_exists('addressee_id', $form)}
                <td>
                    <span id="addressee" {if !empty($addressee_display) and $action eq 2} class="hiddenElement"{/if}>{$form.addressee_id.html|crmAddClass:big}</span>
                    {if !empty($addressee_display) and $action eq 2}
                      <div data-id="addressee" class="replace-plain" title="{ts escape='htmlattribute'}Click to edit{/ts}">
                        {$addressee_display}
                      </div>
                    {/if}
                </td>
            {/if}
        </tr>
        <tr id="greetings1" class="hiddenElement">
          {if array_key_exists('email_greeting_custom', $form)}
            <td><span id="email_greeting_id_label" class="hiddenElement">{$form.email_greeting_custom.label}</span></td>
          {/if}
          {if array_key_exists('postal_greeting_custom', $form)}
            <td><span id="postal_greeting_id_label" class="hiddenElement">{$form.postal_greeting_custom.label}</span></td>
          {/if}
          {if array_key_exists('addressee_custom', $form)}
            <td><span id="addressee_id_label" class="hiddenElement">{$form.addressee_custom.label}</span></td>
          {/if}
        </tr>
        <tr id="greetings2" class="hiddenElement">
          {if array_key_exists('email_greeting_custom', $form)}
            <td><span id="email_greeting_id_html" class="hiddenElement">{$form.email_greeting_custom.html|crmAddClass:big}</span></td>
          {/if}
           {if array_key_exists('postal_greeting_custom', $form)}
            <td><span id="postal_greeting_id_html" class="hiddenElement">{$form.postal_greeting_custom.html|crmAddClass:big}</span></td>
          {/if}
          {if array_key_exists('addressee_custom', $form)}
            <td><span id="addressee_id_html" class="hiddenElement">{$form.addressee_custom.html|crmAddClass:big}</span></td>
          {/if}
        </tr>
        <tr>
          {foreach key=key item=item from=$commPreference}
            <td>
              <br/><span class="label">{$form.$key.label}</span> {help id="id-$key" file="CRM/Contact/Form/Contact.hlp"}
              <br/>{$form.$key.html}
            </td>
          {/foreach}
          <td>
            <br/><span class="label">{$form.preferred_language.label}</span>
            <br/>{$form.preferred_language.html}
          </td>
        </tr>
        <tr>
          <td>{$form.is_opt_out.html} {$form.is_opt_out.label} {help id="id-optOut" title=$form.is_opt_out.label file="CRM/Contact/Form/Contact.hlp"}</td>
        </tr>
    </table>
 </div>
</details>
{include file="CRM/Contact/Form/Edit/CommunicationPreferences.js.tpl"}
