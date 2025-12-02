<?php

namespace Innoweb\CookieConsent\Forms;

use Innoweb\CookieConsent\CookieConsent;
use Innoweb\CookieConsent\Model\CookieGroup;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

/**
 * Class CookieConsentCheckBoxField
 *
 * @author Bram de Leeuw
 */
class CookieConsentCheckBoxField extends CheckboxField
{
    /**
     * @var CookieGroup
     */
    protected CookieGroup $cookieGroup;

    public function __construct(CookieGroup $cookieGroup)
    {
        $this->cookieGroup = $cookieGroup;
        parent::__construct(
            $cookieGroup->ConfigName,
            $cookieGroup->Title,
            $cookieGroup->isRequired()
        );

        $this->setDisabled($cookieGroup->isRequired());
    }

    public function Field($properties = []): DBHTMLText
    {
        if (Config::inst()->get(CookieConsent::class, 'include_css')) {
            Requirements::css('moritz-sauer-13/silverstripe-cookie-consent:client/dist/css/cookieconsentcheckboxfield.css');
        }

        return parent::Field($properties);
    }

    public function getContent(): ?DBField
    {
        return $this->cookieGroup->dbObject('Content');
    }

    public function getCookieGroup(): CookieGroup
    {
        return $this->cookieGroup;
    }
}
