{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="crm-block crm-form-block crm-config-backend-form-block">
<div class="help">
    <p>
    {ts}When migrating a site to a new server, the paths and URLs of your CiviCRM installation may change. {/ts}
    </p>
    <p>
    {capture assign="pathsURL"}{crmURL p="civicrm/admin/setting/path" q="reset=1"}{/capture}
    {capture assign="urlsURL"}{crmURL p="civicrm/admin/setting/url" q="reset=1"}{/capture}
    {ts 1=$pathsURL 2=$urlsURL}The old paths and URLs may be retained in some database records. Use this form to clear caches or to reset paths to their defaults. If you need further customizations, then update the <a href="%1">Directories</a> and <a href="%2">Resource URLs</a>.{/ts}
    </p>
</div>
        <div>
          <span class="crm-button crm-i-button">
            <i class="crm-i fa-undo"></i>
            {$form._qf_UpdateConfigBackend_next_cleanup.html}
          </span>
          <span class="crm-button crm-i-button">
            <i class="crm-i fa-terminal"></i>
            {$form._qf_UpdateConfigBackend_next_resetpaths.html}
          </span>
        </div>
        <div>{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
