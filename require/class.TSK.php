<?php
class TSK {
	public function parse_xml($file) {
		$xml = simplexml_load_file($file);
		if ($xml !== false) {
			return json_decode(json_encode($xml), 1);
		}
	}
}
?>