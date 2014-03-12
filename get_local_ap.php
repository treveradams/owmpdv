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

function distance($lat1, $lon1, $lat2, $lon2)
{
   $R = 20902231.64; // circumference of the earth in feet
   $lat1 = deg2rad($lat1);
   $lat2 = deg2rad($lat2);

   $d = acos(sin($lat1) * sin($lat2) + 
                  cos($lat1) * cos($lat2) *
                  cos($lon2 - $lon1)) * $R;
return $d;
}

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

// This is a simple translation of a function found at:
// http://en.wikipedia.org/wiki/Maidenhead_Locator_System
// (c) 2012 Chris Ruvolo.  Licensed under a 2-clause BSD license.
function gs2latlon($grid)
{
    $formatter=0;
    $five60 = doubleval(5)/doubleval(60);

    $lon = (ord($grid[0]) - ord('A')) * 20 - 180;
    $lat = (ord($grid[1]) - ord('A')) * 10 - 90;
    $lon += (ord($grid[2]) - ord('0')) * 2;
    $lat += (ord($grid[3]) - ord('0')) * 1;

    if (strlen($grid) >= 5) {
        # have subsquares
        $lon += (ord($grid[4]) - ord('A')) * $five60;
        $lat += (ord($grid[5]) - ord('A')) * 2.5/60;
        # move to center of subsquare
        $lon += 2.5/60;
        $lat += 1.25/60;
    } else {
        # move to center of square
        $lon += 1;
        $lat += 0.5;
    }

return array ($lat, $lon);
}

