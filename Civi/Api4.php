<?php
namespace Civi;

class Api4 {

  /**
   * @param string $sql
   *   A SQL expression, which will be evaluated via APIv4.
   *
   *   Ex: "SELECT id, display_name FROM Contact"
   *   Ex: "SYS SELECT id, display_name FROM Contact"
   *
   *   By default, the query is executed with permissions of the current logged-in user.
   *   The "SYS" prefix specifies
   * @param array|NULL $vars
   *   A list of values to interpolate
   * @return \Civi\Api4\Generic\AbstractAction
   * @throws \CRM_Core_Exception
   */
  public static function sql($sql, $vars = NULL) {
    list($entity, $action, $params) = self::parseSql($sql, $vars);
    // DEBUG: print_r(['entity'=> $entity, 'action' => $action, 'params' => $params]);
    require_once 'api/api.php';
    return \Civi\API\Request::create($entity, $action, $params);
  }

  /**
   * @param $sql
   * @param $vars
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function parseSql($sql, $vars) {
    // FIXME: Consider a parser cache. But take care about $vars...

    if (!isset(\Civi::$statics[__CLASS__]['parser'])) {
      \Civi::$statics[__CLASS__]['parser'] = new \PHPSQL\Parser();
    }

    $assert = function ($bool, $msg = '') use ($sql) {
      if (!$bool) {
        throw new \CRM_Core_Exception("Failed to parse APIv4-SQL: $msg\n($sql)");
      }
    };

    $assert($vars === NULL, 'FIXME: Interpolation');

    $entity = $action = NULL;
    $params = ['version' => 4];

    if (substr($sql, 0, 4) === 'SYS ') {
      $params['checkPermissions'] = FALSE;
      $sql = substr($sql, 4);
    }

    $p = \Civi::$statics[__CLASS__]['parser'];
    $expr = $p->parse($sql);
    // DEBUG: print_r($expr);

    switch (TRUE) {

      case isset($expr['SELECT']):
        $assert(count($expr['FROM'] === 1), 'FROM clause should have only specify one entity');
        $assert(count($expr['SELECT'] >= 1), 'SELECT clause shoudl have at least one value');
        $entity = $expr['FROM'][0]['table'];
        $action = 'get';

        foreach ($expr['SELECT'] as $select) {
          $assert($select['alias'] === FALSE, "FIXME: Map column aliases");
          $params['select'][] = $select['base_expr'];
        }

        if (isset($expr['WHERE'])) {
          $where = $expr['WHERE'];
          $assert(count($where) === 3 && $where[0]['expr_type'] === 'colref' && $where[1]['expr_type'] === 'operator' && $where[2]['expr_type'] === 'const',
            'FIXME: Map more advanced WHERE expressions');
          $params['where'][] = [
            $where[0]['base_expr'],
            $where[1]['base_expr'],
            $where[2]['base_expr'],
          ];
        }

        if (!empty($expr['LIMIT']['rowcount'])) {
          $params['limit'] = $expr['LIMIT']['rowcount'];
        }
        if (!empty($expr['LIMIT']['offset'])) {
          $params['offset'] = $expr['LIMIT']['offset'];
        }

        foreach ($expr['ORDER'] ?? [] as $orderBy) {
          $assert($orderBy['expr_type'] === 'colref', 'ORDER BY only supports basic columns');
          $params['orderBy'][$orderBy['base_expr']] = $orderBy['direction'];
        }

        break;

      case isset($expr['INSERT']):
        $assert(0, 'FIXME: INSERT');
        $assert(count($expr['INSERT']) === 1, 'INSERT clause should have one entity');
        $entity = $expr['INSERT'][0]['table'];
        $action = 'create';
        break;

      case isset($expr['UPDATE']):
        $assert(0, 'FIXME: UPDATE');
        $assert(count($expr['SET']) >= 1, 'UPDATE clause should have at least one SET');
        $action = 'update';
        break;

      default:
        throw new \CRM_Core_Exception("Unrecognized SQL verb");
    }

    return [$entity, $action, $params];
  }

}
