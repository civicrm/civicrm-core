{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* tpl for building Household related fields *}
<table class="form-layout-compressed">
  {crmRegion name="contact-form-edit-household"}
    <tr>
      <td>
        {$form.household_name.label}<br/>
        {$form.household_name.html}
      </td>
      <td>
        {$form.nick_name.label}<br/>
        {$form.nick_name.html}
      </td>
      <td>
        {if array_key_exists('contact_sub_type', $form)}
          {$form.contact_sub_type.label}<br />
          {$form.contact_sub_type.html}
        {/if}
      </td>
    </tr>
  {/crmRegion}
</table>
