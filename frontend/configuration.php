<?php
	//include("include/cfgmng.php");
	include("ConfigMng.php");
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
      
      <div id="notifyInfo" class="alert alert-success alert-dismissable">
         <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
         <span id="infoText"></span>
      </div>
      <div id="notifyError" class="alert alert-danger alert-dismissable">
         <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
         <span id="errorText"></span>
      </div>
               		
      <div class="row">
            <div id="busy1" class="square"></div>
				<div class="col-lg-12" style="height:100px;">
					<h3 class="page-header">VPNMAN Configuration</h3>
				</div>
				<!-- /.col-lg-12 -->
			</div>
			<!-- /.row -->
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-default">
               <!--
						<div class="panel-heading">
							<h3>VPNMAN Configuration</h3>
						</div>
                  -->
						<!-- /.panel-heading -->
						<div class="ui-tabs" id='cfgtabs'>
                      <ul class="ui-tabs-nav">
                        <li><a href="#cfgeditor"><span>Edit Configuration</span></a></li>
                        <li><a href="#cfgchecks"><span>Check Configuration</span></a></li>
                     </ul>
                     <!-- configuration editor -->
							<div class="ui-tabs-panel" id='cfgeditor'>
                        <form role="form" action="" method="post" id="configForm">
                        <input type="hidden" name="action" value="save">
								<table class="table table-striped table-bordered table-hover" id="dataTables-example">
									<thead class="small">
										<tr>
											<th>Parameter</th>
											<th>Value</th>                                 
										</tr>
									</thead>
									<tbody class="small" id="vpnData">
                           
									<?php 
                              $CFG->urlif_getcfg();
                              echo $CFG->GetLastHtmlData();
                           ?>
									
                           </tbody>
								</table>
                        <a href="javascript:saveConfig();" class="btn btn-success ">SAVE CONFIG</a>
                        <a href="javascript:buildCA();" class="btn btn-success ">BUILD CA</a>
                        </form>
							</div>
                     <!-- configuration checks -->
                     <div class="ui-tabs-panel" id='cfgchecks'>  
                        <table class="table table-striped table-bordered table-hover" id="check-res">
                           <thead class="small">
                              <tr>
                                 <th>Check</th>
                                 <th>Result</th>
                                 <th>Info</th>
                              </tr>
                         </thead>
                         <tbody class="small" id="check-res">
                           <?php
                              $CFG->urlif_checks();
                              echo $CFG->GetLastHtmlData();
                           ?>
                        </tbody>
                        <a href="javascript:runChecks();" class="btn btn-success ">RUN CHECKS</a>
							</div>
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
	<script src="js/waiting.js"></script>
   
	<script type="text/javascript">
   $(function() {
    $( document ).tooltip();
    $('#notifyInfo').hide();
    $('#notifyError').hide();
    $( "#cfgtabs" ).tabs();
  });
  
   $(function() {
      $('#about').dialog({
         autoOpen: false,
         height: 150,
         width: 300,
         modal: true,
         //position: { my: "center", at: "center top", of: "#page-wrapper" },
      });
   });
   
   function showResult( data, textStatus, jqXHR )
   {
      $('#busy1').activity(false);
      
      values = parseXmlData(data);
      
      if (values['result'] == 1)
      {
            $('#infoText').text(values['error']);
            $('#notifyInfo').show();
      }
      else
      {
         $('#errorText').text(values['error']);
         $('#notifyError').show();
      }
   }
   
   function runChecks()
   {
      $.ajax({
         url : "ConfigMng.php?action=checks",
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
            $("button").addClass('disabled');
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            values = parseXmlData(data);     
            if (values['result'] == 1)
            {
               $('#check-res').html(values['html']);
               $("#accountList").html(values['html']);
            }
            else
            {
               $('#errorText').text("ERROR: " + values['error']);
               $('#notifyError').show();
            }
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore: refreshNodesStatus = " + stato);
         }
         });
   }
   
   function buildCA()
   {
      if (confirm("WARNING: current ca keys will be overridden, continue?"))
      {
         $.ajax({
            url : "ConfigMng.php?action=buildca",
            beforeSend : function () {
               $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
               $("button").addClass('disabled');
            },
            complete: function () {
               $('#busy1').activity(false);
            },
            success : function (data,stato) {
               showResult(data,stato);
               updateConfig();
            },
            error : function (richiesta,stato,errori) {
               //alert("E' evvenuto un errore: refreshNodesStatus = " + stato);
            }
            });
      }
   }
   
   function updateConfig()
   {
      $.ajax({
         url : "ConfigMng.php?action=getcfg",
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
            $("button").addClass('disabled');
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            values = parseXmlData(data);
            if (values['result'] == 1)
            {
               $('#vpnData').html(values['html']);
            }
            else
            {
               $('#errorText').text("ERROR: " + values['error']);
               $('#notifyError').show();
            }
         },
         error : function (richiesta,stato,errori) {
         }
      });
   }
   
   function saveConfig()
   {
      if (confirm("Save configuration?"))
		{
			//$.post('configuration.php', $('#configForm').serialize(), showResult);
         $.post('ConfigMng.php', $('#configForm').serialize(), showResult);
		}
   }
   
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
