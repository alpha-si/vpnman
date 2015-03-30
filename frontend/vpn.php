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
         <div id="notifyInfo" class="alert alert-success alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <span id="infoText">???</span>
         </div>
         <div id="notifyError" class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <span id="errorText">???</span>
         </div>
			<div class="row">
            <div id="busy1" class="square"></div>
				<div class="col-lg-12" style="height:100px;">
					<h3 class="page-header">Configured VPNs</h3>   
            </div>   
				<!-- /.col-lg-12 -->
			</div>
			<!-- /.row -->
			<div class="row">
				<div class="col-lg-12">
					<div class="panel panel-default">
						<div class="panel-heading">
<?php 
                  if ($_SESSION['sess_role'] == 'ADMIN')
                  {
                     echo "<a href=\"javascript:addVpnForm();\" class=\"btn btn-success btn-xs\">Create New VPN</a>";
                  }
                  else
                  {
                     echo "<a href=\"#\" class=\"btn btn-success btn-xs disabled\">Create New VPN</a>";
                  }
?>
						</div>
						<!-- /.panel-heading -->
						<div class="panel-body">
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover" id="dataTables-example">
									<thead class="small">
										<tr>
											<th>ID</th>
											<th>Description</th>
											<th>Status</th>
                                 <th>Network</th>
											<th>SrvAddress</th>
											<th>SrvPort</th>
											<th>MngPort</th>
											<th>ProtoType</th>
											<th>AuthType</th> 
                                 <th>Modify</th>                                  
										</tr>
									</thead>
									<tbody class="small" id="vpnList">
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
   
   <div class="panel-body" id="vpnCfgEditor" title="Edit VPN">
      <form role="form" action="VpnController.php" method="post" id="vpnCfgEditForm">
         <!--<input type="hidden" name="action">-->
         <input type="hidden" name="id">
         <table class="table">
         <tr>
            <td>
            <label>OpenVPN Server Configuration:</label>
            <textarea class="form-control" rows="23" name="content" id="vpnCfgTextArea"></textarea>
            </td>
         </tr>
         <tr>
            <td>
            <!--<button type="submit" class="btn btn-default">Create VPN</button>-->
            <a href="javascript:saveVpnCfg();" class="btn btn-success ">SAVE</a>
            <a href="javascript:closeVpnCfg();" class="btn btn-success ">CANCEL</a>
            </td>
         </tr>
         </table>
      </form>
	</div>
   
   <div class="panel-body" id="vpnForm" title="Edit VPN">
      <form role="form" action="VpnController.php" method="post" id="vpnEditForm">
         <input type="hidden" name="action">
         <input type="hidden" name="id">
         <table class="table">
         <tr>
         <td><label class="control-label" for="vpnName">Description: </label></td>
         <td><input class="form-control" name="vpnName" placeholder="Enter VPN description"></td>
         </tr>
         <tr>
         <td><label>Organization Name: </label></td>
         <td><input class="form-control" placeholder="Enter owner organization" name="vpnOrgName"></td>
         </tr>
         <tr>
         <td><label>Organization Unit: </label></td>
         <td><input class="form-control" placeholder="Enter owner organization unit" name="vpnOrgUnit"></td>
         </tr>
         <tr>
         <td><label>Reference mail: </label></td>
         <td><input class="form-control" placeholder="Enter owner email" name="vpnOrgMail"></td>
         </tr>
         <tr>
         <td><label>Organization Country Code: </label></td>
         <td><input class="form-control" placeholder="Enter organization country code" name="vpnOrgCountry"></td>
         </tr>
         <tr>
         <td><label>Organization Province Code: </label></td>
         <td><input class="form-control" placeholder="Enter organization province code" name="vpnOrgProv"></td>
         </tr>
         <tr>
         <td><label>Organization City Name: </label></td>
         <td><input class="form-control" placeholder="Enter organization city name" name="vpnOrgCity"></td>
         </tr>
         <tr>
         <td><label>Protocol Type: </label></td>
         <td><select class="form-control" name="vpnProtoType">
               <option value="udp">UDP</option>
               <option value="tcp">TCP</option>
            </select></td>
         </tr>
         <tr>
         <td><label>Listen Port: </label></td>
         <td><input class="form-control" placeholder="Enter vpn listening port" name="vpnListenPort"></td>
         </tr>
         <tr>
         <td><label>Management Port: </label></td>
         <td><input class="form-control" placeholder="Enter vpn management port" name="vpnManagePort"></td>
         </tr>
         <tr>
         <td><label>Network Address: </label></td>
         <td><input class="form-control" placeholder="Enter vpn network address" name="vpnNetAddr"></td>
         </tr>
         <tr>
         <td><label>Network Mask: </label></td>
         <td><input class="form-control" placeholder="Enter vpn network mask" name="vpnNetMask"></td>
         </tr>
         <tr>
         <td><label>Authentication Type: </label></td>
         <td><select class="form-control" name="vpnAuthType">
               <option value="PASS_ONLY">PasswordOnly</option>
               <option value="CERT_ONLY">CertificateOnly</option>
               <option value="CERT_PASS">Certificate&Password</option>
            </select></td>
         </tr>
         <tr>
         <td></td>
         <td>
         <!--<button type="submit" class="btn btn-default">Create VPN</button>-->
         <a href="javascript:submitVpn();" class="btn btn-success ">SAVE</a>
         <a href="javascript:editVpnCfg();" class="btn btn-success ">ADVANCED</a>
         </td>
         </tr>
         </table>
      </form>
	</div>
	
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
      $('#notifyInfo').hide();
      $('#notifyError').hide();
      
      $('#about').dialog({
         autoOpen: false,
         height: 150,
         width: 300,
         modal: true,
         //position: { my: "center", at: "center top", of: "#page-wrapper" },
      });

      $( "#vpnForm" ).dialog({
         autoOpen: false,
         height: 800,
         width: 650,
         modal: true,
         //position: { my: "center", at: "center top", of: "#page-wrapper" },
      });
      
      $( "#vpnCfgEditor" ).dialog({
         autoOpen: false,
         height: 800,
         width: 650,
         modal: true,
         //position: { my: "center", at: "center top", of: "#page-wrapper" },
      });
    
   });
   
   function showAbout()
   {
      $('#about').dialog("open");
   }
 	
	function parseXmlData(req)
	{
		//alert(req);
		data = new Array();
		
		var symbols = req.getElementsByTagName('symbol');

		for (var i=0; i<symbols.length; i++)
		{
			name = symbols[i].getAttribute('name');
			//symbols[i].getAttribute('type');
			value = symbols[i].textContent;
			//alert(name + " = " + value);
			data[name] = value;
		}
		
		return data;
	}
   
   function showResult( data, textStatus, jqXHR )
   {
      $('#busy1').activity(false);
      
      getVpnList();
      
      $('#vpnForm').dialog('close');
      
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
   
   function editVpnCfg(id)
   {
      $.ajax({
         url : 'VpnController.php?action=getcfg&id=' + $('input[name=id').val(),
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            values = parseXmlData(data);
            if (values['result'] == 1)
            {
               $('#vpnCfgTextArea').val(values['data']);
               $('#vpnForm').dialog('close');
               $('#vpnCfgEditor').dialog("open");
            }
            else
            {
               $('#errorText').text(values['error']);
               $('#notifyError').show();
            }
         },
         error : function (richiesta,stato,errori) {
         }
         }); 
   }
   
   function saveVpnCfg()
   {    
      //$('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
		$.post("VpnController.php?action=savecfg", $('#vpnCfgEditForm').serialize(), showResult);
      $('#vpnFormCfg').dialog('close');
   }
   
   function closeVpnCfg()
   {
      $('#vpnCfgEditor').dialog("close");  
      $('#vpnForm').dialog('open');
   }
   
   function submitVpn()
   {
      var action = $('input[name=action]').val();
      
      $('#vpnForm').dialog('close');
      $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      
      alert($('#vpnEditForm').serialize());
      
      if ((action == 'update') && confirm("Save configuration?"))
		{
			$.post("VpnController.php", $('#vpnEditForm').serialize(), showResult);
		}
      
      if ((action == 'create') && confirm("Create VPN?"))
		{
			$.post("VpnController.php", $('#vpnEditForm').serialize(), showResult);
		}
   }

	function addVpnForm()
	{
      fillVpnForm((-1));
	}
	
	function delVpnPopup(id)
	{
		if (confirm("Delete VPN" + id + "?"))
		{
         $.ajax({
         url : 'VpnController.php?action=delete&id=' + id,
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            showResult( data, 'success' );
            getVpnList();
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
         }
         }); 
		}
	}
	
	function fillVpnForm(id)
	{
      var requrl = "";
      
      if (id < 0)
      {
         requrl = "VpnController.php?action=edit",
         $('input[name=action]').val("create");
      }
      else
      {
         requrl = "VpnController.php?action=edit&id="+id,
         $('input[name=action]').val("update");
         $('input[name=id]').val(id);
      }
      
		$.ajax({
		url : requrl,
		beforeSend : function () {
			$('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
		},
		complete: function () {
			$('#busy1').activity(false);
		},
		success : function (data,stato) {    
			values = parseXmlData(data);

         if (values['result'] == 1)
         {
            $('input[name=vpnName]').val(values['description']);
            $('input[name=vpnOrgName]').val(values['org_name']);
            $('input[name=vpnOrgUnit]').val(values['org_unit']);
            $('input[name=vpnOrgMail]').val(values['org_mail']);
            $('input[name=vpnOrgCountry]').val(values['org_country']);
            $('input[name=vpnOrgProv]').val(values['org_prov']);
            $('input[name=vpnOrgCity]').val(values['org_city']);
            $('select[name=vpnProtoType]').val(values['proto_type']);
            $('select[name=vpnAuthType]').val(values['auth_type']);
            $('input[name=vpnListenPort]').val(values['srv_port']);
            $('input[name=vpnManagePort]').val(values['mng_port']);
            $('input[name=vpnNetAddr]').val(values['net_addr']);
            $('input[name=vpnNetMask]').val(values['net_mask']);
            $( "#vpnForm" ).dialog("open");	
         }
         else
         {
            $('#errorText').text("ERROR: " + values['error']);
            $('#notifyError').show();
         }
		},
		error : function (richiesta,stato,errori) {
			//alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
		}
		});
	}
   
   function getVpnList()
   {  
      $.ajax({
         url : "VpnController.php?action=list",
         success : function (data,stato) {
            values = parseXmlData(data);
               
            if (values['result'] == 1)
            {
               $("#vpnList").html(values['html']);
            }
            else
            {
               $('#errorText').text("ERROR: " + values['error']);
               $('#notifyError').show();
            }
         },
         complete: function() {
            // Schedule the next request when the current one's complete
            //setTimeout(getVpnList, 10000);
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore: refreshNodesStatus = " + stato);
         }
         });
         
      refreshVpnListBox();
   }
   
   getVpnList();
   
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
