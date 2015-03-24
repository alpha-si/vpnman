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
                <div class="col-lg-12">
                    <h1 class="page-header">Networks</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <a href="javascript:addNetworkForm();" class="btn btn-success btn-xs">NEW NETWORK</a>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover small" id="dataTables-example">
                                    <thead>
                                        <tr>
                                          <th>ID</th>
                                          <th>Network</th>
                                          <th>Netmask</th>
                                          <th>Assigned To</th>
                                          <th>Mapped To</th>
                                          <th>Status</th>
                                          <th>Enabled</th>
                                          <th>Description</th>
                                          <th>Modify</th>
                                        </tr>
                                    </thead>
                                    <tbody id="netList">
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
                <!-- /.col-lg-8 -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
	
	<div class="panel-body" id="netForm" title="Edit Network">
      <form role="form" action="NetworkController.php" method="get" id="netEditForm">
         <input type="hidden" name="action" value="update">
         <input type="hidden" name="id" value="">
         <table class="table">
         <tr>
         <td><label>Address: </label></td>
         <td><input class="form-control" name="network" placeholder="Enter network address"></td>
         </tr>
         <tr>
         <td><label>Netmask: </label></td>
         <td><input class="form-control" placeholder="Enter network mask" name="netmask"></td>
         </tr>
         <tr>
         <td><label>Description: </label></td>
         <td><input class="form-control" placeholder="Enter network description" name="description"></td>
         </tr>
         <tr>
         <td><label>Mapped to: </label></td>
         <td><input class="form-control" placeholder="Enter new network address" name="mapped_to"></td>
         </tr>
         <tr>
         <td><label>Assigned to: </label></td>
         <td><select class="form-control" name="user_id"></select></td>
         </tr>
         <tr>
         <td></td>
         <td><label class="checkbox-inline">
               <input type="checkbox" name="enabled">Enabled
            </label></td>
         </tr>
         <td></td>
         <td>
            <a href="javascript:submitForm();" class="btn btn-success ">SAVE</a>
            <!--<button type="submit" class="btn btn-default">Save Network</button>-->
         </td>
         </tr>
         </table>
      </form>
	</div>

    <!-- Core Scripts - Include with every page -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- Page-Level Plugin Scripts - Dashboard -->
    <!--<script src="js/plugins/morris/raphael-2.1.0.min.js"></script>
    <script src="js/plugins/morris/morris.js"></script>-->

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>

    <!-- Page-Level Demo Scripts - Dashboard - Use for reference -->
    <!--<script src="js/demo/dashboard-demo.js"></script>-->
	<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
   <script src="js/reman-vpn.js"></script>
	<script src="js/waiting.js"></script>
	
	<script type="text/javascript">
	
	$(function() {
      $('#notifyInfo').hide();
      $('#notifyError').hide();
   
		$( "#netForm" ).dialog({
				autoOpen: false,
				height: 450,
				width: 450,
				modal: true,
				//position: { my: "center", at: "center top", of: "#page-wrapper" },
			});
		
		$.ajax({
			url : "NetworkController.php?action=nodelist",
			success : function (data,stato) {
            values = parseXmlData(data);
            if (values['result'] == 1)
            {
               $('select[name=user_id]').append(data);
            }
			},
			error : function (richiesta,stato,errori) {
				//alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
			}
			});
	});
	
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

	function addNetworkForm()
	{
		$('form')[0].reset();
		$('input[name=action]').val("create");
		$( "#netForm" ).dialog("open");
	}
	
	function delNetworkPopup(id)
	{
		if (confirm("Delete network?"))
		{
			$.ajax({
         url : "NetworkController.php?action=delete&id="+id,
         beforeSend : function () {
               $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
            },
         complete: function () {
               $('#busy1').activity(false);
            },
         success : function (data,stato) {
            showResult(data, "success");
         },
         error : function (richiesta,stato,errori) {
         }
         });
		}
	}
   
   function showResult( data, textStatus, jqXHR )
   {
      $('#busy1').activity(false);
      
      getNetList();
      
      $('#netForm').dialog('close');
      
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
   
   function submitForm()
   {
      var action = $('input[name=action]').val();
      
      $('#netForm').dialog('close');
      $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      
      if ((action == 'update') && confirm("Save network?"))
		{
			$.post("NetworkController.php", $('#netEditForm').serialize(), showResult);
		}
      
      if ((action == 'create') && confirm("Create network?"))
		{
			$.post("NetworkController.php", $('#netEditForm').serialize(), showResult);
		}
   }
	
	function fillNetworkForm(id)
	{
		$.ajax({
		url : "NetworkController.php?action=edit&id="+id,
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
            $('input[name=id]').val(values['id']);
            $('input[name=network]').val(values['network']);
            $('input[name=netmask]').val(values['netmask']);
            $('input[name=description]').val(values['description']);
            $('input[name=mapped_to]').val(values['mapped_to']);
            $('select[name=user_id]').val(values['user_id']);
            $('input[name=enabled]').attr('checked', (values['enabled'] == 1));
            $('input[name=action]').val("update");
            $( "#netForm" ).dialog("open");	
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
   
   function getNetList()
   {  
      $.ajax({
         url : "NetworkController.php?action=list",
         success : function (data,stato) {
            if (data != null && data !== undefined) 
            {
               values = parseXmlData(data);
               
               if (values['result'] == 1)
               {
                  $("#netList").html(values['html']);
               }
               else
               {
                  $('#errorText').text("ERROR: " + values['error']);
                  $('#notifyError').show();
               }
            }
         },
         complete: function() {
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore: refreshNodesStatus = " + stato);
         }
         });
   }
   
   getNetList();
			
	</script>

</body>

</html>
