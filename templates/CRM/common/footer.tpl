{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{if call_user_func(array('CRM_Core_Permission','check'), 'access CiviCRM')}
  {include file="CRM/common/accesskeys.tpl"}
  {if !empty($contactId)}
    {include file="CRM/common/contactFooter.tpl"}
  {/if}

  <div class="crm-footer" id="civicrm-footer">
    {crmVersion assign=version}
    {ts 1=$version}Powered by the Progressive Technology Project &amp; built on CiviCRM %1.{/ts}
    {ts 1='http://www.gnu.org/licenses/agpl-3.0.html'}CiviCRM is openly available under the <a href='%1'>GNU AGPL License</a>.{/ts}<br/>
    <a href="mailto:support@progressivetech.org">{ts}Contact support{/ts}</a> &nbsp; &nbsp;
    <a href="https://www.youtube.com/playlist?list=PLE5F8CFD9F95DD6D0&amp;feature=plcp">{ts}See video tutorials{/ts}</a> &nbsp; &nbsp;
    <a href="http://book.civicrm.org/user/">{ts}View online documentation{/ts}</a> &nbsp; &nbsp;
    <a href="https://ptp.ourpowerbase.net/GetPowerBase">{ts}Get PowerBase{/ts}</a> &nbsp; &nbsp;
  </div> 
  {include file="CRM/common/notifications.tpl"}
{/if}
