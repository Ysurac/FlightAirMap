<?php
/**
 * User Agent Generator
 * @version 1.0
 * @link https://github.com/Dreyer/random-uagent
 * @author Dreyer
 */

class UAgent
{
    // General token that says the browser is Mozilla compatible, 
    // and is common to almost every browser today.
    const MOZILLA = 'Mozilla/5.0 ';

    /**
     * Processors by Arch.
     */
    public static $processors = array(
        'lin' => array( 'i686', 'x86_64' ),
        'mac' => array( 'Intel', 'PPC', 'U; Intel', 'U; PPC' ),
        'win' => array( 'foo' )
    );

    /**
     * Browsers
     * 
     * Weighting is based on market share to determine frequency.
     */
    public static $browsers = array(
        34 => array(
            89 => array( 'chrome', 'win' ),
            9  => array( 'chrome', 'mac' ),
            2  => array( 'chrome', 'lin' )
        ),
        32 => array(
            100 => array( 'iexplorer', 'win' )
        ),
        25 => array(
            83 => array( 'firefox', 'win' ),
            16 => array( 'firefox', 'mac' ),
            1  => array( 'firefox', 'lin' )
        ),
        7 => array(
            95 => array( 'safari', 'mac' ),
            4  => array( 'safari', 'win' ),
            1  => array( 'safari', 'lin' )
        ),
        2 => array(
            91 => array( 'opera', 'win' ),
            6  => array( 'opera', 'lin' ),
            3  => array( 'opera', 'mac' )
        )
    );

    /**
     * List of Lanuge Culture Codes (ISO 639-1)
     *
     * @see: http://msdn.microsoft.com/en-gb/library/ee825488(v=cs.20).aspx
     */
    public static $languages = array(
        'af-ZA', 'ar-AE', 'ar-BH', 'ar-DZ', 'ar-EG', 'ar-IQ', 'ar-JO', 'ar-KW', 'ar-LB',
        'ar-LY', 'ar-MA', 'ar-OM', 'ar-QA', 'ar-SA', 'ar-SY', 'ar-TN', 'ar-YE', 'be-BY',
        'bg-BG', 'ca-ES', 'cs-CZ', 'Cy-az-AZ', 'Cy-sr-SP', 'Cy-uz-UZ', 'da-DK', 'de-AT',
        'de-CH', 'de-DE', 'de-LI', 'de-LU', 'div-MV', 'el-GR', 'en-AU', 'en-BZ', 'en-CA', 
        'en-CB', 'en-GB', 'en-IE', 'en-JM', 'en-NZ', 'en-PH', 'en-TT', 'en-US', 'en-ZA', 
        'en-ZW', 'es-AR', 'es-BO', 'es-CL', 'es-CO',  'es-CR', 'es-DO', 'es-EC', 'es-ES',
        'es-GT', 'es-HN', 'es-MX', 'es-NI', 'es-PA', 'es-PE', 'es-PR', 'es-PY', 'es-SV',
        'es-UY', 'es-VE', 'et-EE', 'eu-ES', 'fa-IR', 'fi-FI', 'fo-FO', 'fr-BE', 'fr-CA',
        'fr-CH', 'fr-FR', 'fr-LU', 'fr-MC', 'gl-ES', 'gu-IN', 'he-IL', 'hi-IN', 'hr-HR', 
        'hu-HU', 'hy-AM', 'id-ID', 'is-IS', 'it-CH', 'it-IT', 'ja-JP', 'ka-GE', 'kk-KZ',
        'kn-IN', 'kok-IN', 'ko-KR', 'ky-KZ', 'Lt-az-AZ', 'lt-LT', 'Lt-sr-SP', 'Lt-uz-UZ', 
        'lv-LV', 'mk-MK', 'mn-MN', 'mr-IN', 'ms-BN', 'ms-MY', 'nb-NO', 'nl-BE', 'nl-NL', 
        'nn-NO', 'pa-IN', 'pl-PL', 'pt-BR', 'pt-PT', 'ro-RO', 'ru-RU', 'sa-IN', 'sk-SK', 
        'sl-SI', 'sq-AL', 'sv-FI', 'sv-SE', 'sw-KE', 'syr-SY', 'ta-IN', 'te-IN', 'th-TH', 
        'tr-TR', 'tt-RU', 'uk-UA', 'ur-PK', 'vi-VN', 'zh-CHS', 'zh-CHT', 'zh-CN', 'zh-HK', 
        'zh-MO', 'zh-SG', 'zh-TW',   
    );    

