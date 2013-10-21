<?php

namespace Civi\Contribut;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContributionWidget
 *
 * @ORM\Table(name="civicrm_contribution_widget", indexes={@ORM\Index(name="FK_civicrm_contribution_widget_contribution_page_id", columns={"contribution_page_id"})})
 * @ORM\Entity
 */
class ContributionWidget extends \Civi\Core\Entity
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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="url_logo", type="string", length=255, nullable=true)
     */
    private $urlLogo;

    /**
     * @var string
     *
     * @ORM\Column(name="button_title", type="string", length=255, nullable=true)
     */
    private $buttonTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * @var string
     *
     * @ORM\Column(name="url_homepage", type="string", length=255, nullable=true)
     */
    private $urlHomepage;

    /**
     * @var string
     *
     * @ORM\Column(name="color_title", type="string", length=10, nullable=true)
     */
    private $colorTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="color_button", type="string", length=10, nullable=true)
     */
    private $colorButton;

    /**
     * @var string
     *
     * @ORM\Column(name="color_bar", type="string", length=10, nullable=true)
     */
    private $colorBar;

    /**
     * @var string
     *
     * @ORM\Column(name="color_main_text", type="string", length=10, nullable=true)
     */
    private $colorMainText;

    /**
     * @var string
     *
     * @ORM\Column(name="color_main", type="string", length=10, nullable=true)
     */
    private $colorMain;

    /**
     * @var string
     *
     * @ORM\Column(name="color_main_bg", type="string", length=10, nullable=true)
     */
    private $colorMainBg;

    /**
     * @var string
     *
     * @ORM\Column(name="color_bg", type="string", length=10, nullable=true)
     */
    private $colorBg;

    /**
     * @var string
     *
     * @ORM\Column(name="color_about_link", type="string", length=10, nullable=true)
     */
    private $colorAboutLink;

    /**
     * @var string
     *
     * @ORM\Column(name="color_homepage_link", type="string", length=10, nullable=true)
     */
    private $colorHomepageLink;

    /**
     * @var \Civi\Contribute\ContributionPage
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\ContributionPage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_page_id", referencedColumnName="id")
     * })
     */
    private $contributionPage;



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
     * Set isActive
     *
     * @param boolean $isActive
     * @return ContributionWidget
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
     * Set title
     *
     * @param string $title
     * @return ContributionWidget
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set urlLogo
     *
     * @param string $urlLogo
     * @return ContributionWidget
     */
    public function setUrlLogo($urlLogo)
    {
        $this->urlLogo = $urlLogo;

        return $this;
    }

    /**
     * Get urlLogo
     *
     * @return string 
     */
    public function getUrlLogo()
    {
        return $this->urlLogo;
    }

    /**
     * Set buttonTitle
     *
     * @param string $buttonTitle
     * @return ContributionWidget
     */
    public function setButtonTitle($buttonTitle)
    {
        $this->buttonTitle = $buttonTitle;

        return $this;
    }

    /**
     * Get buttonTitle
     *
     * @return string 
     */
    public function getButtonTitle()
    {
        return $this->buttonTitle;
    }

    /**
     * Set about
     *
     * @param string $about
     * @return ContributionWidget
     */
    public function setAbout($about)
    {
        $this->about = $about;

        return $this;
    }

    /**
     * Get about
     *
     * @return string 
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * Set urlHomepage
     *
     * @param string $urlHomepage
     * @return ContributionWidget
     */
    public function setUrlHomepage($urlHomepage)
    {
        $this->urlHomepage = $urlHomepage;

        return $this;
    }

    /**
     * Get urlHomepage
     *
     * @return string 
     */
    public function getUrlHomepage()
    {
        return $this->urlHomepage;
    }

    /**
     * Set colorTitle
     *
     * @param string $colorTitle
     * @return ContributionWidget
     */
    public function setColorTitle($colorTitle)
    {
        $this->colorTitle = $colorTitle;

        return $this;
    }

    /**
     * Get colorTitle
     *
     * @return string 
     */
    public function getColorTitle()
    {
        return $this->colorTitle;
    }

    /**
     * Set colorButton
     *
     * @param string $colorButton
     * @return ContributionWidget
     */
    public function setColorButton($colorButton)
    {
        $this->colorButton = $colorButton;

        return $this;
    }

    /**
     * Get colorButton
     *
     * @return string 
     */
    public function getColorButton()
    {
        return $this->colorButton;
    }

    /**
     * Set colorBar
     *
     * @param string $colorBar
     * @return ContributionWidget
     */
    public function setColorBar($colorBar)
    {
        $this->colorBar = $colorBar;

        return $this;
    }

    /**
     * Get colorBar
     *
     * @return string 
     */
    public function getColorBar()
    {
        return $this->colorBar;
    }

    /**
     * Set colorMainText
     *
     * @param string $colorMainText
     * @return ContributionWidget
     */
    public function setColorMainText($colorMainText)
    {
        $this->colorMainText = $colorMainText;

        return $this;
    }

    /**
     * Get colorMainText
     *
     * @return string 
     */
    public function getColorMainText()
    {
        return $this->colorMainText;
    }

    /**
     * Set colorMain
     *
     * @param string $colorMain
     * @return ContributionWidget
     */
    public function setColorMain($colorMain)
    {
        $this->colorMain = $colorMain;

        return $this;
    }

    /**
     * Get colorMain
     *
     * @return string 
     */
    public function getColorMain()
    {
        return $this->colorMain;
    }

    /**
     * Set colorMainBg
     *
     * @param string $colorMainBg
     * @return ContributionWidget
     */
    public function setColorMainBg($colorMainBg)
    {
        $this->colorMainBg = $colorMainBg;

        return $this;
    }

    /**
     * Get colorMainBg
     *
     * @return string 
     */
    public function getColorMainBg()
    {
        return $this->colorMainBg;
    }

    /**
     * Set colorBg
     *
     * @param string $colorBg
     * @return ContributionWidget
     */
    public function setColorBg($colorBg)
    {
        $this->colorBg = $colorBg;

        return $this;
    }

    /**
     * Get colorBg
     *
     * @return string 
     */
    public function getColorBg()
    {
        return $this->colorBg;
    }

    /**
     * Set colorAboutLink
     *
     * @param string $colorAboutLink
     * @return ContributionWidget
     */
    public function setColorAboutLink($colorAboutLink)
    {
        $this->colorAboutLink = $colorAboutLink;

        return $this;
    }

    /**
     * Get colorAboutLink
     *
     * @return string 
     */
    public function getColorAboutLink()
    {
        return $this->colorAboutLink;
    }

    /**
     * Set colorHomepageLink
     *
     * @param string $colorHomepageLink
     * @return ContributionWidget
     */
    public function setColorHomepageLink($colorHomepageLink)
    {
        $this->colorHomepageLink = $colorHomepageLink;

        return $this;
    }

    /**
     * Get colorHomepageLink
     *
     * @return string 
     */
    public function getColorHomepageLink()
    {
        return $this->colorHomepageLink;
    }

    /**
     * Set contributionPage
     *
     * @param \Civi\Contribute\ContributionPage $contributionPage
     * @return ContributionWidget
     */
    public function setContributionPage(\Civi\Contribute\ContributionPage $contributionPage = null)
    {
        $this->contributionPage = $contributionPage;

        return $this;
    }

    /**
     * Get contributionPage
     *
     * @return \Civi\Contribute\ContributionPage 
     */
    public function getContributionPage()
    {
        return $this->contributionPage;
    }
}
