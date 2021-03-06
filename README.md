Open WLAN Map Public Data Visualizer (owmpdv)
============================================
So, you are an Open WLAN Map contributor. You drive or walk around
capturing WiFi MAC addresses with associated GPS data. You then upload
it to the public database. You stop doing it for a while, or live in a
large or confusing area. These leads to the questions of what have you done?
What have others done?

That is what this collection of HTML, JavaScript, and PHP accomplish.
Place all of the following within a public HTML directory on a web-server
with PHP capability. Then access it from a phone or other device that can
provide geolocation data to the JavaScript in index.html. The map will first
go to a location in the Atlantic Ocean (it has to scroll to load the Open WLAN
data). It will then jump to the location. You can scroll around on the map.

You will need to use the owmdb2gsdb.py script by myself to convert the Open
WLAN Map public database into smaller, easier to use, files. Copy the gsdb directory
(not just the files) into the same directory as the HTML and code found in this project.

In index.html, you will need to edit the following string to be suitable for your installation:
'var url = "http://web_server_fqdn/path_to_owmpdv/get_local_ap.php?";'

Other variables of interest in index.html are minimum_zoom_display and maximum_zoom_prune.

Interpretation Caveats
----------------------
* Understand, there is no time/date stamps in the public database, so you can only
see what hasn't been done.
* You cannot see what is old and needs to be redone.
* If there are streets without known WiFi APs, it may not be because the street hasn't
been done, but that there are none detected or present.

Usage Caveats
-------------
* Do NOT zoom out too far. You will likely cause what appears to be an infinite loop in
your browser. This is actually just JavaScript being slow with the Open Layers code and
with the MakerTile code that extends it. (This isn't as critical as it used to be!)
* You can scroll around the same way you would on a normal web map.

If you are wanting to change icons, etc. I believe the code is straightforward. If in doubt,
see documentation at http://wiki.openstreetmap.org/wiki/OpenLayers_Dynamic_POI.

Licensing Caveats
-----------------
Please, be aware, many of the JavaScript files are under a different F/OSS license.
MarkerTile.js and MarkerGrid.js have been fixed to work with newer OpenLayer.js
versions than the original.

The icons found here were part of the example at http://sandbox.freemap.sk/dynamic_poi/
on which some of this project were based. Those icons are licensed as CC-BY-SA 2.0.
