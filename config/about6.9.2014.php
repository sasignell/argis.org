<link rel="stylesheet" type="text/css" href="http://www.aprgis.org:8080/geoserver/www/aprgis/theme/app/geoexplorer.css" />
<p><b><font size="4">About ARGIS</font></b></p>
</br><b> ATTENTION: THIS SITE IS NO LONGER BEING ACTIVELY MAINTAINED OR MANAGED: NO DATA UPDATES ARE PLANNED UNTIL FUNDING IS PROCURED.</b></br></br><p>The Adirondack Regional Geographic Information System (ARGIS) is a regional spatial data portal designed to serve authoritative regional data.</br> Created by Steve Signell (<a href="http://frontierspatial.com" target="_blank">Frontier Spatial, LLC<a/>, <a href="http://aprgis.org" target="_blank">APR-GIS Consortium</a>) and Bryan McBride (<a href="http://geoserving.net" target="_blank">geoserving.net</a>), ARGIS is funded by a grant from the New York State Department of Environmental Conservation and contributions from data providers. ARGIS is currently serving data from:</p>
	<p><ul class="ulCls">
		<?php  
	include("dbinfo.php");

		// Opens a connection to a mySQL server
		$connection=pg_connect ("dbname=$database user=$user password=$password host=$host port=$port");
		if (!$connection) {
		  die("Not connected : " . pg_error());
		}

		// Search the rows in the markers table
		$query = "SELECT DISTINCT argis.metadata.originator FROM metadata";

		$result = pg_exec($connection, $query);

		if (!$result) {printf ("ERROR"); exit;}

		// Iterate through the rows, adding XML nodes for each
		while ($row = @pg_fetch_assoc($result)){
		echo "<li>" . $row['originator'] . "</li>";
		}
		?>
	</ul></p>
	<p><b>For more information on individual datasets, click on the "Download Center" tab in the center panel.</b></p>
        <br/>
	<p>To comment on the site, ask a question, or get more information about sharing your data via ARGIS, contact Steve Signell at <a href="mailto:ssignell@esf.edu">ssignell@esf.edu</a></p>

	<br/>
	<p><b>ARGIS Architecture:</b></p>
	<p>ARGIS is built on a LINUX server using free, open-source software:</p>
	<div class="center"><img style="width: 600px; height: 186px;" src="http://www.aprgis.org/argis/img/technology.png">
	</div>
	</div>
