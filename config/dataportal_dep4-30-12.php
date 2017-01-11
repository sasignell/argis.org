<link rel="stylesheet" type="text/css" href="http://www.argis.org:8080/geoserver/www/aprgis/theme/app/geoexplorer.css" />
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
There are several ways to consume ARGIS data:
</br></br>	
<p><font size="2"><b>WEB MAP SERVICE (WMS)</b></font></span> </br>
Our Web Map Service delivers layers directly into GIS applications such as ArcGIS, Google Earth, uDig or QGIS.  WMS layers allow you to click on map features to extract attribute information, but you cannot view the entire attribute table at once.</br>
Use this URL, <b>http://argis.org:8080/geoserver/ows?service=wms&version=1.1.1&request=GetCapabilities&</b>, to connect to a menu of the ARGIS layers shown on our interactive map.</br>For more information, including how to add our WMS to ArcMap, visit this <a href="http://argis.org/?p=163">blog post</a></p>
<br> 
<p><font size="2"><b>SHAPEFILE DOWNLOAD</b></font></span> </br>
 This option will give you the raw data table delivered in ESRI shapefile format, consumable by most desktop GIS programs including ArcGIS, uDig and QGIS. Because shapefiles contain both geographic and attribute information, complex sorting, filtering, query and geoprocessing operations are possible.  Consequently, shapefiles are much larger than KMZ files.</p>
<br>
<p><font size="2"><b>KMZ DOWNLOAD</b></font></span> </br>
 This option will download a .kmz (Google Earth) file to your computer for viewing in Google Earth or importing into another GIS program.  KMZ files are similar to WMS layers in terms of functionality.</p>
</br>
</br>
<p>Many ARGIS layers are <b>updated</b> frequently, so check back often for the latest data.  
</br>
</br>
The <b>metadata</b> link will give you more information on individual datasets. 
</br>
</br>
The <b>preview</b> link will let you view and interact with data before you download.
</br>
</br>
	<div class="center">
	<table cellpadding="2" cellspacing="2" style="text-align: center; margin-left: auto; margin-right: auto;">
			<thead>
				<tr style="background:#eeeeee;">
					<th>DATA SET</th>
					<th>ORIGINATOR</th>
					<th>CONTACT</th>
					<th>LAST UPDATED</th>
					<th>INFO</th>
					<th>PREVIEW</th>
					<th>SHAPEFILE</th>
					<th>KMZ</th>
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
		$query1 = "SELECT * FROM metadata WHERE download=TRUE AND datacenter=TRUE ORDER BY title_of_dataset ASC";
		$query2 = "SELECT * FROM metadata WHERE download=FALSE AND datacenter=TRUE ORDER BY title_of_dataset ASC";

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
				<td><a style='color:black' href='" . $row['online_linkage'] . "' target='_blank'>Metadata</a></td>
				<td><a style='color:black' href='http://www.argis.org:8080/geoserver/wms?service=WMS&version=1.1.0&request=GetMap&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&styles=&bbox=474218.375,4766747.0,635668.0,4970120.0&width=406&height=512&srs=EPSG:26918&format=application/openlayers' target='_blank'>Preview</a></td>
				<td><a style='color:black' href='http://www.argis.org:8080/geoserver/wfs?request=GetFeature&amp;version=1.1.0&amp;typeName=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&amp;outputFormat=SHAPE-ZIP' target='_blank'>Shapefile</a></td>
				<td><a style='color:black' href='http://www.argis.org:8080/geoserver/wms/kml?mode=download&&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "' target='_blank'>KMZ</a></td></tr>";}
		while ($row = @pg_fetch_assoc($result2)){
		echo 	"<tr><td>" . $row['title_of_dataset'] . "</td>
				<td>" . $row['originator'] . "</td>
				 <td><a href=mailto:'" . $row['contact_email'] . "' target='_blank'> " . $row['Contact Person'] . " </a></td>
				<td>" . $row['last_updated'] . "</td>
				<td><a style='color:black' href='" . $row['online_linkage'] . "' target='_blank'>Metadata</a></td>
				<td><a style='color:black' href='http://www.argis.org:8080/geoserver/wms?service=WMS&version=1.1.0&request=GetMap&layers=" . $row['geoserver_workspace'] . ":" . $row['geoserver_layer'] . "&styles=&bbox=474218.375,4766747.0,635668.0,4970120.0&width=406&height=512&srs=EPSG:26918&format=application/openlayers' target='_blank'>Preview</a></td>
				<td>Not Available</td>
				<td>Not Available</td></tr>";
		}
		?>
	</tbody>
		</table>
	</div>