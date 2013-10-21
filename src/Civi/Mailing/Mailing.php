<?php

namespace Civi\Mailing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mailing
 *
 * @ORM\Table(name="civicrm_mailing", indexes={@ORM\Index(name="FK_civicrm_mailing_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_mailing_header_id", columns={"header_id"}), @ORM\Index(name="FK_civicrm_mailing_footer_id", columns={"footer_id"}), @ORM\Index(name="FK_civicrm_mailing_reply_id", columns={"reply_id"}), @ORM\Index(name="FK_civicrm_mailing_unsubscribe_id", columns={"unsubscribe_id"}), @ORM\Index(name="FK_civicrm_mailing_optout_id", columns={"optout_id"}), @ORM\Index(name="FK_civicrm_mailing_msg_template_id", columns={"msg_template_id"}), @ORM\Index(name="FK_civicrm_mailing_created_id", columns={"created_id"}), @ORM\Index(name="FK_civicrm_mailing_scheduled_id", columns={"scheduled_id"}), @ORM\Index(name="FK_civicrm_mailing_approver_id", columns={"approver_id"}), @ORM\Index(name="FK_civicrm_mailing_campaign_id", columns={"campaign_id"}), @ORM\Index(name="FK_civicrm_mailing_sms_provider_id", columns={"sms_provider_id"})})
 * @ORM\Entity
 */
