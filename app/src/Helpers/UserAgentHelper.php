<?php
class UserAgentHelper {
    
    public static function parseList(array $logs) {
        foreach($logs as &$log) {
            $log['browser_short'] = self::parse($log['user_agent']);
        }
        return $logs;
    }

    public static function parse($ua) {
        // 1. Platform (OS)
        $platform = 'Unbekannt';
        if (preg_match('/windows|win32/i', $ua)) $platform = 'Windows';
        elseif (preg_match('/android/i', $ua)) $platform = 'Android';
        elseif (preg_match('/iphone|ipad|ios/i', $ua)) $platform = 'iOS';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $platform = 'Mac';
        elseif (preg_match('/linux/i', $ua)) $platform = 'Linux';
        
        // 2. Browser
        $browser = 'Unbekannt';
        if (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
        
        return "$platform / $browser";
    }
}