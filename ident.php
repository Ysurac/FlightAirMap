<?php
if (isset($_POST['ident']) && $_POST['ident'] != "")
{
	header('Location: '.$globalURL.'/ident/'.$_POST['ident']);
} else {
	header('Location: '.$globalURL);
}
?>