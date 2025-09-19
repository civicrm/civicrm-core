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
        {$form.household_name.label nofilter}<br/>
        {$form.household_name.html nofilter}
      </td>
      <td>
        {$form.nick_name.label nofilter}<br/>
        {$form.nick_name.html nofilter}
      </td>
    {if array_key_exists('contact_sub_type', $form)}
    </tr>
    <tr>
      <td>
        {$form.contact_sub_type.label nofilter}<br />
        {$form.contact_sub_type.html nofilter}
      </td>
    {/if}
      <td>
        {$form.is_deceased.label nofilter}<br />
        {$form.is_deceased.html nofilter}
      </td>
      <td id="showDeceasedDate">
        {$form.deceased_date.label nofilter}<br />
        {$form.deceased_date.html nofilter}
      </td>
    </tr>
  {/crmRegion}
</table>

{include file="CRM/Contact/Form/ShowDeceasedDate.js.tpl"}
