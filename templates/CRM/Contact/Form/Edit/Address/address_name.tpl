{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if array_key_exists('name', $form.address.$blockId)}
  <tr>
      <td colspan="2">
        {$form.address.$blockId.name.label} {help id="id-address-name" file="CRM/Contact/Form/Contact.hlp"}<br />
        {$form.address.$blockId.name.html}
      </td>
  </tr>
{/if}
