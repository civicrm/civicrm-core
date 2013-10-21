<?php

namespace Civi\Dedupe;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rule
 *
 * @ORM\Table(name="civicrm_dedupe_rule", indexes={@ORM\Index(name="FK_civicrm_dedupe_rule_dedupe_rule_group_id", columns={"dedupe_rule_group_id"})})
 * @ORM\Entity
 */
class Rule extends \Civi\Core\Entity
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
     * @ORM\Column(name="rule_table", type="string", length=64, nullable=false)
     */
    private $ruleTable;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_field", type="string", length=64, nullable=false)
     */
    private $ruleField;

    /**
     * @var integer
     *
     * @ORM\Column(name="rule_length", type="integer", nullable=true)
     */
    private $ruleLength;

    /**
     * @var integer
     *
     * @ORM\Column(name="rule_weight", type="integer", nullable=false)
     */
    private $ruleWeight;

    /**
     * @var \Civi\Dedupe\RuleGroup
     *
     * @ORM\ManyToOne(targetEntity="Civi\Dedupe\RuleGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dedupe_rule_group_id", referencedColumnName="id")
     * })
     */
    private $dedupeRuleGroup;



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
     * Set ruleTable
     *
     * @param string $ruleTable
     * @return Rule
     */
    public function setRuleTable($ruleTable)
    {
        $this->ruleTable = $ruleTable;

        return $this;
    }

    /**
     * Get ruleTable
     *
     * @return string 
     */
    public function getRuleTable()
    {
        return $this->ruleTable;
    }

    /**
     * Set ruleField
     *
     * @param string $ruleField
     * @return Rule
     */
    public function setRuleField($ruleField)
    {
        $this->ruleField = $ruleField;

        return $this;
    }

    /**
     * Get ruleField
     *
     * @return string 
     */
    public function getRuleField()
    {
        return $this->ruleField;
    }

    /**
     * Set ruleLength
     *
     * @param integer $ruleLength
     * @return Rule
     */
    public function setRuleLength($ruleLength)
    {
        $this->ruleLength = $ruleLength;

        return $this;
    }

    /**
     * Get ruleLength
     *
     * @return integer 
     */
    public function getRuleLength()
    {
        return $this->ruleLength;
    }

    /**
     * Set ruleWeight
     *
     * @param integer $ruleWeight
     * @return Rule
     */
    public function setRuleWeight($ruleWeight)
    {
        $this->ruleWeight = $ruleWeight;

        return $this;
    }

    /**
     * Get ruleWeight
     *
     * @return integer 
     */
    public function getRuleWeight()
    {
        return $this->ruleWeight;
    }

    /**
     * Set dedupeRuleGroup
     *
     * @param \Civi\Dedupe\RuleGroup $dedupeRuleGroup
     * @return Rule
     */
    public function setDedupeRuleGroup(\Civi\Dedupe\RuleGroup $dedupeRuleGroup = null)
    {
        $this->dedupeRuleGroup = $dedupeRuleGroup;

        return $this;
    }

    /**
     * Get dedupeRuleGroup
     *
     * @return \Civi\Dedupe\RuleGroup 
     */
    public function getDedupeRuleGroup()
    {
        return $this->dedupeRuleGroup;
    }
}
