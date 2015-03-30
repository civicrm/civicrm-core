<?xml version="1.0" encoding="utf-8"?>
<access component="com_civicrm">
  <section name="component">
            <action name="core.admin" title="Configure Joomla! ACL" description="Manage CiviCRM Joomla! ACL." />
            <action name="core.manage" title="See CiviCRM is installed" description="CiviCRM will be shown in list of installed components." />
{foreach from=$permissions item=perm key=name}
            <action name="civicrm.{$name}" title="{$perm.title}" description="{$perm.description}" />
{/foreach}
  </section>
</access>
