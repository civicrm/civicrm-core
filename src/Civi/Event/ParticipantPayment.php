<?php

namespace Civi\Event;

use Doctrine\ORM\Mapping as ORM;

/**
 * ParticipantPayment
 *
 * @ORM\Table(name="civicrm_participant_payment", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contribution_participant", columns={"contribution_id", "participant_id"})}, indexes={@ORM\Index(name="FK_civicrm_participant_payment_participant_id", columns={"participant_id"}), @ORM\Index(name="IDX_6BA1C9FFE5E5FBD", columns={"contribution_id"})})
 * @ORM\Entity
 */
class ParticipantPayment extends \Civi\Core\Entity
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
     * @var \Civi\Event\Participant
     *
     * @ORM\ManyToOne(targetEntity="Civi\Event\Participant")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="participant_id", referencedColumnName="id")
     * })
     */
    private $participant;

    /**
     * @var \Civi\Contribute\Contribution
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Contribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_id", referencedColumnName="id")
     * })
     */
    private $contribution;



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
     * Set participant
     *
     * @param \Civi\Event\Participant $participant
     * @return ParticipantPayment
     */
    public function setParticipant(\Civi\Event\Participant $participant = null)
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * Get participant
     *
     * @return \Civi\Event\Participant 
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return ParticipantPayment
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
