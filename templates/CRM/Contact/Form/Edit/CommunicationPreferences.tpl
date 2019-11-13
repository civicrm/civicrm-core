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

<div class="crm-accordion-wrapper crm-commPrefs-accordion collapsed">
 <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
<div id="commPrefs" class="crm-accordion-body">
    <table class="form-layout-compressed" >
        {if !empty($form.communication_style_id)}
            <tr><td colspan='4'>
                <span class="label">{$form.communication_style_id.label} {help id="id-communication_style" file="CRM/Contact/Form/Contact.hlp"}</span>
                <span class="value">{$form.communication_style_id.html}</span>
            </td><tr>
        {/if}
        <tr>
            {if !empty($form.email_greeting_id)}
                <td>{$form.email_greeting_id.label}</td>
            {/if}
            {if !empty($form.postal_greeting_id)}
                <td>{$form.postal_greeting_id.label}</td>
            {/if}
            {if !empty($form.addressee_id)}
                <td>{$form.addressee_id.label}</td>
            {/if}
      {if !empty($form.email_greeting_id) OR !empty($form.postal_greeting_id) OR !empty($form.addressee_id)}
                <td>&nbsp;&nbsp;{help id="id-greeting" file="CRM/Contact/Form/Contact.hlp"}</td>
      {/if}
        </tr>
        <tr>
            {if !empty($form.email_greeting_id)}
                <td>
                    <span id="email_greeting" {if !empty($email_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.email_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($email_greeting_display) and $action eq 2}
                      <div data-id="email_greeting" class="replace-plain" title="{ts}Click to edit{/ts}">
                        {$email_greeting_display}
                      </div>
                    {/if}
                </td>
            {/if}
            {if !empty($form.postal_greeting_id)}
                <td>
                    <span id="postal_greeting" {if !empty($postal_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.postal_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($postal_greeting_display) and $action eq 2}
                      <div data-id="postal_greeting" class="replace-plain" title="{ts}Click to edit{/ts}">
                        {$postal_greeting_display}
                      </div>
                    {/if}
                </td>
            {/if}
            {if !empty($form.addressee_id)}
                <td>
                    <span id="addressee" {if !empty($addressee_display) and $action eq 2} class="hiddenElement"{/if}>{$form.addressee_id.html|crmAddClass:big}</span>
                    {if !empty($addressee_display) and $action eq 2}
                      <div data-id="addressee" class="replace-plain" title="{ts}Click to edit{/ts}">
                        {$addressee_display}
                      </div>
                    {/if}
                </td>
            {/if}
        </tr>
        <tr id="greetings1" class="hiddenElement">
            {if !empty($form.email_greeting_custom)}
                <td><span id="email_greeting_id_label" class="hiddenElement">{$form.email_greeting_custom.label}</span></td>
            {/if}
            {if !empty($form.postal_greeting_custom)}
                <td><span id="postal_greeting_id_label" class="hiddenElement">{$form.postal_greeting_custom.label}</span></td>
            {/if}
            {if !empty($form.addressee_custom)}
                <td><span id="addressee_id_label" class="hiddenElement">{$form.addressee_custom.label}</span></td>
            {/if}
        </tr>
        <tr id="greetings2" class="hiddenElement">
            {if !empty($form.email_greeting_custom)}
                <td><span id="email_greeting_id_html" class="hiddenElement">{$form.email_greeting_custom.html|crmAddClass:big}</span></td>
            {/if}
             {if !empty($form.postal_greeting_custom)}
                <td><span id="postal_greeting_id_html" class="hiddenElement">{$form.postal_greeting_custom.html|crmAddClass:big}</span></td>
            {/if}
            {if !empty($form.addressee_custom)}
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
            <td>{$form.is_opt_out.html} {$form.is_opt_out.label} {help id="id-optOut" file="CRM/Contact/Form/Contact.hlp"}</td>
            {if !empty($form.preferred_mail_format)}
                <td>{$form.preferred_mail_format.label} &nbsp;
                    {$form.preferred_mail_format.html} {help id="id-emailFormat" file="CRM/Contact/Form/Contact.hlp"}
                </td>
            {/if}
        </tr>
    </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
{include file="CRM/Contact/Form/Edit/CommunicationPreferences.js.tpl"}
