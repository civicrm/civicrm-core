{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* This file provides the plugin for the email block *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller*}
{* @var $blockId Contains the current email block id in evaluation, and assigned in the CRM/Contact/Form/Location.php file *}

{if !$addBlock}
  <tr>
    <td>{ts}Email{/ts}
      &nbsp;&nbsp;<a id='addEmail' href="#" title={ts}Add{/ts} onClick="buildAdditionalBlocks( 'Email', '{$className}');return false;">{ts}add{/ts}</a>
    </td>
    {if $className eq 'CRM_Contact_Form_Contact'}
      <td>{ts}On Hold?{/ts} {help id="id-onhold" file="CRM/Contact/Form/Contact.hlp"}</td>
      <td>{ts}Bulk Mailings?{/ts} {help id="id-bulkmail" file="CRM/Contact/Form/Contact.hlp"}</td>
      <td id="Email-Primary" class="hiddenElement">{ts}Primary?{/ts}</td>
    {/if}
  </tr>
{/if}

<tr id="Email_Block_{$blockId}">
  <td>{$form.email.$blockId.email.html|crmAddClass:email}&nbsp;{$form.email.$blockId.location_type_id.html}
    <div class="clear"></div>
    {if $className eq 'CRM_Contact_Form_Contact'}
      <div class="email-signature crm-collapsible collapsed">
        <div class="collapsible-title">
          {ts}Signature{/ts}
        </div>
        <div id="signatureBlock{$blockId}">
          {$form.email.$blockId.signature_html.label}<br/>{$form.email.$blockId.signature_html.html}<br/>
          {$form.email.$blockId.signature_text.label}<br/>{$form.email.$blockId.signature_text.html}
        </div>
      </div>
    {/if}
  </td>
  <td align="center">{$form.email.$blockId.on_hold.html}</td>
  {if $multipleBulk}
    <td align="center" id="Email-Bulkmail-html">{$form.email.$blockId.is_bulkmail.html}</td>
  {else}
    <td align="center" id="Email-Bulkmail-html">{$form.email.$blockId.is_bulkmail.1.html}</td>
  {/if}
  <td align="center" id="Email-Primary-html" {if $blockId eq 1}class="hiddenElement"{/if}>
    {$form.email.$blockId.is_primary.1.html}
  </td>
  {if $blockId gt 1}
    <td>
      <a href="#" title="{ts}Delete Email Block{/ts}" onClick="removeBlock( 'Email', '{$blockId}' ); return false;">{ts}delete{/ts}</a>
    </td>
  {/if}
</tr>
