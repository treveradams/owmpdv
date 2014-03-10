<?PHP
/* (c) 2013-2014 Trever L. Adams
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.

 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// This is a simple translation of a function found at:
// http://en.wikipedia.org/wiki/Maidenhead_Locator_System
// (c) 2012 Chris Ruvolo.  Licensed under a 2-clause BSD license.
function latlon2gs($lat, $lon)
{
	$grid = "";
        $five60 = doubleval(5)/doubleval(60);
 
	$lon = $lon + 180;
	$lat = $lat + 90;
 
	$grid .= chr(ord('A') + intval($lon / 20));
	$grid .= chr(ord('A') + intval($lat / 10));
	$grid .= chr(ord('0') + intval(($lon % 20)/2));
	$grid .= chr(ord('0') + intval(($lat % 10)/1));
	$grid .= chr(ord('a') + intval(($lon - (intval($lon/2)*2)) / ($five60)));
	$grid .= chr(ord('a') + intval(($lat - (intval($lat/1)*1)) / (2.5/60)));
	return $grid;
}

function add_blocks($top, $left, $bottom, $right)
{
	$gs_used = array();
        $lon_tick = doubleval(5)/doubleval(60);
//	print "Lon tick $lon_tick\n";
	$lat_tick = 2.5/60;
//	print "Lat tick $lat_tick\n";

	$lat_current = $top;

	if($bottom > $top) $lat_direction = 1;
	else $lat_direction = 0;

	if($right > $left) $lon_direction = 1;
	else $lon_direction = 0;

	do {
		$lon_current = $left;
		do {
//			print "Lon: $lon_current $right\n";
			$gs_used[] = latlon2gs($lat_current, $lon_current);
			if(abs($lon_current - $right) > $lon_tick)
			{
				if($lon_direction) $lon_current = $lon_current + $lon_tick;
				else $lon_current = $lon_current - $lon_tick;
//				print "Lon (inner): $lon_current $right\n";
			}
		} while(abs($lon_current - $right) > $lon_tick);
		$gs_used = array_unique($gs_used);
		$gs_used[] = latlon2gs($lat_current, $lon_current);
//		print "Lat: $lat_current $bottom\n";
		if(abs($lat_current - $bottom) > $lat_tick)
		{

			if($lat_direction) $lat_current += $lat_tick;
			else $lat_current -= $lat_tick;
//			print "Lat (inner): $lat_current $bottom\n";
		}
	} while(abs($lat_current - $bottom) > $lat_tick);
	return $gs_used;
}

$gs_used = array();

$right = doubleval($_GET["r"]);
$left = doubleval($_GET["l"]);
$top = doubleval($_GET["t"]);
$bottom = doubleval($_GET["b"]);
$zoom = intval($_GET["z"]);
$members = array();
$name="";
$lat="";
$long="";
$data="";

$gs_used = add_blocks($top, $left, $bottom, $right);

$gs_used[] = latlon2gs($top, $left);
$gs_used[] = latlon2gs($top, $right);
$gs_used[] = latlon2gs($bottom, $left);
$gs_used[] = latlon2gs($bottom, $right);

$gs_used = array_unique($gs_used);

print "lat\tlon\ticon\ticonSize\ticonOffset\ttitle\tdescription\tpopupSize\n";
foreach ($gs_used as &$value)
{
   $cur_file = "gsdb/" . $value . ".csv";
   if(file_exists($cur_file))
      $file = fopen($cur_file, "r");
   else
      continue;
   if(is_bool($file)) continue;
// We need to trash one line of text because it is CSV header
   fgets($file);

   while (!feof($file))
   {
      $data = fgets($file);
      if(strlen($data) > 2)
      {
         list($name, $lat, $long) = preg_split('/[\s]+/', $data);
         $lat = doubleval($lat);
         $long = doubleval($long);
         if($lat >= $bottom && $lat <= $top && $long >= $left && $long <= $right)
         {
             if($zoom<=11) print "$lat\t$long\tOl_icon_red_micro_poi.png\t1,1\t0,0\t\t\t200,80\n";
             else if($zoom<=14) print "$lat\t$long\tOl_icon_red_small_poi.png\t2,2\t1,1\t\t\t200,80\n";
             else print "$lat\t$long\tOl_icon_red_example.png\t16,16\t8,8\t$name\t$name\t200,80\n";
         }
      }
   }
   fclose($file);
}

?>
