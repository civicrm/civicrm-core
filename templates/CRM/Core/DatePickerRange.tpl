{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{*this is included inside a table row*}
{assign var=relativeName   value=$fieldName|cat:"_relative"}
{assign var='from' value=$from|default:'_low'}
{assign var='to' value=$to|default:'_high'}

  {$form.$relativeName.label}<br />
  {$form.$relativeName.html}<br />
  <span class="crm-absolute-date-range">
    <span class="crm-absolute-date-from">
      {assign var=fromName value=$fieldName|cat:$from}
      {$form.$fromName.label}
      {$form.$fromName.html}
    </span>
    <span class="crm-absolute-date-to">
      {assign var=toName   value=$fieldName|cat:$to}
      {$form.$toName.label}
      {$form.$toName.html}
    </span>
  </span>
  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        $("#{/literal}{$relativeName}{literal}").change(function() {
          var n = cj(this).parent().parent();
          if ($(this).val() == "0") {
            $(".crm-absolute-date-range", n).show();
          } else {
            $(".crm-absolute-date-range", n).hide();
            $(':text', n).val('');
          }
        }).change();
      });
    </script>
  {/literal}

