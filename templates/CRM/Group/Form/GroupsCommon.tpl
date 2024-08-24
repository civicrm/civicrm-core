{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if array_key_exists('parents', $form)}
  <table class="form-layout-compressed">
    <tr class="crm-group-form-block-parents">
      <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.parents.label}</td>
      <td>{$form.parents.html|crmAddClass:huge}</td>
    </tr>
  </table>
{/if}
{if array_key_exists('organization_id', $form)}
  <h3>{ts}Associated Organization{/ts} {help id="id-group-organization" file="CRM/Group/Page/Group.hlp"}</h3>
  <table class="form-layout-compressed">
    <tr class="crm-group-form-block-organization">
      <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.organization_id.label}</td>
      <td>{$form.organization_id.html|crmAddClass:huge}
      </td>
    </tr>
  </table>
{/if}
