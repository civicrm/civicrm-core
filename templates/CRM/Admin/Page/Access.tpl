{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=docLink}{docURL page="user/initial-set-up/permissions-and-access-control/" text="Access Control Documentation"}{/capture}
<div class="help">
    <p>{ts 1=$docLink}ACLs (Access Control Lists) allow you control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of Data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}
    {if $config->userSystem->is_drupal EQ '1'}{ts}Note that a CiviCRM ACL Role is not related to the Drupal Role.{/ts}{/if}</p>
    <p>{ts}<strong>EXAMPLE:</strong> 'Team Leaders' (<em>ACL Role</em>) can 'Edit' (<em>Operation</em>) all contacts in the 'Active Volunteers Group' (<em>Data</em>).{/ts}</p>
    <p>{ts 1=$ufAccessURL 2=$jAccessParams 3=$config->userFramework}Use <a href='%1' %2>%3 Access Control</a> to manage basic access to CiviCRM components and menu items. Use CiviCRM ACLs to control access to specific CiviCRM contact groups. You can also configure ACLs to grant or deny access to specific Events, Profiles, and/or Custom Data Fields.{/ts}</p>
   <p>{ts 1=$config->userFramework}Note that %1 Access Control permissions take precedence over CiviCRM ACLs. If you wish to use CiviCRM ACLs, first disable the related permission in %1 Access control for a user role, and then gradually add ACLs to replace that permission for certain groups of contacts.{/ts}
</div>

    <table class="report">
        <tr>
            <td class="nowrap"><a href="{$ufAccessURL}" {$jAccessParams} id="adminAccess">&raquo; {ts 1=$config->userFramework}%1 Access Control{/ts}</a></td>
            <td>{ts}Grant access to CiviCRM components and other CiviCRM permissions.{/ts}</td>
        </tr>
        <tr><td colspan="2" class="separator"><strong>{ts}Use following steps if you need to control View and/or Edit permissions for specific contact groups, specific profiles or specific custom data fields.{/ts}</strong></td></tr>
    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/admin/options/acl_role' q="reset=1"}" id="editACLRoles">&raquo; {ts}1. Manage Roles{/ts}</a></td>
        <td>{ts}Each CiviCRM ACL Role is assigned a set of permissions. Use this link to create or edit the different roles needed for your site.{/ts}</td>
    </tr>
    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/acl/entityrole' q="reset=1"}" id="editRoleAssignments">&raquo; {ts}2. Assign Users to CiviCRM ACL Roles{/ts}</a></td>
        <td>{ts}Once you have defined CiviCRM ACL Roles and granted ACLs to those Roles, use this link to assign users to role(s).{/ts}</td>
    </tr>
    <tr>
        <td class="nowrap"><a href="{crmURL p='civicrm/acl' q="reset=1"}" id="editACLs">&raquo; {ts}3. Manage ACLs{/ts}</a></td>
        <td>{ts}ACLs define permission to do an operation on a set of data, and grant that permission to a CiviCRM ACL Role. Use this link to create or edit the ACLs for your site.{/ts}</td>
    </tr>
    </table>
