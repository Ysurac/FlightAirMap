<?php
if (isset($_POST['owner']) && $_POST['owner'] != "")
{
	header('Location: '.$globalURL.'/owner/'.$_POST['owner']);
} else {
	header('Location: '.$globalURL);
}
?>