<?php
	if(!empty($error)){
		echo error::display($error);	
	}
	$goldIncDay 	= $goldInc * 4 * 24;
	$woodIncDay 	= $woodInc * 4 * 24;
	$stoneIncDay 	= $stoneInc * 4 * 24;
	$ironIncDay 	= $ironInc * 4 * 24;
	
	echo "<table class=\"table ecoTable \">
		  <tbody>
              <tr><th colspan=\"3\">Einkommen</th></tr>
		  	  <tr>
		  	  	  <td></td>
		  	  	  <td>".kohana::lang('general.ecoPerTick')."</td>
		  	  	  <td>".kohana::lang('general.ecoPerDay')."</td>
		  	  </tr>
			  <tr>
				  <td>".html::image('images/gold.gif')."</td>
				  <td>$goldInc</td>
				  <td>$goldIncDay</td>
			  </tr>
			  <tr>
				  <td>".html::image('images/wood.gif')."</td>
				  <td>$woodInc</td>
				  <td>$woodIncDay</td>
			  </tr>
			  <tr>
				  <td>".html::image('images/stone.gif')."</td>
				  <td>$stoneInc</td>
				  <td>$stoneIncDay</td>
			  </tr>
			  <tr>
				  <td>".html::image('images/iron.gif')."</td>
				  <td>$ironInc</td>
				  <td>$ironIncDay</td>
			  </tr>
		  </tbody> 
		  </table>";
	
	$stonePart = Kohana::config('crmGame.ironToStone').html::image('images/stone.gif');
	$woodPart  = Kohana::config('crmGame.ironToWood').html::image('images/wood.gif');
	$iron	   = html::image('images/iron.gif');
	
	echo form::open('economy/adjustRate', array('id'=>'ironForm', 'name'=>'ironForm')).' <br> ';
	echo "<p>
			 1 $iron = $stonePart + $woodPart <br/>
			Maximale $iron Produktion: $ironIncMax
		  </p>";	
	echo form::label('ironRate', 'UmwandlungsRate:');
	echo form::input('ironRate', $ironRate).'%'		.' <br> ';
	echo form::submit('submit', 'Change')			.' <br> ';
	echo form::close();


?>
