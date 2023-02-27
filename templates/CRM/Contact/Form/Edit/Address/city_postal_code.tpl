{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<tr><td colspan="3" style="padding:0;">
<table class="crm-address-element">
<tr>
    {if !empty($form.address.$blockId.city)}
       <td>
          {$form.address.$blockId.city.label}<br />
          {$form.address.$blockId.city.html}
       </td>
    {/if}
    {if !empty($form.address.$blockId.postal_code)}
       <td>
          {$form.address.$blockId.postal_code.label}<br />
          {$form.address.$blockId.postal_code.html}
       </td>
      {if array_key_exists('postal_code_suffix', $form.address.$blockId)}
          <td>
            {$form.address.$blockId.postal_code_suffix.label} {help id="id-postal-code-suffix" file="CRM/Contact/Form/Contact.hlp"}<br/>
            {$form.address.$blockId.postal_code_suffix.html}
          <td>
      {/if}
    {/if}
    <td colspan="2">&nbsp;&nbsp;</td>
</tr>
</table>
</td></tr>
