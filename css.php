<?php

	require("src/php/CssConcat.php");
	
	$q = $_GET['q'];
	$f = isset($_GET['folder']) ? $_GET['folder'] : null;
	
	echo CssConcat::process_route($q, $f);

?>