<?php
if (isset($_POST['pilot']) && $_POST['pilot'] != "")
{
	header('Location: '.$globalURL.'/pilot/'.$_POST['pilot']);
} else {
	header('Location: '.$globalURL);
}
?>