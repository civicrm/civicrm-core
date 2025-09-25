{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* tpl for building Individual related fields *}
<table class="form-layout-compressed">
  {crmRegion name="contact-form-edit-individual"}
    <tr>
      {if !empty($form.prefix_id)}
      <td>
        {$form.prefix_id.label nofilter}<br/>
        {$form.prefix_id.html nofilter}
      </td>
      {/if}
      {if $form.formal_title}
      <td>
        {$form.formal_title.label nofilter}<br/>
        {$form.formal_title.html nofilter}
      </td>
      {/if}
      {if !empty($form.first_name)}
      <td>
        {$form.first_name.label nofilter}<br />
        {$form.first_name.html nofilter}
      </td>
      {/if}
      {if !empty($form.middle_name)}
      <td>
        {$form.middle_name.label nofilter}<br />
        {$form.middle_name.html nofilter}
      </td>
      {/if}
      {if !empty($form.last_name)}
      <td>
        {$form.last_name.label nofilter}<br />
        {$form.last_name.html nofilter}
      </td>
      {/if}
      {if !empty($form.suffix_id)}
      <td>
        {$form.suffix_id.label nofilter}<br/>
        {$form.suffix_id.html nofilter}
      </td>
      {/if}
    </tr>

    <tr>
      <td colspan="2">
        {$form.employer_id.label nofilter}&nbsp;{help id="employer_id" file="CRM/Contact/Form/Contact.hlp"}<br />
        {$form.employer_id.html nofilter}
      </td>
      <td>
        {$form.job_title.label nofilter}<br />
        {$form.job_title.html nofilter}
      </td>
      <td>
        {$form.nick_name.label nofilter}<br />
        {$form.nick_name.html nofilter}
      </td>
      {if !empty($form.contact_sub_type)}
      <td>
        {$form.contact_sub_type.label nofilter}<br />
        {$form.contact_sub_type.html nofilter}
      </td>
      {/if}
    </tr>
  {/crmRegion}
</table>
