<?php
/**
 * This class is part of FlightAirmap. It's used to use languages translations
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/

if (!function_exists("gettext")) {
	function _($text) {
		return $text;
	}
} else {
	if (isset($_COOKIE['language']) && $_COOKIE['language'] != 'en_GB' && (isset($globalTranslate) && $globalTranslate)) {
		$Language = new Language();
		$lang = $_COOKIE['language'];
		putenv("LC_ALL=$lang");
		setlocale(LC_ALL, $Language->getLocale($lang));
		bindtextdomain("fam", dirname(__FILE__).'/../locale');
		textdomain("fam");
		bind_textdomain_codeset("fam", 'UTF-8');
	}
}

class Language {
	public $all_languages = array('ar_SA' => array('العَرَبِيَّةُ',	'ar',	'arabic'),
				'bg_BG' => array('Български',		'bg',	'bulgarian'),
				'id_ID' => array('Bahasa Indonesia',	'id',	'indonesian'),
				'ms_MY' => array('Bahasa Melayu',	'ms',	'malay'),
				'ca_ES' => array('Català',		'ca',	'catalan'), // ca_CA
				'cs_CZ' => array('Čeština',		'cs',	'czech'),
				'de_DE' => array('Deutsch',		'de',	'german'),
				'da_DK' => array('Dansk',		'da',	'danish')     , // dk_DK
				'et_EE' => array('Eesti',		'et',	'estonian'), // ee_ET
				'en_GB' => array('English',		'en',	'english'),
				'en_US' => array('English (US)',	'en',	'english'),
				'es_AR' => array('Español (Argentina)',	'es',	'spanish'),
				'es_CO' => array('Español (Colombia)',	'es',	'spanish'),
				'es_ES' => array('Español',	'es',	'spanish'),
				'es_419' => array('Español (América Latina)',	'es',	'spanish'),
				'es_MX' => array('Español (Mexico)',	'es',	'spanish'),
				'es_VE' => array('Español (Venezuela)',	'es',	'spanish'),
				'eu_ES' => array('Euskara',		'en',	'basque'),
				'fr_FR' => array('Français',		'fr',	'french'),
				'gl_ES' => array('Galego',		'gl',	'galician'),
				'el_GR' => array('Ελληνικά',		'el',	'greek'), // el_EL
				'he_IL' => array('עברית',		'he',	'hebrew'), // he_HE
				'hr_HR' => array('Hrvatski',		'hr',	'croatian'),
				'hu_HU' => array('Magyar',		'hu',	'hungarian'),
				'it_IT' => array('Italiano',		'it',	'italian'),
				'lv_LV' => array('Latviešu',		'lv',	'latvian'),
				'lt_LT' => array('Lietuvių',		'lt',	'lithuanian'),
				'nl_NL' => array('Nederlands',		'nl',	'dutch'),
				'nb_NO' => array('Norsk (Bokmål)',	'nb',	'norwegian'), // no_NB
				'nn_NO' => array('Norsk (Nynorsk)',	'nn',	'norwegian'), // no_NN
				'fa_IR' => array('فارسی',		'fa',	'persian'),
				'pl_PL' => array('Polski',		'pl',	'polish'),
				'pt_PT' => array('Português',		'pt',	'portuguese'),
				'pt_BR' => array('Português do Brasil',	'pt',	'brazilian portuguese'),
				'ro_RO' => array('Română',		'en',	'romanian'),
				'ru_RU' => array('Pусский',		'ru',	'russian'),
				'sk_SK' => array('Slovenčina',		'sk',	'slovak'),
				'sl_SI' => array('Slovenščina',		'sl',	'slovenian slovene'),
				'sr_RS' => array('Srpski',		'sr',	'serbian'),
				'fi_FI' => array('Suomi',		'fi',	'finish'),
				'sv_SE' => array('Svenska',		'sv',	'swedish'),
				'vi_VN' => array('Tiếng Việt',		'vi',	'vietnamese'),
				'th_TH' => array('ภาษาไทย',		'th',	'thai'),
				'tr_TR' => array('Türkçe',		'tr',	'turkish'),
				'uk_UA' => array('Українська',		'en',	'ukrainian'), // ua_UA
				'ja_JP' => array('日本語',		'ja',	'japanese'),
				'zh_CN' => array('简体中文',		'zh',	'chinese'),
				'zh_TW' => array('繁體中文',		'zh',	'chinese'),
				'ko_KR' => array('한글',		'ko',	'korean')
			);

	/**
	* Returns list of available locales
	*
	* @return array
	*/
	public function listLocaleDir()
	{
		$result = array('en_GB');
		if (!is_dir(dirname(__FILE__).'/../locale')) {
			return $result;
		}
		$handle = @opendir(dirname(__FILE__).'/../locale');
		if ($handle === false) return $result;
		while (false !== ($file = readdir($handle))) {
			$path = dirname(__FILE__).'/../locale'.'/'.$file.'/LC_MESSAGES/fam.mo';
			if ($file != "." && $file != ".." && @file_exists($path)) {
				$result[] = $file;
			}
		}
		closedir($handle);
		return $result;
	}

	public function getLocale($locale)
	{
		return array($locale,$this->all_languages[$locale][1],$this->all_languages[$locale][2],$locale.'.utf8',$locale.'.UTF8');
	}

	/**
	* Returns list of available languages
	*
	* @return array
	 */
	public function getLanguages()
	{
		$available = $this->listLocaleDir();
		$allAvailableLanguages = array();
		$currentLocal = setlocale(LC_ALL, 0);
		foreach ($available as $lang) {
			if (isset($this->all_languages[$lang]) && (setlocale(LC_ALL,$this->getLocale($lang)) || $lang = 'en_GB')) $allAvailableLanguages[$lang] = $this->all_languages[$lang];
		}
		setlocale(LC_ALL,$currentLocal);
		return $allAvailableLanguages;
	}
}
?>
