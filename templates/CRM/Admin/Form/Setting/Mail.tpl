{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
{capture assign=docLink}{docURL page="CiviMail Mailer Settings" text="CiviMail Mailer Settings and Optimization" resource="wiki"}{/capture}
<div class="crm-block crm-form-block crm-mail-form-block">
  <div class="help">
    {ts 1=$docLink}These settings are used to configure mailer properties for the optional CiviMail component and may allow you to significantly optimize performance. Please read the %1 documentation, and make sure you understand it before modifying default values. (These settings are NOT used for the built-in 'Email - send now' feature).{/ts}
  </div>
  {include file='CRM/Admin/Form/Setting/SettingForm.tpl'}
</div>
