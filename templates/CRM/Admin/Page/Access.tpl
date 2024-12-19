{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=docUrlText}{ts}Access Control Documentation{/ts}{/capture}
{capture assign=docLink}{docURL page="user/initial-set-up/permissions-and-access-control/" text=$docUrlText}{/capture}
<div class="help">
  <p>{ts 1=$docLink}ACLs (Access Control Lists) allow you control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of Data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}
  {if $config->userSystem->is_drupal EQ '1'}{ts}Note that a CiviCRM ACL Role is not related to the Drupal Role.{/ts}{/if}</p>
  <p>{ts}<strong>EXAMPLE:</strong> 'Team Leaders' (<em>ACL Role</em>) can 'Edit' (<em>Operation</em>) all contacts in the 'Active Volunteers Group' (<em>Data</em>).{/ts}</p>
  <p>{ts}CiviCRM ACLs can control access to specific CiviCRM contact groups. You can also configure ACLs to grant or deny access to specific Events, Profiles or Custom Data Fields.{/ts}</p>
  {if $config->userFramework == 'Standalone'}
    <p>{ts 1=$ufAccessURL|smarty:nodefaults}Note that <a href="%1">User Role</a> permissions take precedence over CiviCRM ACLs. If you wish to use CiviCRM ACLs, first disable the related permission in User Roles, and then gradually add ACLs to replace that permission for certain groups of contacts.{/ts}
  {else}
    <p>{ts 1=$ufAccessURL|smarty:nodefaults 2=$jAccessParams 3=$config->userFramework}Note that <a href='%1' %2>%3 permissions</a> take precedence over CiviCRM ACLs. If you wish to use CiviCRM ACLs, first disable the related permission in %3 for a user role, and then gradually add ACLs to replace that permission for certain groups of contacts.{/ts}
  {/if}
</div>
<table>
  <tr>
    {if $config->userFramework == 'Standalone'}
      <td class="nowrap"><a href="{$ufAccessURL|smarty:nodefaults}" id="adminAccess"><i class="crm-i fa-chevron-right fa-fw" aria-hidden="true"></i>{ts}User Roles{/ts}</a></td>
    {else}
      <td class="nowrap"><a href="{$ufAccessURL|smarty:nodefaults}" {$jAccessParams} id="adminAccess"><i class="crm-i fa-chevron-right fa-fw" aria-hidden="true"></i> {ts 1=$config->userFramework}%1 Permissions{/ts}</a></td>
    {/if}
    <td>{ts}Grant access to CiviCRM components and other CiviCRM permissions.{/ts}</td>
  </tr>
  <tr><td colspan="2" class="separator"><strong>{ts}Use following steps if you need to control View and/or Edit permissions for specific contact groups, specific profiles or specific custom data fields.{/ts}</strong></td></tr>
  <tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/admin/options/acl_role' q="reset=1"}" id="editACLRoles"><i class="crm-i fa-users fa-fw" aria-hidden="true"></i> {ts}1. Manage Roles{/ts}</a></td>
    <td>{ts}Each CiviCRM ACL Role is assigned a set of permissions. Use this link to create or edit the different roles needed for your site.{/ts}</td>
  </tr>
  <tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/acl/entityrole' q="reset=1"}" id="editRoleAssignments"><i class="crm-i fa-user-plus fa-fw" aria-hidden="true"></i> {ts}2. Assign Users to CiviCRM ACL Roles{/ts}</a></td>
    <td>{ts}Once you have defined CiviCRM ACL Roles and granted ACLs to those Roles, use this link to assign users to role(s).{/ts}</td>
  </tr>
  <tr>
    <td class="nowrap"><a href="{crmURL p='civicrm/acl' q="reset=1"}" id="editACLs"><i class="crm-i fa-id-card-o fa-fw" aria-hidden="true"></i> {ts}3. Manage ACLs{/ts}</a></td>
    <td>{ts}ACLs define permission to do an operation on a set of data, and grant that permission to a CiviCRM ACL Role. Use this link to create or edit the ACLs for your site.{/ts}</td>
  </tr>
</table>
