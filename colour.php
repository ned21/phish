<?php

function colourTable($oldV, $oldColour) {
	$v = $oldV;
	echo "<table style=\"background: #fff; border: 2px black solid; width:90%;\"><tr><td>\n";
		echo "<table style=\"border: 1px black solid; width: 80%;\" cellspacing=\"0\">\n";
		echo "<tr><td colspan=\"20\" style=\"text-align: center; font-weight: bold;\">Choose colour and saturation</td></tr>\n";
			for ($s=0; $s<1; $s += 0.1) {
				echo "<tr class=\"menu\">";
				for ($h=0; $h<360; $h += 18) {
					$colour = HSVtoRGB($h, $s, $v);
					if ($colour == $oldColour)
						echo "<td style=\"background: #$colour; color: #$colour; border: 1px red solid; width:5%\">\n";
					else
						echo "<td style=\"background: #$colour; color: #$colour; width:5%\">\n";
					echo "  <a href=\"index.php?".htmlspecialchars("light=$v&colour=$colour&action=colour")."\" style=\"color: #$colour; \">_</a>\n";

				}
			}
		echo "</table>";
	echo "<td>";
		echo "<table style=\"border: 1px black solid; width:50%;\" cellspacing=\"0\">";
		echo "<tr><td style=\"text-align: center; font-weight: bold;\">Lightness</td></tr>";
			$s = 0;
			for ($v=1; $v>=0; $v -= 0.1) {
				$v = round($v, 1);
				$colour = HSVtoRGB($h, $s, $v);
				if ($v == $oldV)
					echo "<tr style=\"background: #$colour; color: #$colour;\" class=\"menu\"><td style=\"border: 1px red solid; width:100%\">\n";
				else
					echo "<tr style=\"background: #$colour; color: #$colour;\" class=\"menu\"><td style=\"width:100%\">\n";
				echo "  <a href=\"index.php?".htmlspecialchars("light=$v&action=colour")."\" style=\"display: block;color: #$colour; \">_</a>\n";
			}
		echo "</table>\n";
	echo "</td></tr>\n";
	echo "</table>\n";
	if (isset($oldColour))
		echo "<p style=\"text-align: center; font-weight: bold;\">Current colour #$oldColour</p>\n".
			 "<p style=\"color: #$oldColour; background-color: #$oldColour;\">$oldColour</p>\n";
}

function dhex($c) {
	//used for padding 1-digit hex values to 2-digits
	$c = dechex($c);
	if (strlen($c) == 1)
		$c = "0".$c;
	return $c;
}

function HSVtoRGB($h, $s, $v ) {

	if( $s == 0 ) {
		//grey
		$r = $g = $b = $v;
	} else {
		$h /= 60;
		$i = floor( $h );
		$f = $h - $i;
		$p = $v * ( 1 - $s );
		$q = $v * ( 1 - $s * $f );
		$t = $v * ( 1 - $s * ( 1 - $f ) );

		switch( $i ) {
			case 0:
				$r = $v;
				$g = $t;
				$b = $p;
				break;
			case 1:
				$r = $q;
				$g = $v;
				$b = $p;
				break;
			case 2:
				$r = $p;
				$g = $v;
				$b = $t;
				break;
			case 3:
				$r = $p;
				$g = $q;
				$b = $v;
				break;
			case 4:
				$r = $t;
				$g = $p;
				$b = $v;
				break;
			case 5:
				$r = $v;
				$g = $p;
				$b = $q;
				break;
		}
	}
	$r *= 255;
	$g *= 255;
	$b *= 255;
	return dhex($r).dhex($g).dhex($b);
}
?>