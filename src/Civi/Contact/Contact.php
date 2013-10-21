<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 *
 * @ORM\Table(name="civicrm_contact", uniqueConstraints={@ORM\UniqueConstraint(name="UI_external_identifier", columns={"external_identifier"})}, indexes={@ORM\Index(name="index_contact_type", columns={"contact_type"}), @ORM\Index(name="index_contact_sub_type", columns={"contact_sub_type"}), @ORM\Index(name="index_sort_name", columns={"sort_name"}), @ORM\Index(name="index_preferred_communication_method", columns={"preferred_communication_method"}), @ORM\Index(name="index_hash", columns={"hash"}), @ORM\Index(name="index_api_key", columns={"api_key"}), @ORM\Index(name="index_first_name", columns={"first_name"}), @ORM\Index(name="index_last_name", columns={"last_name"}), @ORM\Index(name="UI_prefix", columns={"prefix_id"}), @ORM\Index(name="UI_suffix", columns={"suffix_id"}), @ORM\Index(name="index_communication_style_id", columns={"communication_style_id"}), @ORM\Index(name="UI_gender", columns={"gender_id"}), @ORM\Index(name="index_household_name", columns={"household_name"}), @ORM\Index(name="index_organization_name", columns={"organization_name"}), @ORM\Index(name="index_is_deleted_sort_name", columns={"is_deleted", "sort_name", "id"}), @ORM\Index(name="FK_civicrm_contact_primary_contact_id", columns={"primary_contact_id"}), @ORM\Index(name="FK_civicrm_contact_employer_id", columns={"employer_id"})})
 * @ORM\Entity
 */
