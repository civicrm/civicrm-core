{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{capture assign=docLink}{docURL page="user/current/initial-set-up/permissions-and-access-control/" text="Access Control Documentation"}{/capture}
<div id="help">
    <p>{ts 1=$docLink}ACLs (Access Control Lists) allow you control access to CiviCRM data. An ACL consists of an <strong>Operation</strong> (e.g. 'View' or 'Edit'), a <strong>set of Data</strong> that the operation can be performed on (e.g. a group of contacts), and a <strong>Role</strong> that has permission to do this operation. Refer to the %1 for more info.{/ts}
    {if $config->userSystem->is_drupal EQ '1'}{ts}Note that a CiviCRM ACL Role is not related to the Drupal Role.{/ts}{/if}</p>
    <p>{ts}<strong>EXAMPLE:</strong> 'Team Leaders' (<em>ACL Role</em>) can 'Edit' (<em>Operation</em>) all contacts in the 'Active Volunteers Group' (<em>Data</em>).{/ts}</p>
    {if $config->userSystem->is_drupal EQ '1'}
        <p>{ts 1=$ufAccessURL}Use <a href='%1'>Drupal Access Control</a> to manage basic access to CiviCRM components and menu items. Use CiviCRM ACLs to control access to specific CiviCRM contact groups. You can also configure ACLs to grant or deny access to specific Events Profiles, and/or Custom Data Fields.{/ts}</p>
    {elseif $config->userFramework EQ 'Joomla'}
        <p>{ts 1=$ufAccessURL 2=$jAccessParams}Use <a href='%1' %2>Joomla Access Control</a> to manage basic access to CiviCRM components and menu items. Use CiviCRM ACLs to control access to specific CiviCRM contact groups. You can also configure ACLs to grant or deny access to specific Events, Profiles, and/or Custom Data Fields.{/ts}</p>
   {elseif $config->userFramework EQ 'WordPress'}
        <p>{ts 1=$ufAccessURL}Use <a href='%1'>WordPress Access Control</a> to manage basic access to CiviCRM components and menu items. Use CiviCRM ACLs to control access to specific CiviCRM contact groups. You can also configure ACLs to grant or deny access to specific Events, Profiles, and/or Custom Data Fields.{/ts}</p>
   {/if}
   <p>{ts 1=$config->userFramework}Note that %1 Access Control permissions take precedence over CiviCRM ACLs. If you wish to use CiviCRM ACLs, first disable the related permission in %1 Access control for a user role, and then gradually add ACLs to replace that permission for certain groups of contacts.{/ts}
</div>

    <table class="report">
        <tr>
    {if $config->userSystem->is_drupal EQ '1'}
            <td class="nowrap"><a href="{$ufAccessURL}" id="adminAccess">&raquo; {ts}Drupal Access Control{/ts}</a></td>
            <td>{ts}Grant access to CiviCRM components and other CiviCRM permissions.{/ts}</td>
    {elseif $config->userFramework EQ 'Joomla'}
            <td class="nowrap"><a href="{$ufAccessURL}" {$jAccessParams} id="adminAccess">&raquo; {ts}Joomla Access Control{/ts}</a></td>
            <td>{ts}Grant access to CiviCRM components and other CiviCRM permissions.{/ts}</td>
    {elseif $config->userFramework EQ 'WordPress'}
            <td class="nowrap"><a href="{$ufAccessURL}" id="adminAccess">&raquo; {ts}WordPress Access Control{/ts}</a></td>
            <td>{ts}Grant access to CiviCRM components and other CiviCRM permissions.{/ts}</td>
    {/if}
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
