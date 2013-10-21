<?php

namespace Civi\CCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity
 *
 * @ORM\Table(name="civicrm_case_activity", indexes={@ORM\Index(name="UI_case_activity_id", columns={"case_id", "activity_id"}), @ORM\Index(name="FK_civicrm_case_activity_activity_id", columns={"activity_id"}), @ORM\Index(name="IDX_F73AAFE1CF10D4F5", columns={"case_id"})})
 * @ORM\Entity
 */
class Activity extends \Civi\Core\Entity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Civi\CCase\CCase
     *
     * @ORM\ManyToOne(targetEntity="Civi\CCase\CCase")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="case_id", referencedColumnName="id")
     * })
     */
    private $case;

    /**
     * @var \Civi\Activity\Activity
     *
     * @ORM\ManyToOne(targetEntity="Civi\Activity\Activity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     * })
     */
    private $activity;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set case
     *
     * @param \Civi\CCase\CCase $case
     * @return Activity
     */
    public function setCase(\Civi\CCase\CCase $case = null)
    {
        $this->case = $case;

        return $this;
    }

    /**
     * Get case
     *
     * @return \Civi\CCase\CCase 
     */
    public function getCase()
    {
        return $this->case;
    }

    /**
     * Set activity
     *
     * @param \Civi\Activity\Activity $activity
     * @return Activity
     */
    public function setActivity(\Civi\Activity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Civi\Activity\Activity 
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
