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
    <link href="css/sb-admin.css" rel="stylesheet">
</head>

<body>
    <div id="wrapper">

		<!-- add left and top menu -->
        <?php include("include/menu.php"); ?>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Connections History</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Sessions:
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="dataTable_wrapper">
                                <table class="table table-striped table-bordered table-hover small" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>VPN Address</th>
                                            <th>Real Address</th>
                                            <th>Bytes RX</th>
                                            <th>Bytes TX</th>
                                            <th>CID</th>
                                            <th>KID</th>		
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
         $query = $DB->prepare("SELECT username, c.* FROM connection_history AS c, accounts AS u WHERE c.user_id = u.id AND u.vpn_id = ? ORDER BY start_time DESC, end_time");
         $query->execute(array($_SESSION['sess_vpn_id']));
      }
      else
      {
         $query = $DB->prepare("SELECT username, c.* FROM connection_history AS c, accounts AS u WHERE c.user_id = u.id AND u.vpn_id = ? AND u.user_id = ? ORDER BY start_time DESC, end_time");
         $query->execute(array($_SESSION['sess_vpn_id'], $_SESSION['sess_user_id']));
      }
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         echo "<tr class=\"odd gradeX\">";
         echo "<td>" . $row[0] . "</td>";
         echo "<td>" . $row[3] . "</td>";
         echo "<td>" . $row[4] . "</td>";
         echo "<td>" . $row[9] . "</td>";
         echo "<td>" . $row[7] . "</td>";
         echo "<td>" . $row[5] . "</td>";
         echo "<td>" . $row[6] . "</td>";
         echo "<td>" . $row[10] . "</td>";
         echo "<td>" . $row[11] . "</td>";
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
    
    <!-- DataTables JavaScript -->
    <script src="js/plugins/dataTables/jquery.dataTables.js"></script>
    <script src="js/plugins/dataTables/dataTables.bootstrap.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

    <!-- Page-Level Demo Scripts - Dashboard - Use for reference -->
	<script src="js/reman-vpn.js"></script>
   
   <!-- Custom Theme JavaScript -->
   <!--<script src="js/sb-admin-2.js"></script>-->

   <!-- Page-Level Demo Scripts - Tables - Use for reference -->
	<script type="text/javascript">
   $(document).ready(function() {
      $('#dataTables-example').DataTable({
                responsive: true
      });
   });		
	</script>

</body>

</html>
