{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
