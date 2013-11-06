<?php

namespace Civi\Price;

use Doctrine\ORM\Mapping as ORM;

/**
 * SetContributionEntity
 *
 * @ORM\Entity
 *
 */
class SetContributionEntity extends SetEntity
{
  /**
   * @ORM\ManyToOne(targetEntity="Civi\Contribute\Contribution")
   * @ORM\JoinColumns({
   *   @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
   * })
   */
  private $contribution;

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return SetContributionEntity
     */
    public function setContribution(\Civi\Contribute\Contribution $contribution = null)
    {
        $this->contribution = $contribution;

        return $this;
    }

    /**
     * Get contribution
     *
     * @return \Civi\Contribute\Contribution 
     */
    public function getContribution()
    {
        return $this->contribution;
    }
}