class Mailing extends \Civi\Core\Entity
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
     * @var integer
     *
     * @ORM\Column(name="resubscribe_id", type="integer", nullable=true)
     */
    private $resubscribeId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=128, nullable=true)
     */
    private $fromName;

    /**
     * @var string
     *
     * @ORM\Column(name="from_email", type="string", length=128, nullable=true)
     */
    private $fromEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="replyto_email", type="string", length=128, nullable=true)
     */
    private $replytoEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=128, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body_text", type="text", nullable=true)
     */
    private $bodyText;

    /**
     * @var string
     *
     * @ORM\Column(name="body_html", type="text", nullable=true)
     */
    private $bodyHtml;

    /**
     * @var boolean
     *
     * @ORM\Column(name="url_tracking", type="boolean", nullable=true)
     */
    private $urlTracking;

    /**
     * @var boolean
     *
     * @ORM\Column(name="forward_replies", type="boolean", nullable=true)
     */
    private $forwardReplies;

    /**
     * @var boolean
     *
     * @ORM\Column(name="auto_responder", type="boolean", nullable=true)
     */
    private $autoResponder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="open_tracking", type="boolean", nullable=true)
     */
    private $openTracking;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_completed", type="boolean", nullable=true)
     */
    private $isCompleted;

    /**
     * @var boolean
     *
     * @ORM\Column(name="override_verp", type="boolean", nullable=true)
     */
    private $overrideVerp = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="scheduled_date", type="datetime", nullable=true)
     */
    private $scheduledDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="approval_date", type="datetime", nullable=true)
     */
    private $approvalDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="approval_status_id", type="integer", nullable=true)
     */
    private $approvalStatusId;

    /**
     * @var string
     *
     * @ORM\Column(name="approval_note", type="text", nullable=true)
     */
    private $approvalNote;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_archived", type="boolean", nullable=true)
     */
    private $isArchived = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", nullable=true)
     */
    private $visibility = 'User and User Admin Only';

    /**
     * @var boolean
     *
     * @ORM\Column(name="dedupe_email", type="boolean", nullable=true)
     */
    private $dedupeEmail = '0';

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
     * @var \Civi\Mailing\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="header_id", referencedColumnName="id")
     * })
     */
    private $header;

    /**
     * @var \Civi\Mailing\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="footer_id", referencedColumnName="id")
     * })
     */
    private $footer;

    /**
     * @var \Civi\Mailing\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="reply_id", referencedColumnName="id")
     * })
     */
    private $reply;

    /**
     * @var \Civi\Mailing\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="unsubscribe_id", referencedColumnName="id")
     * })
     */
    private $unsubscribe;

    /**
     * @var \Civi\Mailing\Component
     *
     * @ORM\ManyToOne(targetEntity="Civi\Mailing\Component")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="optout_id", referencedColumnName="id")
     * })
     */
    private $optout;

    /**
     * @var \Civi\Core\MessageTemplate
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\MessageTemplate")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="msg_template_id", referencedColumnName="id")
     * })
     */
    private $msgTemplate;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_id", referencedColumnName="id")
     * })
     */
    private $created;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="scheduled_id", referencedColumnName="id")
     * })
     */
    private $scheduled;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="approver_id", referencedColumnName="id")
     * })
     */
    private $approver;

    /**
     * @var \Civi\Campaign\Campaign
     *
     * @ORM\ManyToOne(targetEntity="Civi\Campaign\Campaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     * })
     */
    private $campaign;

    /**
     * @var \Civi\SMS\Provider
     *
     * @ORM\ManyToOne(targetEntity="Civi\SMS\Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sms_provider_id", referencedColumnName="id")
     * })
     */
    private $smsProvider;



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
     * Set resubscribeId
     *
     * @param integer $resubscribeId
     * @return Mailing
     */
    public function setResubscribeId($resubscribeId)
    {
        $this->resubscribeId = $resubscribeId;

        return $this;
    }

    /**
     * Get resubscribeId
     *
     * @return integer 
     */
    public function getResubscribeId()
    {
        return $this->resubscribeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Mailing
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set fromName
     *
     * @param string $fromName
     * @return Mailing
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * Get fromName
     *
     * @return string 
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Set fromEmail
     *
     * @param string $fromEmail
     * @return Mailing
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * Get fromEmail
     *
     * @return string 
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * Set replytoEmail
     *
     * @param string $replytoEmail
     * @return Mailing
     */
    public function setReplytoEmail($replytoEmail)
    {
        $this->replytoEmail = $replytoEmail;

        return $this;
    }

    /**
     * Get replytoEmail
     *
     * @return string 
     */
    public function getReplytoEmail()
    {
        return $this->replytoEmail;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Mailing
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set bodyText
     *
     * @param string $bodyText
     * @return Mailing
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * Get bodyText
     *
     * @return string 
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Set bodyHtml
     *
     * @param string $bodyHtml
     * @return Mailing
     */
    public function setBodyHtml($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;

        return $this;
    }

    /**
     * Get bodyHtml
     *
     * @return string 
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * Set urlTracking
     *
     * @param boolean $urlTracking
     * @return Mailing
     */
    public function setUrlTracking($urlTracking)
    {
        $this->urlTracking = $urlTracking;

        return $this;
    }

    /**
     * Get urlTracking
     *
     * @return boolean 
     */
    public function getUrlTracking()
    {
        return $this->urlTracking;
    }

    /**
     * Set forwardReplies
     *
     * @param boolean $forwardReplies
     * @return Mailing
     */
    public function setForwardReplies($forwardReplies)
    {
        $this->forwardReplies = $forwardReplies;

        return $this;
    }

    /**
     * Get forwardReplies
     *
     * @return boolean 
     */
    public function getForwardReplies()
    {
        return $this->forwardReplies;
    }

    /**
     * Set autoResponder
     *
     * @param boolean $autoResponder
     * @return Mailing
     */
    public function setAutoResponder($autoResponder)
    {
        $this->autoResponder = $autoResponder;

        return $this;
    }

    /**
     * Get autoResponder
     *
     * @return boolean 
     */
    public function getAutoResponder()
    {
        return $this->autoResponder;
    }

    /**
     * Set openTracking
     *
     * @param boolean $openTracking
     * @return Mailing
     */
    public function setOpenTracking($openTracking)
    {
        $this->openTracking = $openTracking;

        return $this;
    }

    /**
     * Get openTracking
     *
     * @return boolean 
     */
    public function getOpenTracking()
    {
        return $this->openTracking;
    }

    /**
     * Set isCompleted
     *
     * @param boolean $isCompleted
     * @return Mailing
     */
    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    /**
     * Get isCompleted
     *
     * @return boolean 
     */
    public function getIsCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * Set overrideVerp
     *
     * @param boolean $overrideVerp
     * @return Mailing
     */
    public function setOverrideVerp($overrideVerp)
    {
        $this->overrideVerp = $overrideVerp;

        return $this;
    }

    /**
     * Get overrideVerp
     *
     * @return boolean 
     */
    public function getOverrideVerp()
    {
        return $this->overrideVerp;
    }

    /**
     * Set createdDate
     *
     * @param \DateTime $createdDate
     * @return Mailing
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
     * Set scheduledDate
     *
     * @param \DateTime $scheduledDate
     * @return Mailing
     */
    public function setScheduledDate($scheduledDate)
    {
        $this->scheduledDate = $scheduledDate;

        return $this;
    }

    /**
     * Get scheduledDate
     *
     * @return \DateTime 
     */
    public function getScheduledDate()
    {
        return $this->scheduledDate;
    }

    /**
     * Set approvalDate
     *
     * @param \DateTime $approvalDate
     * @return Mailing
     */
    public function setApprovalDate($approvalDate)
    {
        $this->approvalDate = $approvalDate;

        return $this;
    }

    /**
     * Get approvalDate
     *
     * @return \DateTime 
     */
    public function getApprovalDate()
    {
        return $this->approvalDate;
    }

    /**
     * Set approvalStatusId
     *
     * @param integer $approvalStatusId
     * @return Mailing
     */
    public function setApprovalStatusId($approvalStatusId)
    {
        $this->approvalStatusId = $approvalStatusId;

        return $this;
    }

    /**
     * Get approvalStatusId
     *
     * @return integer 
     */
    public function getApprovalStatusId()
    {
        return $this->approvalStatusId;
    }

    /**
     * Set approvalNote
     *
     * @param string $approvalNote
     * @return Mailing
     */
    public function setApprovalNote($approvalNote)
    {
        $this->approvalNote = $approvalNote;

        return $this;
    }

    /**
     * Get approvalNote
     *
     * @return string 
     */
    public function getApprovalNote()
    {
        return $this->approvalNote;
    }

    /**
     * Set isArchived
     *
     * @param boolean $isArchived
     * @return Mailing
     */
    public function setIsArchived($isArchived)
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * Get isArchived
     *
     * @return boolean 
     */
    public function getIsArchived()
    {
        return $this->isArchived;
    }

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return Mailing
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return string 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set dedupeEmail
     *
     * @param boolean $dedupeEmail
     * @return Mailing
     */
    public function setDedupeEmail($dedupeEmail)
    {
        $this->dedupeEmail = $dedupeEmail;

        return $this;
    }

    /**
     * Get dedupeEmail
     *
     * @return boolean 
     */
    public function getDedupeEmail()
    {
        return $this->dedupeEmail;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return Mailing
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

    /**
     * Set header
     *
     * @param \Civi\Mailing\Component $header
     * @return Mailing
     */
    public function setHeader(\Civi\Mailing\Component $header = null)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Get header
     *
     * @return \Civi\Mailing\Component 
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set footer
     *
     * @param \Civi\Mailing\Component $footer
     * @return Mailing
     */
    public function setFooter(\Civi\Mailing\Component $footer = null)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Get footer
     *
     * @return \Civi\Mailing\Component 
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Set reply
     *
     * @param \Civi\Mailing\Component $reply
     * @return Mailing
     */
    public function setReply(\Civi\Mailing\Component $reply = null)
    {
        $this->reply = $reply;

        return $this;
    }

    /**
     * Get reply
     *
     * @return \Civi\Mailing\Component 
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * Set unsubscribe
     *
     * @param \Civi\Mailing\Component $unsubscribe
     * @return Mailing
     */
    public function setUnsubscribe(\Civi\Mailing\Component $unsubscribe = null)
    {
        $this->unsubscribe = $unsubscribe;

        return $this;
    }

    /**
     * Get unsubscribe
     *
     * @return \Civi\Mailing\Component 
     */
    public function getUnsubscribe()
    {
        return $this->unsubscribe;
    }

    /**
     * Set optout
     *
     * @param \Civi\Mailing\Component $optout
     * @return Mailing
     */
    public function setOptout(\Civi\Mailing\Component $optout = null)
    {
        $this->optout = $optout;

        return $this;
    }

    /**
     * Get optout
     *
     * @return \Civi\Mailing\Component 
     */
    public function getOptout()
    {
        return $this->optout;
    }

    /**
     * Set msgTemplate
     *
     * @param \Civi\Core\MessageTemplate $msgTemplate
     * @return Mailing
     */
    public function setMsgTemplate(\Civi\Core\MessageTemplate $msgTemplate = null)
    {
        $this->msgTemplate = $msgTemplate;

        return $this;
    }

    /**
     * Get msgTemplate
     *
     * @return \Civi\Core\MessageTemplate 
     */
    public function getMsgTemplate()
    {
        return $this->msgTemplate;
    }

    /**
     * Set created
     *
     * @param \Civi\Contact\Contact $created
     * @return Mailing
     */
    public function setCreated(\Civi\Contact\Contact $created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \Civi\Contact\Contact 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set scheduled
     *
     * @param \Civi\Contact\Contact $scheduled
     * @return Mailing
     */
    public function setScheduled(\Civi\Contact\Contact $scheduled = null)
    {
        $this->scheduled = $scheduled;

        return $this;
    }

    /**
     * Get scheduled
     *
     * @return \Civi\Contact\Contact 
     */
    public function getScheduled()
    {
        return $this->scheduled;
    }

    /**
     * Set approver
     *
     * @param \Civi\Contact\Contact $approver
     * @return Mailing
     */
    public function setApprover(\Civi\Contact\Contact $approver = null)
    {
        $this->approver = $approver;

        return $this;
    }

    /**
     * Get approver
     *
     * @return \Civi\Contact\Contact 
     */
    public function getApprover()
    {
        return $this->approver;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Mailing
     */
    public function setCampaign(\Civi\Campaign\Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Civi\Campaign\Campaign 
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set smsProvider
     *
     * @param \Civi\SMS\Provider $smsProvider
     * @return Mailing
     */
    public function setSmsProvider(\Civi\SMS\Provider $smsProvider = null)
    {
        $this->smsProvider = $smsProvider;

        return $this;
    }

    /**
     * Get smsProvider
     *
     * @return \Civi\SMS\Provider 
     */
    public function getSmsProvider()
    {
        return $this->smsProvider;
    }
}
