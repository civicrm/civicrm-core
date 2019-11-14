{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
