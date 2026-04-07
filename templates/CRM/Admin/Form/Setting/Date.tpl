{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {capture assign=crmURL}{crmURL p='civicrm/admin/setting/preferences/date' q='action=reset=1'}{/capture}
    {ts 1=$crmURL}Use this screen to configure default formats for date display and date input fields throughout your site. Settings use standard POSIX specifiers. New installations are preconfigured with standard United States formats. You can override this default setting and define the range of allowed dates for specific field types at <a href="%1">Administer > Customize Data and Screens > Date Preferences</a>{/ts} {help id='date-format'}
</div>
{include file="CRM/Admin/Form/Generic.tpl"}
