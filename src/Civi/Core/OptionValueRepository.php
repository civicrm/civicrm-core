<?php

namespace Civi\Core;

use Doctrine\ORM\EntityRepository;

class OptionValueRepository extends EntityRepository
{
  public function findOne($group_name, $value_name)
  {
    $dql = <<<EOS
      SELECT
        option_value
      FROM
        Civi\Core\OptionValue option_value
      JOIN
        option_value.optionGroup option_group
      WHERE
        option_group.name = ?1
      AND
        option_value.name = ?2
EOS;
    $entity_manager = $this->getEntityManager();
    $query = $entity_manager->createQuery($dql);
    $query->setParameter(1, $group_name);
    $query->setParameter(2, $value_name);
    return $query->getSingleResult();
  }
}
