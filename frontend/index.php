<?php
	include("include/cfgmng.php");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VPNMAN - VPN Management Platform</title>

    <!-- Core CSS - Include with every page -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">

    <!-- Page-Level Plugin CSS - Dashboard -->
    <link href="css/plugins/morris/morris-0.4.3.min.css" rel="stylesheet">

    <!-- SB Admin CSS - Include with every page -->
    <link href="css/sb-admin.css" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
</head>

<body>
    <div id="wrapper">
	
		<!-- add left and top menu -->
        <?php include("include/menu.php"); ?>
		
		<div id="page-wrapper">
			<div class="row">
				<div class="col-lg-12" style="height:25px;">
				</div>
				<!-- /.col-lg-12 -->
			</div>
			<!-- /.row -->
			<div class="row">
				<div class="col-lg-8">
					<div class="panel panel-default">
						<div id="map_canvas" style="height:395px"></div>
					</div>
				</div>
				<!-- /.col-lg-8 -->
				<div class="col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<i class="fa fa-bell fa-fw"></i> Status Panel
						</div>
						<!-- /.panel-heading -->
						<div class="panel-body">
							<div class="list-group">
								<a href="#" class="list-group-item">
									<i class="fa fa-power-off fa-fw"></i> VPN Server Status
									<span class="pull-right text-muted small">
									<button type="button" class="btn btn-success btn-xs" id="vpnStatus" onclick="javascript:controlVpn();"></button>
									</span>
								</a>
								<a href="#" class="list-group-item">
									<i class="fa fa-tag fa-fw"></i> VPN Server Version
									<span class="pull-right text-muted small"><em id="vpnVer"></em>
									</span>
								</a>
								<a href="#" class="list-group-item">
									<i class="fa fa-laptop fa-fw"></i> Connected Nodes
									<span class="pull-right text-muted small"><em id="vpnNodes"></em>
									</span>
								</a>
								<a href="#" class="list-group-item">
									<i class="fa fa-clock-o fa-fw"></i> Uptime
									<span class="pull-right text-muted small"><em id="vpnUptime"></em>
									</span>
								</a>
								<a href="#" class="list-group-item">
									<i class="fa fa-arrow-right fa-fw"></i> TX Bytes
									<span class="pull-right text-muted small"><em id="vpnTxBytes"></em>
									</span>
								</a>
								<a href="#" class="list-group-item">
									<i class="fa fa-arrow-left fa-fw"></i> RX Bytes
									<span class="pull-right text-muted small"><em id="vpnRxBytes"></em>
									</span>
								</a>
								
							</div>
							<!-- /.list-group -->
							<div id="about2">
								<center><p>developed by <a href="http://www.alpha-si.com"><img src='./img/logo_alphasi2.png' style='margin:15px'></a></p></center>
							</div>
						</div>
						<!-- /.panel-body -->
					</div>
					<!-- /.panel -->
				</div>
				<!-- /.col-lg-4 -->		
				<div class="col-lg-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							Connected Nodes:
						</div>
						<!-- /.panel-heading -->
						<div class="panel-body">
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover" id="dataTables-example">
									<thead class="small">
										<tr>
											<th>Username</th>
											<th>Description</th>
											<th>HwSerial</th>
											<th>Type</th>
											<th>Status</th>
											<th>Start Time</th>
											<th>End Time</th>
											<th>Bytes RX</th>
											<th>Bytes TX</th>
											<th>Public IP</th>
											<th>VPN IP</th>
										</tr>
									</thead>
									<tbody class="small" id="nodesStatus">
										<!-- filled by ajax -->
									</tbody>
								</table>
							</div>
							<!-- /.table-responsive -->
						</div>
						<!-- /.panel-body -->
					</div>
					<!-- /.panel -->	
				</div>
				
			</div>
			<!-- /.row -->
		</div>
		<!-- /#page-wrapper -->
		
	</div>
	<!-- /#wrapper -->
	
	<div id="about">
		<center><h5><b>VPNMAN v0.1</b></h5><p>developed by <a href="http://www.alpha-si.com">Alpha SI s.r.l.</a></p></center>
	</div>
	     
   <!-- Core Scripts - Include with every page -->
	<script src="http://maps.google.com/maps/api/js?sensor=true" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui-map/ui/min/jquery.ui.map.full.min.js" type="text/javascript"></script>
	<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
	<script src="js/reman-vpn.js"></script>
	
	<script type="text/javascript">
		function createMapMarkers(ev, map) 
		{
			$.ajax({
			url : "VpnController.php?action=getgpspos",
			success : function (data,stato) {
				if (data != null && data !== undefined && typeof data == 'object') 
				{
					values = parseXmlData(data);
					for (var username in values)
					{
                  var marker = new google.maps.Marker();
                   marker = {
                       position: values[username],
                       title: username,
                       infoWindow: {
                         content: username
                       }
                   }

						$('#map_canvas').gmap('addMarker', marker);
					}
				}
			},
			error : function (richiesta,stato,errori) {
				alert("E' evvenuto un errore: createMapMarkers = " + stato);
			}
			});
			
		}
		
        $(function() {
				$('#map_canvas').gmap().bind('init', createMapMarkers);
				
				$('#about').dialog({
					autoOpen: false,
					height: 150,
					width: 300,
					modal: true,
				});
        });
		
		refreshVpnStatus();
		refreshNodesStatus();
		
		function showAbout()
		{
			$('#about').dialog("open");
		}
	</script>

    <!--<script src="js/jquery-1.10.2.js"></script>-->
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- Page-Level Plugin Scripts - Dashboard -->
    <script src="js/plugins/morris/morris.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

</body>

</html>
