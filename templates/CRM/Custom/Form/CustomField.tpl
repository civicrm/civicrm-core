{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this file is deprecated & it's recommended to define formElement in the calling function. As of 5.1
 it is no longer used by core & is only retained in case it is used by extensions *}

{assign var="element_name" value=$element.element_name}
{include file="CRM/Custom/Form/Edit/CustomField.tpl" formElement=$form.$element_name}
