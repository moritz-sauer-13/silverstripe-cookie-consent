<?php

namespace Innoweb\CookieConsent\Extensions;

use Innoweb\CookieConsent\Model\CookieGroup;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class SiteConfigExtension
 * @package Innoweb\CookieConsent
 */
class SiteConfigExtension extends Extension
{
    private static $db = [
        'CookieConsentTitle' => 'Varchar(255)',
        'CookieConsentContent' => 'HTMLText'
    ];

    private static $translate = [
        'CookieConsentTitle',
        'CookieConsentContent'
    ];

    /**
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields): void
    {
        $fields->findOrMakeTab('Root.CookieConsent', _t(__CLASS__ . '.CookieConsent', 'Cookie Consent'));
        $fields->addFieldsToTab('Root.CookieConsent', [
            TextField::create('CookieConsentTitle', _t(__CLASS__ . '.CookieConsentTitle', 'Cookie Consent Title')),
            HtmlEditorField::create('CookieConsentContent', _t(__CLASS__ . '.CookieConsentContent', 'Cookie Consent Content')),
            GridField::create('Cookies', _t(__CLASS__ . '.Cookies', 'Cookies'), CookieGroup::get(), GridFieldConfig_RecordEditor::create())
        ]);
    }

    /**
     * Set the defaults this way beacause the SiteConfig is probably already created
     *
     * @throws ValidationException
     */
    public function requireDefaultRecords(): void
    {
        if ($config = SiteConfig::current_site_config()) {
            if (empty($config->CookieConsentTitle)) {
                $config->CookieConsentTitle = _t(__CLASS__ . '.DefaultCookieConsentTitle', 'This website uses cookies');
            }

            if (empty($config->CookieConsentContent)) {
                $config->CookieConsentContent = _t(__CLASS__ . '.DefaultCookieConsentContent', '<p>We use cookies to personalise content, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media and analytics partners who may combine it with other information that you’ve provided to them or that they’ve collected from your use of their services. You consent to our cookies if you continue to use our website.</p>');
            }

            $config->write();
        }
    }

    public function getCookieGroups(): DataList
    {
        return CookieGroup::get();
    }
}