    /**
     * Generate Device Platform
     *
     * Uses a random result with a weighting related to frequencies.
     */
    public static function generate_platform()
    {
        $rand = mt_rand( 1, 100 );
        $sum = 0;

        foreach ( self::$browsers as $share => $freq_os )
        {
            $sum += $share;

            if ( $rand <= $sum )
            {
                $rand = mt_rand( 1, 100 );
                $sum = 0;

                foreach ( $freq_os as $share => $choice )
                {
                    $sum += $share;

                    if ( $rand <= $sum )
                    {
                        return $choice;
                    }
                }
            }
        }

        throw new Exception( 'Sum of $browsers frequency is not 100.' );
    }

    private static function array_random( $array )
    {
        $i = array_rand( $array, 1 );

        return $array[$i];
    }

    private static function get_language( $lang = array() )
    {
        return self::array_random( empty( $lang ) ? self::$languages : $lang );
    }

    private static function get_processor( $os )
    {
        return self::array_random( self::$processors[$os] );
    }

    private static function get_version_nt()
    {   
        // Win2k (5.0) to Win 7 (6.1).
        return mt_rand( 5, 6 ) . '.' . mt_rand( 0, 1 );
    }

    private static function get_version_osx()
    {
        return '10_' . mt_rand( 5, 7 ) . '_' . mt_rand( 0, 9 );
    }

    private static function get_version_webkit()
    {
        return mt_rand( 531, 536 ) . mt_rand( 0, 2 );
    }

    private static function get_verison_chrome()
    {
        return mt_rand( 13, 15 ) . '.0.' . mt_rand( 800, 899 ) . '.0';
    }

    private static function get_version_gecko()
    {
        return mt_rand( 17, 31 ) . '.0';
    }

    private static function get_version_ie()
    {
        return mt_rand( 7, 9 ) . '.0';
    }

    private static function get_version_trident()
    {
        // IE8 (4.0) to IE11 (7.0).
        return mt_rand( 4, 7 ) . '.0';
    }

    private static function get_version_net()
    {
        // generic .NET Framework common language run time (CLR) version numbers.
        $frameworks = array(
            '2.0.50727',
            '3.0.4506',
            '3.5.30729',
        );

        $rev = '.' . mt_rand( 26, 648 );

        return self::array_random( $frameworks ) . $rev;
    }

    private static function get_version_safari()
    {
        if ( mt_rand( 0, 1 ) == 0 )
        {
            $ver = mt_rand( 4, 5 ) . '.' . mt_rand( 0, 1 );
        }
        else
        {
            $ver = mt_rand( 4, 5 ) . '.0.' . mt_rand( 1, 5 );
        }

        return $ver;
    }

    private static function get_version_opera()
    {
        return mt_rand( 15, 19 ) . '.0.' . mt_rand( 1147, 1284 ) . mt_rand( 49, 100 );
    }

    /**
     * Opera
     * 
     * @see: http://dev.opera.com/blog/opera-user-agent-strings-opera-15-and-beyond/
     */
    public static function opera( $arch )
    {
        $opera = ' OPR/' . self::get_version_opera();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::get_version_webkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $chrome = ' Chrome/' . self::get_verison_chrome();
        $safari = ' Safari/' . $engine;

        switch ( $arch )
        {
            case 'lin':
                return '(X11; Linux {proc}) ' . $webkit . $chrome . $safari . $opera;
            case 'mac':
                $osx = self::get_version_osx();
                return '(Macintosh; U; {proc} Mac OS X ' . $osx . ')' . $webkit . $chrome . $safari . $opera;
            case 'win':
                // fall through.
            default:
                $nt = self::get_version_nt();
                return '(Windows NT ' . $nt . '; WOW64) ' . $webkit . $chrome . $safari . $opera;
        }
    }    

