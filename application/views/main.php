<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">	
	<head>		
		<title>Cremoria</title>
		<?php echo html::stylesheet($css)?>
        <?php echo html::script($js)?>
	</head>	
	<body>
		<div id="root">			
			<div id="banner"></div>
			<div id="infobar">
				<?php echo $info ?>
			</div>				
			<div id="data">				
				<div id="navi">
					<?php echo $navi ?>
				</div>				
				<div id="content">
					<?php echo $content ?>
				</div>				
				<br class="clear" />
			</div>			
			<div id="bottom"></div>		
		</div>
	</body>
</html>