{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="crm-block crm-form-block crm-import_sql-form-block">
  <h3>{ts}SQL Import{/ts}</h3>
    <table class="form-layout-compressed">
      <tr class ="crm-import_sql-form-block-sqlQuery">
         <td class="label">{$form.sqlQuery.label}</td>
         <td>{$form.sqlQuery.html}<br />
         <span class="description">{ts}SQL Query must be a SELECT query that returns one or more rows of data to be imported. Specify the database name(s) AND table name(s) in the query (e.g. "SELECT * FROM my_database.my_table WHERE date_entered BETWEEN '1999-01-01' AND '2000-07-31'").{/ts}</span></td>
    </tr>
   </table>
 </div>