<?php
	require_once("include/cfgmng.php");
?>
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
    <link href="css/plugins/timeline/timeline.css" rel="stylesheet">

    <!-- SB Admin CSS - Include with every page -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
    <link href="css/sb-admin.css" rel="stylesheet">
    <link href="css/logtail.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div id="wrapper">

		<!-- add left and top menu -->
        <?php include("include/menu.php"); ?>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Log</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                    <!---------------------------------------------->
                    <div id="tabs">
                       <ul>
                         <li><a href="#sessions">Sessions</a></li>
                         <li><a href="#openvpnlog">OpenVPN Log</a></li>
                         <li><a href="#ovpnctrllog">Ovpnctrl Log</a></li>
                       </ul>
                        <!-------------------- SESSIONS TAB -------------------------->
                        <div id="sessions" class="panel-body">
                            <div class="dataTable_wrapper">
                                <table class="table table-striped table-bordered table-hover small" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Length</th>
                                            <th>VPN Address</th>
                                            <th>Real Address</th>
                                            <th>RX (MB)</th>
                                            <th>TX (MB)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
   if (!isset($_SESSION['sess_vpn_id']))
   {
         echo "<tr class=\"odd gradeX\">";
			echo "<td colspan='9'>Select a vpn...</td>";
			echo "</tr>";
         exit;
   }
   else
   {
      if ($_SESSION['sess_role'] != 'USER')
      {
         $query = $DB->prepare("
         SELECT username, c.start_time, c.end_time, TIMEDIFF(c.end_time, c.start_time) as sl, ROUND((c.bytes_received / 1048576),2) as rx, ROUND((c.bytes_sent / 1048576),2) as tx, c.trusted_ip, c.ifconfig_pool_remote_ip
         FROM connection_history AS c, accounts AS u 
         WHERE c.user_id = u.id AND u.vpn_id = ? 
         ORDER BY start_time DESC, end_time LIMIT 1000");
         $query->execute(array($_SESSION['sess_vpn_id']));
      }
      else
      {
         $query = $DB->prepare("SELECT username, , c.start_time, c.end_time, TIMEDIFF(c.end_time, c.start_time) as sl, ROUND((c.bytes_received / 1048576),2) as rx, ROUND((c.bytes_sent / 1048576),2) as tx, c.trusted_ip, c.ifconfig_pool_remote_ip
         FROM connection_history AS c, accounts AS u 
         WHERE c.user_id = u.id AND u.vpn_id = ? AND u.user_id = ? 
         ORDER BY start_time DESC, end_time LIMIT 1000");
         $query->execute(array($_SESSION['sess_vpn_id'], $_SESSION['sess_user_id']));
      }
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         echo "<tr class=\"odd gradeX\">";
         echo "<td>" . $row[0] . "</td>";
         echo "<td>" . $row[1] . "</td>";
         echo "<td>" . $row[2] . "</td>";
         echo "<td>" . $row[3] . "</td>";
         echo "<td>" . $row[7] . "</td>";
         echo "<td>" . $row[6] . "</td>";
         echo "<td>" . $row[4] . "</td>";
         echo "<td>" . $row[5] . "</td>";
         echo "</tr>";	
      }
   }
?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                        
                        <!-------------------- OpenvpnLog TAB -------------------------->
                        <div id="openvpnlog" class="panel-body">
                        <!--
                           <div id="header">
                              js-logtail.
                              <a href="./">Reversed</a> or
                              <a href="./?noreverse">chronological</a> view.
                              <a id="pause" href='#'>Pause</a>.
                          </div>
                          -->
                          <pre id="openvpnlog-data"></pre>
                        </div>

                        <!-------------------- OvpnctrlLog TAB -------------------------->
                        <div id="ovpnctrllog" class="panel-body">
                           <pre id="ovpnctrllog-data"></pre>
                        </div>
                        
                    </div>
                    <!-- /.panel -->
                </div>
            </div>
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- Core Scripts - Include with every page -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- Page-Level Plugin Scripts - Dashboard -->
    <!--<script src="js/plugins/morris/raphael-2.1.0.min.js"></script>
    <script src="js/plugins/morris/morris.js"></script>-->
    <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
    
    <!-- DataTables JavaScript -->
    <script src="js/plugins/dataTables/jquery.dataTables.js"></script>
    <script src="js/plugins/dataTables/dataTables.bootstrap.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

    <!-- Page-Level Demo Scripts - Dashboard - Use for reference -->
	<script src="js/reman-vpn.js"></script>
   
   <!-- Page-Level Demo Scripts - Tables - Use for reference -->
	<script type="text/javascript">

   var logstate = 
      [{
         logtype:'openvpn', 
         logelem:'#openvpnlog-data', 
         logseek:0,
      }, {
         logtype:'ovpnctrl', 
         logelem:'#ovpnctrllog-data', 
         logseek:0,
      }];
   
   function getLogContent(vpn_id, log_state)
   {  
      var logurl = "VpnController.php?action=getlog&id=" + vpn_id + "&logtype=" + log_state.logtype;

      if (log_state.logseek > 0)
      {
         logurl += "&offset=" + log_state.logseek;
      }
//alert(logurl);
      $.ajax({
		url : logurl,
		success : function (data,stato) {
			if (data != null && data !== undefined && typeof data == 'object') 
			{
            values = parseXmlData(data);

            if (values['result'])
            { 
               log_state.logseek = values['seek'];
               $(log_state.logelem).append(values['html']);
            }
            else
            {
               $(log_state.logelem).append(values['error'] + "<br>");
            }
			}
		},
		complete: function() {
			// Schedule the next request when the current one's complete
			setTimeout(updateLogContent, 5000);
		},
		error : function (richiesta,stato,errori) {
			//alert("E' evvenuto un errore: refreshVpnStatus = " + stato);
		}
		});
   }
   
   function updateLogContent()
   {
      var id = <?php echo $_SESSION['sess_vpn_id']; ?>;
      var active = $( "#tabs" ).tabs( "option", "active" );

      if (active == 1)
      {
         // update openvpn log content
         getLogContent(id, logstate[0]);
      }
      else if (active == 2)
      {
         // update ovpnctrl log content
         getLogContent(id, logstate[1]);
      }
   }
   
    $(function() {
       $( "#tabs" ).tabs({
         activate: updateLogContent,
      });
   });		
   
	</script>

</body>

</html>
