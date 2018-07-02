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
<?xml version="1.0" encoding="UTF-8"?>
<Case>
  <Client>{$case.clientName}</Client>
  <CaseType>{$case.caseType}</CaseType>
  <CaseSubject>{$case.subject|escape}</CaseSubject>
  <CaseStatus>{$case.status}</CaseStatus>
  <CaseOpen>{$case.start_date}</CaseOpen>
  <CaseClose>{$case.end_date}</CaseClose>
  <ActivitySet>
    <Label>{$activitySet.label}</Label>
    <IncludeActivities>{$includeActivities}</IncludeActivities>
    <Redact>{$isRedact}</Redact>
{foreach from=$activities item=activity}
    <Activity>
       <EditURL>{$activity.editURL}</EditURL>
       <Fields>
{foreach from=$activity.fields item=field}
          <Field>
            <Label>{$field.label|escape}</Label>
{if $field.category}
            <Category>{$field.category|escape}</Category>
{/if}
            <Value>{$field.value|escape}</Value>
            <Type>{$field.type}</Type>
          </Field>
{/foreach}
{if $activity.customGroups}
         <CustomGroups>
{foreach from=$activity.customGroups key=customGroupName item=customGroup}
            <CustomGroup>
               <GroupName>{$customGroupName|escape}</GroupName>
{foreach from=$customGroup item=field}
                  <Field>
                    <Label>{$field.label|escape}</Label>
                    <Value>{$field.value|escape}</Value>
                    <Type>{$field.type}</Type>
                  </Field>
{/foreach}
            </CustomGroup>
{/foreach}
         </CustomGroups>
{/if}
       </Fields>
    </Activity>
{/foreach}
  </ActivitySet>
</Case>

