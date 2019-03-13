<?php
/**
 * Parses a user agent string into its important parts
 *
 * @author Jesse G. Donat <donatj@gmail.com>
 *
 * @link https://github.com/donatj/PhpUserAgent
 * @link http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
 *
 * @param  string|null               $u_agent User agent string to parse or null. Uses $_SERVER['HTTP_USER_AGENT'] on NULL
 * @throws \InvalidArgumentException on not having a proper user agent to parse.
 * @return string[]                  an array with browser, version and platform keys
 */
namespace Framework\Libraries;
class UserAgentParser {
    public static function parse($u_agent = null) {
        if (null === $u_agent) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $u_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                throw new \InvalidArgumentException('parse_user_agent requires a user agent');
            }
        }

        $platform = null;
        $browser  = null;
        $version  = null;

        $empty = array('platform' => $platform, 'browser' => $browser, 'version' => $version);

        if ( ! $u_agent) {
            return $empty;
        }

        if (preg_match('/\((.*?)\)/m', $u_agent, $parent_matches)) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
				(?:\ [^;]*)?
				(?:;|$)/imx', $parent_matches[1], $result);

            $priority = array('Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11');

            $result['platform'] = array_unique($result['platform']);
            if (count($result['platform']) > 1) {
                if ($keys = array_intersect($priority, $result['platform'])) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } else if (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }

        if ('linux-gnu' == $platform || 'X11' == $platform) {
            $platform = 'Linux';
        } else if ('CrOS' == $platform) {
            $platform = 'Chrome OS';
        }

        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
				TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|UCBrowser|Puffin|OculusBrowser|SamsungBrowser|
				Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
				Valve\ Steam\ Tenfoot|
				NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
				(?:\)?;?)
				(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent, $result);

        // If nothing matched, return null (to avoid undefined index errors)
        if ( ! isset($result['browser'][0]) || ! isset($result['version'][0])) {
            if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result)) {
                return array('platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null: null);
            }

            return $empty;
        }

        if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result)) {
            $rv_result = $rv_result['version'];
        }

        $browser = $result['browser'][0];
        $version = $result['version'][0];

        $lowerBrowser = array_map('strtolower', $result['browser']);

        $find = function ($search, &$key, &$value = null) use ($lowerBrowser) {
            $search = (array) $search;

            foreach ($search as $val) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if (false !== $xkey) {
                    $value = $val;
                    $key   = $xkey;

                    return true;
                }
            }

            return false;
        };

        $key = 0;
        $val = '';
        if ('Iceweasel' == $browser || strtolower($browser) == 'icecat') {
            $browser = 'Firefox';
        } else if ($find('Playstation Vita', $key)) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } else if ($find(array('Kindle Fire', 'Silk'), $key, $val)) {
            $browser  = 'Silk' == $val ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if ( ! ($version = $result['version'][$key]) || ! is_numeric($version[0])) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } else if ($find('NintendoBrowser', $key) || 'Nintendo 3DS' == $platform) {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } else if ($find('Kindle', $key, $platform)) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } else if ($find('OPR', $key)) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } else if ($find('Opera', $key, $browser)) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } else if ($find('Puffin', $key, $browser)) {
            $version = $result['version'][$key];
            if (strlen($version) > 3) {
                $part = substr($version, -2);
                if (ctype_upper($part)) {
                    $version = substr($version, 0, -2);

                    $flags = array('IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows');
                    if (isset($flags[$part])) {
                        $platform = $flags[$part];
                    }
                }
            }
        } else if ($find('YaBrowser', $key, $browser)) {
            $browser = 'Yandex';
            $version = $result['version'][$key];
        } else if ($find(array('IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome'), $key, $browser)) {
            $version = $result['version'][$key];
        } else if ($rv_result && $find('Trident', $key)) {
            $browser = 'MSIE';
            $version = $rv_result;
        } else if ($find('UCBrowser', $key)) {
            $browser = 'UC Browser';
            $version = $result['version'][$key];
        } else if ($find('CriOS', $key)) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } else if ('AppleWebKit' == $browser) {
            if ('Android' == $platform) {
                // $key = 0;
                $browser = 'Android Browser';
            } else if (strpos($platform, 'BB') === 0) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } else if ('BlackBerry' == $platform || 'PlayBook' == $platform) {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }

            $find('Version', $key);
            $version = $result['version'][$key];
        } else if ($pKey = preg_grep('/playstation \d/', array_map('strtolower', $result['browser']))) {
            $pKey = reset($pKey);

            $platform = 'PlayStation ' . preg_replace('/\D/', '', $pKey);
            $browser  = 'NetFront';
        }

        return array('platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null);
    }
}