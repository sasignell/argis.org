<link rel="stylesheet" type="text/css" href="http://www.aprgis.org:8080/geoserver/www/aprgis/theme/app/geoexplorer.css" />
<style type="text/css">
table
{
border-collapse:collapse;
font:normal 12px verdana;
}
table, td, th
{
border:1px solid gray;
}
</style>
<p><font size="4"><b>Download Center</b></font></br>
</br>
	<font size="3" color="red"><b>THIS SITE IS NO LONGER BEING ACTIVELY MAINTAINED OR MANAGED: NO DATA UPDATES ARE PLANNED UNTIL FUNDING IS PROCURED.</br></br>Use our WEB MAP SERVICE (WMS) to deliver ARGIS layers directly into desktop GIS applications such as ArcGIS, Google Earth, uDig or QGIS. </b></font></br>  </br> Our WMS URL (<b>http://aprgis.org:8080/geoserver/ows?service=wms&version=1.1.1&request=GetCapabilities&</b>) will allow you to connect to the menu of the ARGIS layers shown on our interactive map.   WMS layers allow you to click on map features to extract attribute information, but you cannot view the entire attribute table at once.  For more information, including how to add our WMS to ArcMap, visit this <a href="http://aprgis.org/index.php?post=use-argis-wms-service-to-add-layers-to-googleearth-arcgis-others" target="_BLANK">blog post</a></p>

</br>
	<div class="center">
	<table cellpadding="2" cellspacing="2" style="text-align: center; margin-left: auto; margin-right: auto;">
			<thead>
				<tr style="background:#eeeeee;">
					<th>DATA SET</th>
					<th>ORIGINATOR</th>
					<th>CONTACT</th>
					<th>LAST UPDATED</th>
                                        <th>UPDATE FREQUENCY</th>
					<th>INFO</th>
					<th>PREVIEW</th>
					<th>SHAPEFILE*</th>
					<th>KMZ**</th>
				 </tr>
			</thead>
			<tbody>
		<?php
	        include("dbinfo.php");

		// Opens a connection to a mySQL server
		$connection=pg_connect ("dbname=$database user=$user password=$password host=$host port=$port");
		if (!$connection) {
		  die("Not connected : " . pg_error());
		}

		// Search the rows in the markers table
		$query1 = "SELECT * FROM argis.metadata WHERE download=TRUE AND datacenter=TRUE ORDER BY title_of_dataset ASC";
		$query2 = "SELECT * FROM argis.metadata WHERE download=FALSE AND datacenter=TRUE ORDER BY title_of_dataset ASC";

		$result1 = pg_exec($connection, $query1);
		$result2 = pg_exec($connection, $query2);

		if (!$result1) {printf ("ERROR"); exit;}
		if (!$result2) {printf ("ERROR"); exit;}

		// Iterate through the rows, adding XML nodes for each
		while ($row = @pg_fetch_assoc($result1)){
		echo 	"<tr><td>" . $row['title_of_dataset'] . "</td>
				<td>" . $row['originator'] . "</td>
			        <td><a href=mailto:'" . $row['contact_email'] . "' target='_blank'> " . $row['Contact Person'] . " </a></td>
				<td>" . $row['last_updated'] . "</td>
				<td>" . $row['updatefreq'] . "</td>
                                <td><a style='color:black' href='" . $row['online_linkage'] . "' target='_blank'>Metadata</a></td>
				<td><a style='color:black' href='http://www.aprgis.org:8080/geoserver/wms?service=WMS&version=1.1.0&request=GetMap&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&styles=&bbox=474218.375,4766747.0,635668.0,4970120.0&width=406&height=512&srs=EPSG:26918&format=application/openlayers' target='_blank'>Preview</a></td>
				<td><a style='color:black' href='http://www.aprgis.org:8080/geoserver/wfs?request=GetFeature&amp;version=1.1.0&amp;typeName=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&amp;outputFormat=SHAPE-ZIP' target='_blank'>Shapefile</a></td>
				<td><a style='color:black' href='http://www.aprgis.org:8080/geoserver/wms/kml?mode=download&&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "' target='_blank'>KMZ</a></td></tr>";}

		while ($row = @pg_fetch_assoc($result2)){
		echo 	"<tr><td>" . $row['title_of_dataset'] . "</td>
				<td>" . $row['originator'] . "</td>
				 <td><a href=mailto:'" . $row['contact_email'] . "' target='_blank'> " . $row['Contact Person'] . " </a></td>
				<td>" . $row['last_updated'] . "</td>
				<td>" . $row['updatefreq'] . "</td>
                                <td><a style='color:black' href='" . $row['online_linkage'] . "' target='_blank'>Metadata</a></td>
				<td><a style='color:black' href='http://www.aprgis.org:8080/geoserver/wms?service=WMS&version=1.1.0&request=GetMap&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&styles=&bbox=474218.375,4766747.0,635668.0,4970120.0&width=406&height=512&srs=EPSG:26918&format=application/openlayers' target='_blank'>Preview</a></td>
				<td>Not Available</td>
				<td>Not Available</td></tr>";
		}
		?>
	</tbody>
		</table>

	</div>
	</br>
	</br>
	<i> *This option will give you the raw data table delivered in ESRI shapefile format, consumable by most desktop GIS programs including ArcGIS, uDig and QGIS. Because shapefiles contain both geographic and attribute information, complex sorting, filtering, query and geoprocessing operations are possible.  Consequently, shapefiles are much larger than KMZ files.</p>
	<br>
 **This option will download a .kmz (Google Earth) file to your computer for viewing in Google Earth or importing into another GIS program.  KMZ files are similar to WMS layers in terms of functionality.</i></p>
	
