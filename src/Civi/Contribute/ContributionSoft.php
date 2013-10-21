<?php

namespace Civi\Contribute;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContributionSoft
 *
 * @ORM\Table(name="civicrm_contribution_soft", indexes={@ORM\Index(name="index_id", columns={"pcp_id"}), @ORM\Index(name="FK_civicrm_contribution_soft_contribution_id", columns={"contribution_id"}), @ORM\Index(name="FK_civicrm_contribution_soft_contact_id", columns={"contact_id"})})
 * @ORM\Entity
 */
class ContributionSoft extends \Civi\Core\Entity
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
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=20, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pcp_display_in_roll", type="boolean", nullable=true)
     */
    private $pcpDisplayInRoll = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="pcp_roll_nickname", type="string", length=255, nullable=true)
     */
    private $pcpRollNickname;

    /**
     * @var string
     *
     * @ORM\Column(name="pcp_personal_note", type="string", length=255, nullable=true)
     */
    private $pcpPersonalNote;

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
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

    /**
     * @var \Civi\PCP\PCP
     *
     * @ORM\ManyToOne(targetEntity="Civi\PCP\PCP")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pcp_id", referencedColumnName="id")
     * })
     */
    private $pcp;



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
     * Set amount
     *
     * @param string $amount
     * @return ContributionSoft
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return ContributionSoft
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set pcpDisplayInRoll
     *
     * @param boolean $pcpDisplayInRoll
     * @return ContributionSoft
     */
    public function setPcpDisplayInRoll($pcpDisplayInRoll)
    {
        $this->pcpDisplayInRoll = $pcpDisplayInRoll;

        return $this;
    }

    /**
     * Get pcpDisplayInRoll
     *
     * @return boolean 
     */
    public function getPcpDisplayInRoll()
    {
        return $this->pcpDisplayInRoll;
    }

    /**
     * Set pcpRollNickname
     *
     * @param string $pcpRollNickname
     * @return ContributionSoft
     */
    public function setPcpRollNickname($pcpRollNickname)
    {
        $this->pcpRollNickname = $pcpRollNickname;

        return $this;
    }

    /**
     * Get pcpRollNickname
     *
     * @return string 
     */
    public function getPcpRollNickname()
    {
        return $this->pcpRollNickname;
    }

    /**
     * Set pcpPersonalNote
     *
     * @param string $pcpPersonalNote
     * @return ContributionSoft
     */
    public function setPcpPersonalNote($pcpPersonalNote)
    {
        $this->pcpPersonalNote = $pcpPersonalNote;

        return $this;
    }

    /**
     * Get pcpPersonalNote
     *
     * @return string 
     */
    public function getPcpPersonalNote()
    {
        return $this->pcpPersonalNote;
    }

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return ContributionSoft
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

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return ContributionSoft
     */
    public function setContact(\Civi\Contact\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set pcp
     *
     * @param \Civi\PCP\PCP $pcp
     * @return ContributionSoft
     */
    public function setPcp(\Civi\PCP\PCP $pcp = null)
    {
        $this->pcp = $pcp;

        return $this;
    }

    /**
     * Get pcp
     *
     * @return \Civi\PCP\PCP 
     */
    public function getPcp()
    {
        return $this->pcp;
    }
}
