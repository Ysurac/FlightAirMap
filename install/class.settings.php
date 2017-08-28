<?php
require_once(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');

class settings {

	/*
	* This function is used to modify a setting in settings.php file
	* @param Array list of settings and their values
	*/
	public static function modify_settings($settings) {
		$Common = new Common();
		$settings_filename = '../require/settings.php';
		$content = file_get_contents($settings_filename);
		$fh = fopen($settings_filename,'w');
		foreach ($settings as $settingname => $value) {
			if ($value == 'TRUE' || $value == 'FALSE') {
				$pattern = '/\R\$'.$settingname." = ".'(TRUE|FALSE)'."/";
				$replace = "\n".'\$'.$settingname." = ".$value."";
			} elseif (is_array($value)) {
				$pattern = '/\R\$'.$settingname." = array\(".'(.*)'."\)/";
				if ($Common->isAssoc($value)) {
					foreach ($value as $key => $data) {
						if (!isset($array_value)) {
							if (is_array($data)) {
								foreach ($data as $keya => $dataa) {
									if (is_array($dataa) && !empty($dataa)) {
										foreach ($dataa as $dataaa) {
											if (!isset($dataarraya)) $dataarraya = $dataaa;
											else $dataarraya .= "','".$dataaa;
										}
										$dataarray = "array('".$keya."' => array('".$dataarraya."'))";
										unset($dataarraya);
									} else {
										if (!isset($dataarray)) $dataarray = "'".$dataa."'";
										else $dataarray .= ",'".$dataa."'";
									}
								}
								$array_value = "'".$key."' => ".$dataarray;
								unset($dataarray);
							} else {
								if ($data == 'TRUE' || $data == 'FALSE') {
									$array_value = "'".$key."' => ".$data."";
								} else {
									$array_value = "'".$key."' => '".$data."'";
								}
							}
						} else {
							if (is_array($data)) {
								foreach ($data as $keya => $dataa) {
									if (is_array($dataa) && !empty($dataa)) {
										foreach ($dataa as $dataaa) {
											if (!isset($dataarraya)) $dataarraya = $dataaa;
											else $dataarraya .= "','".$dataaa;
										}
										$dataarray = "array('".$keya."' => array('".$dataarraya."'))";
										unset($dataarraya);
									} else {
										if (!isset($dataarray)) $dataarray = "'".$dataa."'";
										else $dataarray .= "','".$dataa."'";
									}
								}
								$array_value .= ",'".$key."' => ".$dataarray;
								unset($dataarray);
							} else {
								if ($data == 'TRUE' || $data == 'FALSE') {
									$array_value .= ",'".$key."' => ".$data."";
								} else {
									$array_value .= ",'".$key."' => '".$data."'";
								}
							}
						}
					}
				} else {
					foreach ($value as $key => $data) {
						if (is_array($data) && $Common->isAssoc($data)) {
							foreach ($data as $keyd => $datad) {
								if (!isset($arrayd_value)) {
									if ($datad == 'TRUE' || $datad == 'FALSE') {
										$arrayd_value = "'".$keyd."' => ".$datad."";
									} else {
										$arrayd_value = "'".$keyd."' => '".$datad."'";
									}
								} else {
									if ($datad == 'TRUE' || $datad == 'FALSE') {
										$arrayd_value .= ",'".$keyd."' => ".$datad."";
									} else {
										$arrayd_value .= ",'".$keyd."' => '".$datad."'";
									}
								}
							}
							if (!isset($array_value)) {
								if (!isset($arrayd_value)) $arrayd_value = '';
								//$array_value = "'".$key."' => array(".$arrayd_value.")";
								$array_value = "array(".$arrayd_value.")";
							} elseif (isset($arrayd_value)) {
								//$array_value .= ",'".$key."' => array(".$arrayd_value.")";
								$array_value .= ",array(".$arrayd_value.")";
							}
							unset($arrayd_value);
						} else {
							if (!isset($array_value)) {
								$array_value = "'".$data."'";
							} else {
								$array_value .= ",'".$data."'";
							}
						}
					}
				}
				if (!isset($array_value)) $array_value = '';
				$replace = "\n".'\$'.$settingname." = array(".$array_value.")";
				unset($array_value);
			} else {
				$pattern = '/\R\$'.$settingname." = '".'(.*)'."'/";
				$replace = "\n".'\$'.$settingname." = '".$value."'";
			}
			$rep_cnt = 0;
			$content = preg_replace($pattern,$replace,$content,1,$rep_cnt);
			
			/// If setting was a string and is now an array
			if ($rep_cnt === 0 && is_array($value)) {
				$pattern = '/\R\$'.$settingname." = '".'(.*)'."'/";
				$content = preg_replace($pattern,$replace,$content,1,$rep_cnt);
			}
			
			// If setting is not in settings.php (for update)
			if ($rep_cnt === 0) {
				$content = preg_replace('/\?>/',$replace.";\n?>",$content,1,$rep_cnt);
			}

		}
		fwrite($fh,$content);
		fclose($fh);
	}

	/*
	* This function is used to comment a setting in settings.php file
	* @param Array list of settings to comment
	*/
	public static function comment_settings($settings) {
		$Common = new Common();
		$settings_filename = '../require/settings.php';
		$content = file_get_contents($settings_filename);
		$fh = fopen($settings_filename,'w');
		foreach ($settings as $settingname) {
			$pattern = '/\R\$'.$settingname." = /";
			$replace = '//$'.$settingname." = ";
			$content = preg_replace($pattern,$replace,$content);
		}
		fwrite($fh,$content);
		fclose($fh);
	}
}

//settings::comment_settings(array('globalSBS1Hosts'));
?>