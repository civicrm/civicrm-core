{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the plugin for the email block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current email block id in evaluation, and assigned in the CRM/Contact/Form/Location.php file *}

{* note this is only called from CRM_Contact_Form_Contact in core so the className if clauses are not needed & should be phased out *}
{if !$addBlock}
  <tr>
    <td>{ts}Email{/ts}
      &nbsp;&nbsp;<a id='addEmail' href="#" title="{ts escape='htmlattribute'}Add{/ts}" onClick="buildAdditionalBlocks( 'Email', '{$className}');return false;">{ts}add{/ts}</a>
    </td>
    {if $className eq 'CRM_Contact_Form_Contact'}
      <td>
        {capture assign='colTitle'}{ts}On Hold?{/ts}{/capture}{$colTitle}
        {help id="id-onhold" file="CRM/Contact/Form/Contact.hlp" title=$colTitle}
      </td>
      <td>
        {capture assign='colTitle'}{ts}Bulk Mailings?{/ts}{/capture}{$colTitle}
        {help id="id-bulkmail" file="CRM/Contact/Form/Contact.hlp" title=$colTitle}
      </td>
      <td id="Email-Primary" class="hiddenElement">{ts}Primary?{/ts}</td>
    {/if}
  </tr>
{/if}

<tr id="Email_Block_{$blockId}">
  <td>{$form.email.$blockId.email.html|crmAddClass:email}&nbsp;{$form.email.$blockId.location_type_id.html}
    {if $isAddSignatureFields}
      <div class="clear"></div>
      <details class="email-signature crm-accordion-light">
        <summary>
          {ts}Signature{/ts}
        </summary>
        <div id="signatureBlock{$blockId}" class="crm-accordion-body">
          {$form.email.$blockId.signature_html.label}<br/>{$form.email.$blockId.signature_html.html}<br/>
          {$form.email.$blockId.signature_text.label}<br/>{$form.email.$blockId.signature_text.html}
        </div>
      </details>
    {/if}
  </td>
  <td align="center">{$form.email.$blockId.on_hold.html}</td>
  <td align="center" id="Email-Bulkmail-html" {if !$multipleBulk}class="crm-email-bulkmail"{/if}>{$form.email.$blockId.is_bulkmail.html}</td>
  <td align="center" id="Email-Primary-html" {if $blockId eq 1}class="hiddenElement"{/if}>
    {$form.email.$blockId.is_primary.1.html}
  </td>
  {if $blockId gt 1}
    <td>
      <a href="#" title="{ts escape='htmlattribute'}Delete Email Block{/ts}" onClick="removeBlock( 'Email', '{$blockId}' ); return false;">{ts}delete{/ts}</a>
    </td>
  {/if}
</tr>
