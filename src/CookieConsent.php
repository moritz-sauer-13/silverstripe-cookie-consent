<?php

namespace Innoweb\CookieConsent;

use Innoweb\CookieConsent\Model\CookieGroup;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

class CookieConsent
{
    use Extensible;
    use Injectable;
    use Configurable;

    const string NECESSARY = 'Necessary';
    const string ANALYTICS = 'Analytics';
    const string MARKETING = 'Marketing';
    const string EXTERNAL = 'External';
    const string PREFERENCES = 'Preferences';

    private static array $required_groups = [
        self::NECESSARY
    ];

    private static array $cookies = [];

    private static bool $include_css = true;

    private static bool $create_default_pages = true;

    /**
     * Use this name when setting the consent cookie
     *
     * @config
     * @var string
     */
    private static string $cookie_name = 'CookieConsent';

    /**
     * The expiry time in days for a consent persistence cookie
     *
     * @config
     * @var int
     */
    private static int $cookie_expiry = 60;

    /**
     * Use this path when setting the consent cookie
     *
     * @config
     * @var string
     */
    private static ?string $cookie_path = null;

    /**
     * Use this domain when setting the consent cookie
     *
     * @config
     * @var string
     */
    private static ?string $cookie_domain = null;

    /**
     * Use http-only cookies. Set to true if you don't need js access.
     *
     * @config
     * @var bool
     */
    private static bool $cookie_http_only = false;

    /**
     * Use this name when using the cookie consent http header
     *
     * @config
     * @var string
     */
    private static $header_name = 'X-Cookie-Consent';

    /**
     * Set consent cookies for all hosts allowed through SS_ALLOWED_HOSTS config
     *
     * @config
     * @var bool
     */
    private static bool $include_all_allowed_hosts = false;

    /**
     * Check if there is consent for the given cookie
     *
     * @param string $group
     * @return bool
     */
    public static function check(string $group = CookieConsent::NECESSARY): bool
    {
        $cookies = self::config()->get('cookies');
        if (!isset($cookies[$group])) {
            Injector::inst()->get(LoggerInterface::class)->error(sprintf(
                "The cookie group '%s' is not configured. You need to add it to the cookies config on %s",
                $group,
                self::class
            ));
            return false;
        }

        $consent = self::getConsent();
        return in_array($group, $consent);
    }

    /**
     * Grant consent for the given cookie group
     *
     * @param $group
     */
    public static function grant($group): void
    {
        $consent = self::getConsent();
        if (is_array($group)) {
            $consent = array_merge($consent, $group);
        } else {
            array_push($consent, $group);
        }
        self::setConsent($consent);
    }

    /**
     * Grant consent for all the configured cookie groups
     */
    public static function grantAll(): void
    {
        $consent = array_keys(Config::inst()->get(CookieConsent::class, 'cookies'));
        self::setConsent($consent);
    }

    /**
     * Remove consent for the given cookie group
     *
     * @param $group
     */
    public static function remove($group): void
    {
        $consent = self::getConsent();
        $key = array_search($group, $consent);
        $cookies = Config::inst()->get(CookieConsent::class, 'cookies');
        if (isset($cookies[$group])) {

            // go through cookies set on request and check if they are set for this group
            foreach ($_COOKIE as $cookieName => $value) {

                // check if the cookie is set for this group
                foreach ($cookies[$group] as $configuredHost => $configuredCookies) {

                    // get host and host without subdomain
                    $hosts = [];
                    $hosts[] = ($configuredHost === CookieGroup::LOCAL_PROVIDER)
                        ? Director::host()
                        : str_replace('_', '.', $configuredHost);

                    if (substr_count($hosts[0], '.') > 1) {
                        $hostParts = explode('.', $hosts[0]);
                        $count = count($hostParts);
                        for ($i = 1; $i < ($count - 1); $i++) {
                            array_shift($hostParts);
                            $hosts[] = implode('.', $hostParts);
                        }
                    }

                    foreach ($configuredCookies as $configuredCookie) {
                        if (preg_match('/^' . str_replace('*', '.*', $configuredCookie) . '$/', $cookieName)) {
                            foreach ($hosts as $host) {
                                Cookie::force_expiry($cookieName, null, $host);
                            }
                        }
                    }
                }

            }

        }

        unset($consent[$key]);
        self::setConsent($consent);
    }

    /**
     * Get the current configured consent
     *
     * @return array
     */
    public static function getConsent(): array
    {
        // get consent data from cookie
        if ($value = Cookie::get(self::config()->get('cookie_name'))) {
            return explode(',', $value);
        }
        // get consent data from http header (for example when in use behind CDN)
        if (($request = Controller::curr()->getRequest()) && ($value = $request->getHeader(self::config()->get('header_name')))) {
            return explode(',', urldecode($value));
        }
        return [];
    }

    /**
     * Save the consent
     *
     * @param $consent
     */
    public static function setConsent($consent): void
    {
        $consent = array_filter(array_unique(array_merge($consent, self::config()->get('required_groups'))));
		$request = Controller::curr()->getRequest();
        $secure = Director::is_https($request) && Session::config()->get('cookie_secure');
        Cookie::set(
            self::config()->get('cookie_name'),
            implode(',', $consent),
            self::config()->get('cookie_expiry'),
            self::config()->get('cookie_path'),
            self::config()->get('cookie_domain'),
            $secure,
            self::config()->get('cookie_http_only')
        );
    }

    /**
     * Check if the group is required
     *
     * @param $group
     * @return bool
     */
    public static function isRequired($group): bool
    {
        return in_array($group, self::config()->get('required_groups'));
    }
}
