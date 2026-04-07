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
    {if array_key_exists('contact_sub_type', $form)}
    </tr>
    <tr>
      <td>
        {$form.contact_sub_type.label}<br />
        {$form.contact_sub_type.html}
      </td>
    {/if}
      <td>
        {$form.is_deceased.label}<br />
        {$form.is_deceased.html}
      </td>
      <td id="showDeceasedDate">
        {$form.deceased_date.label}<br />
        {$form.deceased_date.html}
      </td>
    </tr>
  {/crmRegion}
</table>

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}