    /**
     * Safari
     *
     */
    public static function safari( $arch )
    {
        $version = ' Version/' . self::get_version_safari();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::get_version_webkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $safari = ' Safari/' . $engine;

        switch ( $arch )
        {
            case 'mac':
                $osx = self::get_version_osx();
                return '(Macintosh; U; {proc} Mac OS X ' . $osx . '; {lang})' . $webkit . $version . $safari;
            case 'win':
                // fall through.
            default:
                $nt = self::get_version_nt();
                return '(Windows; U; Windows NT ' . $nt . ')' . $webkit . $version . $safari;
        }

    }

    /**
     * Internet Explorer
     * 
     * @see: http://msdn.microsoft.com/en-gb/library/ms537503(v=vs.85).aspx
     */
    public static function iexplorer( $arch )
    {
        $nt = self::get_version_nt();
        $ie = self::get_version_ie();
        $trident = self::get_version_trident();
        $net = self::get_version_net();

        return '(compatible' 
            . '; MSIE ' . $ie 
            . '; Windows NT ' . $nt 
            . '; WOW64' // A 32-bit version of Internet Explorer is running on a 64-bit processor.
            . '; Trident/' . $trident 
            . '; .NET CLR ' . $net
            . ')';
    }

    /**
     * Firefox User-Agent
     *
     * @see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
     */
    public static function firefox( $arch )
    {
        // The release version of Gecko. 
        $gecko = self::get_version_gecko();

        // On desktop, the gecko trail is fixed.
        $trail = '20100101';

        $release = 'rv:' . $gecko;
        $version = 'Gecko/' . $trail . ' Firefox/' . $gecko;

        switch ( $arch )
        {
            case 'lin':
                return '(X11; Linux {proc}; ' . $release . ') ' . $version;
            case 'mac':
                $osx = self::get_version_osx();
                return '(Macintosh; {proc} Mac OS X ' . $osx . '; ' . $release . ') ' . $version;
            case 'win':
                // fall through.
            default:
                $nt = self::get_version_nt();
                return '(Windows NT ' . $nt . '; {lang}; ' . $release . ') ' . $version;
        }
    }

    public static function chrome( $arch )
    {
        $chrome = ' Chrome/' . self::get_verison_chrome();

        // WebKit Rendering Engine (WebKit = Backend, Safari = Frontend).
        $engine = self::get_version_webkit();
        $webkit = ' AppleWebKit/' . $engine . ' (KHTML, like Gecko)';
        $safari = ' Safari/' . $engine;

        switch ( $arch )
        {
            case 'lin':
                return '(X11; Linux {proc}) ' . $webkit . $chrome . $safari;
            case 'mac':
                $osx = self::get_version_osx();
                return '(Macintosh; U; {proc} Mac OS X ' . $osx . ')' . $webkit . $chrome . $safari;
            case 'win':
                // fall through.
            default:
                $nt = self::get_version_nt();
                return '(Windows NT ' . $nt . ') ' . $webkit . $chrome . $safari;
        }
    }

    public static function random( $lang = array( 'en-US' ) )
    {
        list( $browser, $os ) = self::generate_platform();

        return self::generate( $browser, $os, $lang );
    }

    public static function generate( $browser = 'chrome', $os = 'win', $lang = array( 'en-US' ) )
    {
        $ua = self::MOZILLA . call_user_func( 'UAgent::' . $browser, $os );

        $tags = array(
            '{proc}' => self::get_processor( $os ),
            '{lang}' => self::get_language( $lang ),
        );

        $ua = str_replace( array_keys( $tags ), array_values( $tags ), $ua );

        return $ua;
    }
}
?>
