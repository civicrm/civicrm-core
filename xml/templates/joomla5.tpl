<?xml version="1.0" encoding="utf-8"?>
<!--
    Generated from {$smarty.template}
-->
<extension method="upgrade" type="component" version="2.5">
  <name>CiviCRM</name>
  <creationDate>{$creationDate}</creationDate>
  <copyright>(C) CiviCRM LLC</copyright>
  <author>CiviCRM LLC</author>
  <authorEmail>info@civicrm.org</authorEmail>
  <authorUrl>civicrm.org</authorUrl>
  <version>{$CiviCRMVersion}</version>
  <description>CiviCRM</description>
  <files folder="site">
    <filename>civicrm.php</filename>
    <filename>civicrm.html.php</filename>
    <folder>views</folder>
    <folder>elements</folder>
  </files>
  <install>
    <queries>
    </queries>
  </install>
  <uninstall>
      <queries>
      </queries>
  </uninstall>
  <scriptfile>script.civicrm.php</scriptfile>
  <administration>
    <menu task="civicrm/dashboard&amp;reset=1">COM_CIVICRM_MENU</menu>
    <files folder="admin">
      <filename>admin.civicrm.php</filename>
      <filename>civicrm.php</filename>
      <filename>configure.php</filename>
      <filename>access.xml</filename>
      <filename>config.xml</filename>
{if $pkgType eq 'alt'}
      <folder>civicrm</folder>
{else}
      <filename>civicrm.zip</filename>
{/if}
      <folder>helpers</folder>
    </files>
    <languages folder="admin">
      <language tag="en-GB">language/en-GB/en-GB.com_civicrm.ini</language>
      <language tag="en-GB">language/en-GB/en-GB.com_civicrm.sys.ini</language>
    </languages>
  </administration>
  <plugins>
      <plugin folder="admin/plugins" plugin="civicrm" name="CiviCRM User Management" group="user" />
      <plugin folder="admin/plugins" plugin="civicrmsys" name="CiviCRM System Listener" group="system" />
      <plugin folder="admin/plugins" plugin="civicrmicon" name="CiviCRM QuickIcon" group="quickicon" />
  </plugins>
</extension>
