<?php
require_once(dirname(__FILE__).'/class.Common.php');
class TSK {
	public function parse_xml($url) {
		$Common = new Common();
		$filedata = $Common->getData($url,'get','','','','','','',true);
		if ($filedata != '' && $filedata !== false) {
			$xml = simplexml_load_string($filedata);
			if ($xml !== false) {
				return json_decode(json_encode($xml), 1);
			}
		}
		return array();
	}
}
?>