{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* tpl for building Organization related fields *}
<table class="form-layout-compressed">
  {crmRegion name="contact-form-edit-organization"}
    <tr>
      <td>
        {$form.organization_name.label|purify nofilter}<br/>
        {$form.organization_name.html nofilter}
      </td>
      <td>
        {$form.legal_name.label|purify nofilter}<br/>
        {$form.legal_name.html nofilter}
      </td>
      <td>
        {$form.nick_name.label|purify nofilter}<br/>
        {$form.nick_name.html nofilter}
      </td>
      <td>
        {$form.sic_code.label|purify nofilter}<br/>
        {$form.sic_code.html nofilter}
      </td>
    </tr>
    <tr>
      {if array_key_exists('contact_sub_type', $form)}
        <td>
          {$form.contact_sub_type.label|purify nofilter}<br />
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

