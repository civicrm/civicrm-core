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

{*
  Usage: CRM_Core_Region::instance('...')->add(array(
  'template' => 'CRM/common/LogButton.tpl',
  'instance_id' => CRM_Report_Utils_Report::getInstanceIDForValue('logging/contact/summary'),
  'css_class' => 'hrqual-revision-link',
  'table_name' => 'my_table',
  'contact_id' => 123,
  ));

  Note: This file is used by CivHR
*}

<a class="css_right {$snippet.css_class}" href="#" title="{ts}View Revisions{/ts}">View Revisions</a>

{literal}
<script type="text/javascript">
(function($, CRM) {
  if ($(".{/literal}{$snippet.css_class}{literal}").length) {
    $(".{/literal}{$snippet.css_class}{literal}").crmRevisionLink({
      contactId: {/literal}{$snippet.contact_id}{literal},
      tableName: "{/literal}{$snippet.table_name}{literal}",
      reportId: {/literal}{$snippet.instance_id}{literal}
    });
  }
})(cj, CRM);
</script>
{/literal}
