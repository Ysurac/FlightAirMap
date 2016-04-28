<?php

if (!function_exists("gettext")) {
        function _($text) {
                return $text;
        }
} else {
/*
        $Language = new Language();
        setlocale(LC_MESSAGES, 'fr_FR');
        bindtextdomain("fam", "./locale/nocache");
        bind_textdomain_codeset("fam", 'UTF-8');
        $results = bindtextdomain("fam", "./locale");
        $results = textdomain("fam");
*/
}

class Language {
	/**
	* Returns list of available locales
	*
	* @return array
	 */
	public function listLocaleDir()
	{
		$result = array('en');
		if (!is_dir('./locale')) {
			return $result;
		}
		$handle = @opendir('./locale');
		if ($handle === false) return $result;
		while (false !== ($file = readdir($handle))) {
			$path = './locale'.'/'.$file.'/LC_MESSAGES/fam.mo';
			if ($file != "." && $file != ".." && @file_exists($path)) {
				$result[] = $file;
			}
		}
		closedir($handle);
		return $result;
	}
}
?>