<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * WordReplacement
 *
 * @ORM\Table(name="civicrm_word_replacement", uniqueConstraints={@ORM\UniqueConstraint(name="UI_find", columns={"find_word"})}, indexes={@ORM\Index(name="FK_civicrm_word_replacement_domain_id", columns={"domain_id"})})
 * @ORM\Entity
 */
class WordReplacement extends \Civi\Core\Entity
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
     * @ORM\Column(name="find_word", type="string", length=255, nullable=true)
     */
    private $findWord;

    /**
     * @var string
     *
     * @ORM\Column(name="replace_word", type="string", length=255, nullable=true)
     */
    private $replaceWord;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="match_type", type="string", nullable=true)
     */
    private $matchType = 'wildcardMatch';

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;



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
     * Set findWord
     *
     * @param string $findWord
     * @return WordReplacement
     */
    public function setFindWord($findWord)
    {
        $this->findWord = $findWord;

        return $this;
    }

    /**
     * Get findWord
     *
     * @return string 
     */
    public function getFindWord()
    {
        return $this->findWord;
    }

    /**
     * Set replaceWord
     *
     * @param string $replaceWord
     * @return WordReplacement
     */
    public function setReplaceWord($replaceWord)
    {
        $this->replaceWord = $replaceWord;

        return $this;
    }

    /**
     * Get replaceWord
     *
     * @return string 
     */
    public function getReplaceWord()
    {
        return $this->replaceWord;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return WordReplacement
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set matchType
     *
     * @param string $matchType
     * @return WordReplacement
     */
    public function setMatchType($matchType)
    {
        $this->matchType = $matchType;

        return $this;
    }

    /**
     * Get matchType
     *
     * @return string 
     */
    public function getMatchType()
    {
        return $this->matchType;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return WordReplacement
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
