<?php
	include("include/cfgmng.php");
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
                    <h1 class="page-header">Accounts</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
<?php
                        if ($_SESSION['sess_role'] != 'USER')
                        {
                           echo "<a href=\"javascript:addAccountForm();\" class=\"btn btn-success btn-xs\">NEW ACCOUNT</a>";
                        }
                        else
                        {
                           echo "<a href=\"#\" class=\"btn btn-success btn-xs disabled\">NEW ACCOUNT</a>";
                        }
?>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover small" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Description</th>
                                            <th>HW Serial</th>
                                            <th>AccountType</th>
                                            <th>AuthType</th>
                                            <th>Status</th>
                                            <th>Enabled</th>
                                            <th>Links</th>
                                            <th>Modify</th>
                                        </tr>
                                    </thead>
                                    <tbody id="accountList">
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
	<div class="panel-body" id="accForm" title="Edit Account">
      <form role="form" action="AccountController.php" method="get" id="accEditForm">
         <input type="hidden" name="action">
         <input type="hidden" name="id" value="">
         <table class="table">
         <tr>
         <td><label class="control-label" for="accUsername">Username: </label></td>
         <td><input class="form-control" name="username" placeholder="Enter login username"></td>
         </tr>
         <tr>
         <td><label>Password: </label></td>
         <td><input class="form-control" placeholder="Enter login password" name="passwd"></td>
         </tr>
         <tr>
         <td><label>Description: </label></td>
         <td><input class="form-control" placeholder="Enter account description" name="description"></td>
         </tr>
         <tr>
         <td><label>HW Serial: </label></td>
         <td><input class="form-control" placeholder="Enter HW serial number" name="hw_serial"></td>
         </tr>
         <tr>
         <td><label>Account Type: </label></td>
         <td><select class="form-control" name="type">
               <option value="NODE">AccessPoint</option>
               <option value="CLIENT">Client</option>
            </select></td>
         </tr>
         <tr>
         <td><label>Auth Type: </label></td>
         <td><select class="form-control" name="auth_type">
               <option value="PASS_ONLY">Password Only</option>
               <option value="CERT_ONLY">Certificate Only</option>
               <option value="CERT_PASS">Certificate + Password</option>
            </select></td>
         </tr>
         <tr>
         <td><label>GPS Coordinates: </label></td>
         <td><input class="form-control" placeholder="Enter longitude, latitude" name="coordinates"></td>
         </tr>
         <tr>
         <td><label>Web Link: </label></td>
         <td><input class="form-control" placeholder="Enter url" name="links"></td>
         </tr>
         <tr>
         <td></td>
         <td><label class="checkbox-inline">
               <input type="checkbox" name="enabled">Enabled
            </label></td>
         </tr>
         <td></td>
         <td><a href="javascript:submitAccount();" class="btn btn-success ">SAVE</a></td>
         </tr>
         </table>
      </form>
		<!-- /.row (nested) -->
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
      
		$( "#accForm" ).dialog({
				autoOpen: false,
				height: 600,
				width: 450,
				modal: true,
				//position: { my: "center", at: "center top", of: "#page-wrapper" },
			});
	});
   
   function showResult( data, textStatus, jqXHR )
   {
      $('#busy1').activity(false);
      
      getAccountList();
      
      $('#accForm').dialog('close');
      
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
	
	function parseXmlData(req)
	{
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
   
   function submitAccount()
   {
      var action = $('input[name=action]').val();
      
      $('#accForm').dialog('close');
      $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      
		//@@@DEBUG alert($('#accEditForm').serialize());

      if ((action == 'update') && confirm("Save account?"))
		{
			$.post("AccountController.php", $('#accEditForm').serialize(), showResult);
		}
      
      if ((action == 'create') && confirm("Create account?"))
		{
			$.post("AccountController.php", $('#accEditForm').serialize(), showResult);
		}
   }

	function addAccountForm()
	{
      $('form')[0].reset();
		$('input[name=action]').val("create");
		$( "#accForm" ).dialog("open");
	}
	
	function delAccountPopup(id)
	{
      if (confirm("Delete account " + id + "?"))
		{
         $.ajax({
         url : 'AccountController.php?action=delete&id=' + id,
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            showResult( data, 'success' );
            getAccountList();
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
         }
         }); 
		}
	}
   
	function fillAccountForm(id)
	{
      var requrl = "";
      
      /*
      if (id < 0)
      {
         requrl = "AccountController.php?action=edit",
         $('input[name=action]').val("edit");
      }
      else
      {
         requrl = "AccountController.php?action=edit&id="+id,
         $('input[name=action]').val("update");
         $('input[name=id]').val(id);
      }
      */
      
		$.ajax({
		url : "AccountController.php?action=edit&id="+id,
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
            $('input[name=username]').val(values['username']);
            $('input[name=passwd]').val(values['passwd']);
            $('input[name=description]').val(values['description']);
            $('input[name=hw_serial]').val(values['hw_serial']);
            $('select[name=type]').val(values['type']);
            $('input[name=coordinates]').val(values['coordinates']);
            $('input[name=links]').val(values['links']);
            $('input[name=enabled]').attr('checked', (values['enabled'] == 1));
            $('input[name=auth_type]').val(values['auth_type']);
            $('input[name=action]').val("update");
            $( "#accForm" ).dialog("open");
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
   
   function getAccountList()
   {
      $.ajax({
         url : "AccountController.php?action=list",
         success : function (data,stato) {
            values = parseXmlData(data);
               
            if (values['result'] == 1)
            {
               $("#accountList").html(values['html']);
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
   }
   
   getAccountList();
	
	</script>

</body>

</html>