function add_blocks($top, $left, $bottom, $right)
{
    $gs_used = array();
    $lon_tick = doubleval(5)/doubleval(60);
//    print "Lon tick $lon_tick\n";
    $lat_tick = 2.5/60;
//    print "Lat tick $lat_tick\n";

    $lat_current = $top;

    if($bottom > $top) $lat_direction = 1;
    else $lat_direction = 0;

    if($right > $left) $lon_direction = 1;
    else $lon_direction = 0;

    do {
        $lon_current = $left;
        do {
//            print "Lon: $lon_current $right\n";
            $gs_used[] = latlon2gs($lat_current, $lon_current);
            if(abs($lon_current - $right) > $lon_tick)
            {
                if($lon_direction) $lon_current = $lon_current + $lon_tick;
                else $lon_current = $lon_current - $lon_tick;
//                print "Lon (inner): $lon_current $right\n";
            }
        } while(abs($lon_current - $right) > $lon_tick);
        $gs_used = array_unique($gs_used);
        $gs_used[] = latlon2gs($lat_current, $lon_current);
//        print "Lat: $lat_current $bottom\n";
        if(abs($lat_current - $bottom) > $lat_tick)
        {
            if($lat_direction) $lat_current += $lat_tick;
            else $lat_current -= $lat_tick;
//            print "Lat (inner): $lat_current $bottom\n";
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
if(isset($_GET["mzd"]))
    $min_zoom_display = intval($_GET["mzd"]);
else
    $min_zoom_display = 0;
if(isset($_GET["mzp"]))
    $max_zoom_prune = intval($_GET["mzp"]);
else
    $max_zoom_prune = 11;
if(isset($_GET["res"]))
    $res = intval($_GET["res"]);
else
    $res = 0;
$members = array();
$name="";
$lat="";
$long="";
$data="";
$half_long = 2.5/60;
$half_lat = 1.25/60;

$gs_used = add_blocks($top, $left, $bottom, $right);

$gs_used[] = latlon2gs($top, $left);
$gs_used[] = latlon2gs($top, $right);
$gs_used[] = latlon2gs($bottom, $left);
$gs_used[] = latlon2gs($bottom, $right);

$gs_used = array_unique($gs_used);

// Setup icons to use
if($res <= 2) {
    $icon_file = "Ol_icon_red_example.png";
    $icon_size = "16,16";
    $icon_mp = "8,8";
}
else if($res <= 9) {
    $icon_file = "Ol_icon_red_medium_poi.png";
    $icon_size = "8,8";
    $icon_mp = "4,4";
}
else if($res <= 16) {
    $icon_file = "Ol_icon_red_small_poi.png";
    $icon_size = "2,2";
    $icon_mp = "1,1";
}
else {
    $icon_file = "Ol_icon_red_micro_poi.png";
    $icon_size = "1,1";
    $icon_mp = "0,0";
}

print "lat\tlon\ticon\ticonSize\ticonOffset\ttitle\tdescription\tpopupSize\n";
if($min_zoom_display <= $zoom)
{
    $last_used = -1;
    if($max_zoom_prune > $zoom)
    {
        $example["long"] = doubleval(0);
        $example["lat"] = doubleval(0);
        $good = array_fill(0, 1000, $example);
    }
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

       if($max_zoom_prune < $zoom)
       {           
           while (!feof($file))
           {
               $data = fgets($file);
               if(strlen($data) > 2 && $data != False)
               {
                   list($name, $lat, $long) = preg_split('/[\s]+/', $data);
                   $lat = doubleval($lat);
                   $long = doubleval($long);
                   if($lat >= $bottom && $lat <= $top && $long >= $left && $long <= $right)
                   {
                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat, $long, $icon_file, $icon_size, $icon_mp);
                   }
               }
           }
           fclose($file);
       }
       else {
           if($res >= 25000)
           {
               list ($lat, $long) = gs2latlon($value);
               $lat = doubleval($lat);
               $long = doubleval($long);
               $lat_plus = doubleval($lat + $half_lat);
               $lat_less = doubleval($lat - $half_lat);
               $long_plus = doubleval($long + $half_long);
               $long_less = doubleval($long - $half_long);

               if($lat >= $bottom && $lat <= $top && $long >= $left && $long <= $right) {
                   $temp["lat"] = $lat;
                   $temp["long"] = $long;
//                   printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat, $long, $icon_file, $icon_size, $icon_mp);
               }
               else if($lat_plus >= $bottom && $lat_plus <= $top) {
                   if($long_plus >= $left && $long_plus <= $right)
                   {
                       $temp["lat"] = $lat_plus;
                       $temp["long"] = $long_plus;
//                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat_plus, $long_plus, $icon_file, $icon_size, $icon_mp);
                   }
                   else
                   {
                       $temp["lat"] = $lat_plus;
                       $temp["long"] = $long_less;
//                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat_plus, $long_less, $icon_file, $icon_size, $icon_mp);
                   }
               } else {
                   if($long_plus >= $left && $long_plus <= $right)
                   {
                       $temp["lat"] = $lat_less;
                       $temp["long"] = $long_plus;
//                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat_less, $long_plus, $icon_file, $icon_size, $icon_mp);
                   }
                   else
                   {
                       $temp["lat"] = $lat_less;
                       $temp["long"] = $long_less;
//                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $lat_less, $long_less, $icon_file, $icon_size, $icon_mp);
                   }
               }
               if($last_used == -1)
               {
                   $last_used++;
                   $good[$last_used]["lat"] = $lat;
                   $good[$last_used]["long"] = $long;
               }
               else {
                   $broken = 0;
                   for($i=0; $i<=$last_used; $i++)
                   {
                       if(distance($good[$i]["lat"], $good[$i]["long"], $temp["lat"], $temp["long"]) < $res) { $broken = 1; break; }
                   }
                   if(!$broken) {
                       $last_used++;
                       $good[$last_used]["lat"] = $lat;
                       $good[$last_used]["long"] = $long;
                   }
               }
               if($last_used == 1000)
               {
                   for($i=0; $i<=$last_used; $i++)
                   {
                       printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $good[$i]["lat"], $good[$i]["long"], $icon_file, $icon_size, $icon_mp);
                   }
                   $last_used = -1;
               }
           }
           else {
               $last_used = -1;
               while (!feof($file))
               {
                   $data = fgets($file);
                   if(strlen($data) > 2 && $data != False)
                   {
                       list($name, $lat, $long) = preg_split('/[\s]+/', $data);
                       $lat = doubleval($lat);
                       $long = doubleval($long);
                       if($last_used == -1)
                       {
                           $last_used++;
                           $good[$last_used]["lat"] = $lat;
                           $good[$last_used]["long"] = $long;
                       }
                       else if($lat >= $bottom && $lat <= $top && $long >= $left && $long <= $right)
                       {
                           $broken = 0;
                           for($i=0; $i<=$last_used; $i++)
                           {
                               if(distance($good[$i]["lat"], $good[$i]["long"], $lat, $long) < $res) { $broken = 1; break; }
                           }
                           if(!$broken) {
                               $last_used++;
                               $good[$last_used]["lat"] = $lat;
                               $good[$last_used]["long"] = $long;
                           }
                       }
                   }
                   if($last_used == 1000)
                   {
                       for($i=0; $i<=$last_used; $i++)
                       {
                           printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $good[$i]["lat"], $good[$i]["long"], $icon_file, $icon_size, $icon_mp);
                       }
                       $last_used = -1;
                   }
               }
               for($i=0; $i<=$last_used; $i++)
               {
                   printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $good[$i]["lat"], $good[$i]["long"], $icon_file, $icon_size, $icon_mp);
                   $last_used = -1;
               }
           }
           fclose($file);
       }
    }
    if($last_used >= 0)
    {
        for($i=0; $i<=$last_used; $i++)
        {
            printf("%lf\t%lf\t%s\t%s\t%s\t\t\t1,1\n", $good[$i]["lat"], $good[$i]["long"], $icon_file, $icon_size, $icon_mp);
        }
    }
}

?>