class Contact extends \Civi\Core\Entity
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
     * @ORM\Column(name="contact_type", type="string", length=64, nullable=true)
     */
    private $contactType;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_sub_type", type="string", length=255, nullable=true)
     */
    private $contactSubType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_not_email", type="boolean", nullable=true)
     */
    private $doNotEmail = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_not_phone", type="boolean", nullable=true)
     */
    private $doNotPhone = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_not_mail", type="boolean", nullable=true)
     */
    private $doNotMail = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_not_sms", type="boolean", nullable=true)
     */
    private $doNotSms = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="do_not_trade", type="boolean", nullable=true)
     */
    private $doNotTrade = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_opt_out", type="boolean", nullable=false)
     */
    private $isOptOut = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="legal_identifier", type="string", length=32, nullable=true)
     */
    private $legalIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="external_identifier", type="string", length=32, nullable=true)
     */
    private $externalIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_name", type="string", length=128, nullable=true)
     */
    private $sortName;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=128, nullable=true)
     */
    private $displayName;

    /**
     * @var string
     *
     * @ORM\Column(name="nick_name", type="string", length=128, nullable=true)
     */
    private $nickName;

    /**
     * @var string
     *
     * @ORM\Column(name="legal_name", type="string", length=128, nullable=true)
     */
    private $legalName;

    /**
     * @var string
     *
     * @ORM\Column(name="image_URL", type="string", length=255, nullable=true)
     */
    private $imageUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="preferred_communication_method", type="string", length=255, nullable=true)
     */
    private $preferredCommunicationMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="preferred_language", type="string", length=5, nullable=true)
     */
    private $preferredLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="preferred_mail_format", type="string", nullable=true)
     */
    private $preferredMailFormat = 'Both';

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=32, nullable=true)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="string", length=32, nullable=true)
     */
    private $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=64, nullable=true)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="middle_name", type="string", length=64, nullable=true)
     */
    private $middleName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=64, nullable=true)
     */
    private $lastName;

    /**
     * @var integer
     *
     * @ORM\Column(name="prefix_id", type="integer", nullable=true)
     */
    private $prefixId;

    /**
     * @var integer
     *
     * @ORM\Column(name="suffix_id", type="integer", nullable=true)
     */
    private $suffixId;

    /**
     * @var string
     *
     * @ORM\Column(name="formal_title", type="string", length=64, nullable=true)
     */
    private $formalTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="communication_style_id", type="integer", nullable=true)
     */
    private $communicationStyleId;

    /**
     * @var integer
     *
     * @ORM\Column(name="email_greeting_id", type="integer", nullable=true)
     */
    private $emailGreetingId;

    /**
     * @var string
     *
     * @ORM\Column(name="email_greeting_custom", type="string", length=128, nullable=true)
     */
    private $emailGreetingCustom;

    /**
     * @var string
     *
     * @ORM\Column(name="email_greeting_display", type="string", length=255, nullable=true)
     */
    private $emailGreetingDisplay;

    /**
     * @var integer
     *
     * @ORM\Column(name="postal_greeting_id", type="integer", nullable=true)
     */
    private $postalGreetingId;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_greeting_custom", type="string", length=128, nullable=true)
     */
    private $postalGreetingCustom;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_greeting_display", type="string", length=255, nullable=true)
     */
    private $postalGreetingDisplay;

    /**
     * @var integer
     *
     * @ORM\Column(name="addressee_id", type="integer", nullable=true)
     */
    private $addresseeId;

    /**
     * @var string
     *
     * @ORM\Column(name="addressee_custom", type="string", length=128, nullable=true)
     */
    private $addresseeCustom;

    /**
     * @var string
     *
     * @ORM\Column(name="addressee_display", type="string", length=255, nullable=true)
     */
    private $addresseeDisplay;

    /**
     * @var string
     *
     * @ORM\Column(name="job_title", type="string", length=255, nullable=true)
     */
    private $jobTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="gender_id", type="integer", nullable=true)
     */
    private $genderId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birth_date", type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deceased", type="boolean", nullable=true)
     */
    private $isDeceased = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deceased_date", type="date", nullable=true)
     */
    private $deceasedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="household_name", type="string", length=128, nullable=true)
     */
    private $householdName;

    /**
     * @var string
     *
     * @ORM\Column(name="organization_name", type="string", length=128, nullable=true)
     */
    private $organizationName;

    /**
     * @var string
     *
     * @ORM\Column(name="sic_code", type="string", length=8, nullable=true)
     */
    private $sicCode;

    /**
     * @var string
     *
     * @ORM\Column(name="user_unique_id", type="string", length=255, nullable=true)
     */
    private $userUniqueId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    private $isDeleted = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_date", type="datetime", nullable=true)
     */
    private $modifiedDate;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="primary_contact_id", referencedColumnName="id")
     * })
     */
    private $primaryContact;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="employer_id", referencedColumnName="id")
     * })
     */
    private $employer;



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
     * Set contactType
     *
     * @param string $contactType
     * @return Contact
     */
    public function setContactType($contactType)
    {
        $this->contactType = $contactType;

        return $this;
    }

    /**
     * Get contactType
     *
     * @return string 
     */
    public function getContactType()
    {
        return $this->contactType;
    }

    /**
     * Set contactSubType
     *
     * @param string $contactSubType
     * @return Contact
     */
    public function setContactSubType($contactSubType)
    {
        $this->contactSubType = $contactSubType;

        return $this;
    }

    /**
     * Get contactSubType
     *
     * @return string 
     */
    public function getContactSubType()
    {
        return $this->contactSubType;
    }

    /**
     * Set doNotEmail
     *
     * @param boolean $doNotEmail
     * @return Contact
     */
    public function setDoNotEmail($doNotEmail)
    {
        $this->doNotEmail = $doNotEmail;

        return $this;
    }

    /**
     * Get doNotEmail
     *
     * @return boolean 
     */
    public function getDoNotEmail()
    {
        return $this->doNotEmail;
    }

    /**
     * Set doNotPhone
     *
     * @param boolean $doNotPhone
     * @return Contact
     */
    public function setDoNotPhone($doNotPhone)
    {
        $this->doNotPhone = $doNotPhone;

        return $this;
    }

    /**
     * Get doNotPhone
     *
     * @return boolean 
     */
    public function getDoNotPhone()
    {
        return $this->doNotPhone;
    }

    /**
     * Set doNotMail
     *
     * @param boolean $doNotMail
     * @return Contact
     */
    public function setDoNotMail($doNotMail)
    {
        $this->doNotMail = $doNotMail;

        return $this;
    }

    /**
     * Get doNotMail
     *
     * @return boolean 
     */
    public function getDoNotMail()
    {
        return $this->doNotMail;
    }

    /**
     * Set doNotSms
     *
     * @param boolean $doNotSms
     * @return Contact
     */
    public function setDoNotSms($doNotSms)
    {
        $this->doNotSms = $doNotSms;

        return $this;
    }

    /**
     * Get doNotSms
     *
     * @return boolean 
     */
    public function getDoNotSms()
    {
        return $this->doNotSms;
    }

    /**
     * Set doNotTrade
     *
     * @param boolean $doNotTrade
     * @return Contact
     */
    public function setDoNotTrade($doNotTrade)
    {
        $this->doNotTrade = $doNotTrade;

        return $this;
    }

    /**
     * Get doNotTrade
     *
     * @return boolean 
     */
    public function getDoNotTrade()
    {
        return $this->doNotTrade;
    }

    /**
     * Set isOptOut
     *
     * @param boolean $isOptOut
     * @return Contact
     */
    public function setIsOptOut($isOptOut)
    {
        $this->isOptOut = $isOptOut;

        return $this;
    }

    /**
     * Get isOptOut
     *
     * @return boolean 
     */
    public function getIsOptOut()
    {
        return $this->isOptOut;
    }

    /**
     * Set legalIdentifier
     *
     * @param string $legalIdentifier
     * @return Contact
     */
    public function setLegalIdentifier($legalIdentifier)
    {
        $this->legalIdentifier = $legalIdentifier;

        return $this;
    }

    /**
     * Get legalIdentifier
     *
     * @return string 
     */
    public function getLegalIdentifier()
    {
        return $this->legalIdentifier;
    }

    /**
     * Set externalIdentifier
     *
     * @param string $externalIdentifier
     * @return Contact
     */
    public function setExternalIdentifier($externalIdentifier)
    {
        $this->externalIdentifier = $externalIdentifier;

        return $this;
    }

    /**
     * Get externalIdentifier
     *
     * @return string 
     */
    public function getExternalIdentifier()
    {
        return $this->externalIdentifier;
    }

    /**
     * Set sortName
     *
     * @param string $sortName
     * @return Contact
     */
    public function setSortName($sortName)
    {
        $this->sortName = $sortName;

        return $this;
    }

    /**
     * Get sortName
     *
     * @return string 
     */
    public function getSortName()
    {
        return $this->sortName;
    }

    /**
     * Set displayName
     *
     * @param string $displayName
     * @return Contact
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string 
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set nickName
     *
     * @param string $nickName
     * @return Contact
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;

        return $this;
    }

    /**
     * Get nickName
     *
     * @return string 
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * Set legalName
     *
     * @param string $legalName
     * @return Contact
     */
    public function setLegalName($legalName)
    {
        $this->legalName = $legalName;

        return $this;
    }

    /**
     * Get legalName
     *
     * @return string 
     */
    public function getLegalName()
    {
        return $this->legalName;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     * @return Contact
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string 
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Set preferredCommunicationMethod
     *
     * @param string $preferredCommunicationMethod
     * @return Contact
     */
    public function setPreferredCommunicationMethod($preferredCommunicationMethod)
    {
        $this->preferredCommunicationMethod = $preferredCommunicationMethod;

        return $this;
    }

    /**
     * Get preferredCommunicationMethod
     *
     * @return string 
     */
    public function getPreferredCommunicationMethod()
    {
        return $this->preferredCommunicationMethod;
    }

    /**
     * Set preferredLanguage
     *
     * @param string $preferredLanguage
     * @return Contact
     */
    public function setPreferredLanguage($preferredLanguage)
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    /**
     * Get preferredLanguage
     *
     * @return string 
     */
    public function getPreferredLanguage()
    {
        return $this->preferredLanguage;
    }

    /**
     * Set preferredMailFormat
     *
     * @param string $preferredMailFormat
     * @return Contact
     */
    public function setPreferredMailFormat($preferredMailFormat)
    {
        $this->preferredMailFormat = $preferredMailFormat;

        return $this;
    }

    /**
     * Get preferredMailFormat
     *
     * @return string 
     */
    public function getPreferredMailFormat()
    {
        return $this->preferredMailFormat;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return Contact
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set apiKey
     *
     * @param string $apiKey
     * @return Contact
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string 
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Contact
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set middleName
     *
     * @param string $middleName
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * Get middleName
     *
     * @return string 
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set prefixId
     *
     * @param integer $prefixId
     * @return Contact
     */
    public function setPrefixId($prefixId)
    {
        $this->prefixId = $prefixId;

        return $this;
    }

    /**
     * Get prefixId
     *
     * @return integer 
     */
    public function getPrefixId()
    {
        return $this->prefixId;
    }

    /**
     * Set suffixId
     *
     * @param integer $suffixId
     * @return Contact
     */
    public function setSuffixId($suffixId)
    {
        $this->suffixId = $suffixId;

        return $this;
    }

    /**
     * Get suffixId
     *
     * @return integer 
     */
    public function getSuffixId()
    {
        return $this->suffixId;
    }

    /**
     * Set formalTitle
     *
     * @param string $formalTitle
     * @return Contact
     */
    public function setFormalTitle($formalTitle)
    {
        $this->formalTitle = $formalTitle;

        return $this;
    }

    /**
     * Get formalTitle
     *
     * @return string 
     */
    public function getFormalTitle()
    {
        return $this->formalTitle;
    }

    /**
     * Set communicationStyleId
     *
     * @param integer $communicationStyleId
     * @return Contact
     */
    public function setCommunicationStyleId($communicationStyleId)
    {
        $this->communicationStyleId = $communicationStyleId;

        return $this;
    }

    /**
     * Get communicationStyleId
     *
     * @return integer 
     */
    public function getCommunicationStyleId()
    {
        return $this->communicationStyleId;
    }

    /**
     * Set emailGreetingId
     *
     * @param integer $emailGreetingId
     * @return Contact
     */
    public function setEmailGreetingId($emailGreetingId)
    {
        $this->emailGreetingId = $emailGreetingId;

        return $this;
    }

    /**
     * Get emailGreetingId
     *
     * @return integer 
     */
    public function getEmailGreetingId()
    {
        return $this->emailGreetingId;
    }

    /**
     * Set emailGreetingCustom
     *
     * @param string $emailGreetingCustom
     * @return Contact
     */
    public function setEmailGreetingCustom($emailGreetingCustom)
    {
        $this->emailGreetingCustom = $emailGreetingCustom;

        return $this;
    }

    /**
     * Get emailGreetingCustom
     *
     * @return string 
     */
    public function getEmailGreetingCustom()
    {
        return $this->emailGreetingCustom;
    }

    /**
     * Set emailGreetingDisplay
     *
     * @param string $emailGreetingDisplay
     * @return Contact
     */
    public function setEmailGreetingDisplay($emailGreetingDisplay)
    {
        $this->emailGreetingDisplay = $emailGreetingDisplay;

        return $this;
    }

    /**
     * Get emailGreetingDisplay
     *
     * @return string 
     */
    public function getEmailGreetingDisplay()
    {
        return $this->emailGreetingDisplay;
    }

    /**
     * Set postalGreetingId
     *
     * @param integer $postalGreetingId
     * @return Contact
     */
    public function setPostalGreetingId($postalGreetingId)
    {
        $this->postalGreetingId = $postalGreetingId;

        return $this;
    }

    /**
     * Get postalGreetingId
     *
     * @return integer 
     */
    public function getPostalGreetingId()
    {
        return $this->postalGreetingId;
    }

    /**
     * Set postalGreetingCustom
     *
     * @param string $postalGreetingCustom
     * @return Contact
     */
    public function setPostalGreetingCustom($postalGreetingCustom)
    {
        $this->postalGreetingCustom = $postalGreetingCustom;

        return $this;
    }

    /**
     * Get postalGreetingCustom
     *
     * @return string 
     */
    public function getPostalGreetingCustom()
    {
        return $this->postalGreetingCustom;
    }

    /**
     * Set postalGreetingDisplay
     *
     * @param string $postalGreetingDisplay
     * @return Contact
     */
    public function setPostalGreetingDisplay($postalGreetingDisplay)
    {
        $this->postalGreetingDisplay = $postalGreetingDisplay;

        return $this;
    }

    /**
     * Get postalGreetingDisplay
     *
     * @return string 
     */
    public function getPostalGreetingDisplay()
    {
        return $this->postalGreetingDisplay;
    }

    /**
     * Set addresseeId
     *
     * @param integer $addresseeId
     * @return Contact
     */
    public function setAddresseeId($addresseeId)
    {
        $this->addresseeId = $addresseeId;

        return $this;
    }

    /**
     * Get addresseeId
     *
     * @return integer 
     */
    public function getAddresseeId()
    {
        return $this->addresseeId;
    }

    /**
     * Set addresseeCustom
     *
     * @param string $addresseeCustom
     * @return Contact
     */
    public function setAddresseeCustom($addresseeCustom)
    {
        $this->addresseeCustom = $addresseeCustom;

        return $this;
    }

    /**
     * Get addresseeCustom
     *
     * @return string 
     */
    public function getAddresseeCustom()
    {
        return $this->addresseeCustom;
    }

    /**
     * Set addresseeDisplay
     *
     * @param string $addresseeDisplay
     * @return Contact
     */
    public function setAddresseeDisplay($addresseeDisplay)
    {
        $this->addresseeDisplay = $addresseeDisplay;

        return $this;
    }

    /**
     * Get addresseeDisplay
     *
     * @return string 
     */
    public function getAddresseeDisplay()
    {
        return $this->addresseeDisplay;
    }

    /**
     * Set jobTitle
     *
     * @param string $jobTitle
     * @return Contact
     */
    public function setJobTitle($jobTitle)
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    /**
     * Get jobTitle
     *
     * @return string 
     */
    public function getJobTitle()
    {
        return $this->jobTitle;
    }

    /**
     * Set genderId
     *
     * @param integer $genderId
     * @return Contact
     */
    public function setGenderId($genderId)
    {
        $this->genderId = $genderId;

        return $this;
    }

    /**
     * Get genderId
     *
     * @return integer 
     */
    public function getGenderId()
    {
        return $this->genderId;
    }

    /**
     * Set birthDate
     *
     * @param \DateTime $birthDate
     * @return Contact
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Get birthDate
     *
     * @return \DateTime 
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Set isDeceased
     *
     * @param boolean $isDeceased
     * @return Contact
     */
    public function setIsDeceased($isDeceased)
    {
        $this->isDeceased = $isDeceased;

        return $this;
    }

    /**
     * Get isDeceased
     *
     * @return boolean 
     */
    public function getIsDeceased()
    {
        return $this->isDeceased;
    }

    /**
     * Set deceasedDate
     *
     * @param \DateTime $deceasedDate
     * @return Contact
     */
    public function setDeceasedDate($deceasedDate)
    {
        $this->deceasedDate = $deceasedDate;

        return $this;
    }

    /**
     * Get deceasedDate
     *
     * @return \DateTime 
     */
    public function getDeceasedDate()
    {
        return $this->deceasedDate;
    }

    /**
     * Set householdName
     *
     * @param string $householdName
     * @return Contact
     */
    public function setHouseholdName($householdName)
    {
        $this->householdName = $householdName;

        return $this;
    }

    /**
     * Get householdName
     *
     * @return string 
     */
    public function getHouseholdName()
    {
        return $this->householdName;
    }

    /**
     * Set organizationName
     *
     * @param string $organizationName
     * @return Contact
     */
    public function setOrganizationName($organizationName)
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    /**
     * Get organizationName
     *
     * @return string 
     */
    public function getOrganizationName()
    {
        return $this->organizationName;
    }

    /**
     * Set sicCode
     *
     * @param string $sicCode
     * @return Contact
     */
    public function setSicCode($sicCode)
    {
        $this->sicCode = $sicCode;

        return $this;
    }

    /**
     * Get sicCode
     *
     * @return string 
     */
    public function getSicCode()
    {
        return $this->sicCode;
    }

    /**
     * Set userUniqueId
     *
     * @param string $userUniqueId
     * @return Contact
     */
    public function setUserUniqueId($userUniqueId)
    {
        $this->userUniqueId = $userUniqueId;

        return $this;
    }

    /**
     * Get userUniqueId
     *
     * @return string 
     */
    public function getUserUniqueId()
    {
        return $this->userUniqueId;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return Contact
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Contact
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get createdDate
     *
     * @return \DateTime 
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return Contact
     */
    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * Get modifiedDate
     *
     * @return \DateTime 
     */
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    /**
     * Set primaryContact
     *
     * @param \Civi\Contact\Contact $primaryContact
     * @return Contact
     */
    public function setPrimaryContact(\Civi\Contact\Contact $primaryContact = null)
    {
        $this->primaryContact = $primaryContact;

        return $this;
    }

    /**
     * Get primaryContact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getPrimaryContact()
    {
        return $this->primaryContact;
    }

    /**
     * Set employer
     *
     * @param \Civi\Contact\Contact $employer
     * @return Contact
     */
    public function setEmployer(\Civi\Contact\Contact $employer = null)
    {
        $this->employer = $employer;

        return $this;
    }

    /**
     * Get employer
     *
     * @return \Civi\Contact\Contact 
     */
    public function getEmployer()
    {
        return $this->employer;
    }
}
