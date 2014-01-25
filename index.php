<?php

session_name('linkedin');
session_start();

require_once('LinkedIn.php');

$html = <<< heredoc
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="utf-8">
	<!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>LinkedIn &middot; Authentication</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">
	<link href="http://app.molepocket.com/css/styless.css" rel="stylesheet">
</head>
<body>
<style type="text/css">
	img {margin-right: 15px}
	h1 {margin: 0px 0 5px 0; font-size: 2em; font-weight: 500; display: inline-block;}
	.clear {clear: both; margin-bottom: 15px;}
	hr.thin {margin: 0}
	.jelen {font-size: 3em; margin-top: .2em}
</style>
	<div class="container" style="width: 500px; margin-top: 50px;">
heredoc;

// Form has been submitted
if (isset($_GET['authenticate']) && ($_GET['authenticate'] === 'initiate'))
{
	// Next five loc is the most important part of the script
	$in = new LinkedIn;
	$in->setApiKey('YOUR_API_KEY')
		->setApiSecret('YOUR_API_SECRET')
		->setRedirectUri('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . '?authenticate=initiate');
	$user = $in->run();

	echo $html;

	if (!$user)
	{
		?>
		<p><span class="label label-danger">Error</span> (např.: 401 Unauthorized ap.)</p>
		<?php
		session_destroy();
	}
	else
	{
		?>
		<!-- USER -->
		<img src="<?php echo $user->pictureUrl;?>" class="img-thumbnail pull-left">
		<h1><?php echo $user->firstName . ' ' . $user->lastName;?></h1> <a href="mailto:<?php echo $user->emailAddress;?>?subject=Hi there!" target="_blank"><i class="envelope"></i></a>
		<hr class="thin">
		<span class="lead"><?php echo $user->headline;?></span><br>
		<i class="location"></i> <span class=""><?php echo $user->location->name;?></span><br>
		<div class="clear"></div>
		<?php
	}
		echo '<pre>';
		var_dump($user);
		echo '</pre>';
		?>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>" class="btn btn-success btn-lg btn-block"><i class="chevron-left"></i> Zpět</a>
		<?php
}
else
{
	echo $html;
?>
		<!-- FORM -->
		<form id="frm-linkedinConnectForm" action="<?php echo $_SERVER['PHP_SELF'];?>" method="get">
			<input type="hidden" id="authenticate" name="authenticate" value="initiate" />
			<button type="submit" value="Přesměruj na LinkedIn" class="btn btn-primary btn-lg btn-block">Přesměruj na Linked<i class="linkedin"></i></button>
		</form>
<?php
}
?>
		<i class="jelen pull-right" title="&copy; 2014"></i>
	</div>
</body>
</html>