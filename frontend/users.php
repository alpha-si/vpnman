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
                    <h1 class="page-header">Users</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
							<a href="javascript:addUserForm();" class="btn btn-success btn-xs">NEW USER</a>
							
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th colspan="2">User Role</th>
                                            <th>Modify</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userList">
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
	<div class="panel-body" id="userDialog" title="Edit User">
      <form role="form" action="UserController.php" method="get" id="userForm">
         <input type="hidden" name="action">
         <input type="hidden" name="id" value="">
         <table class="table" style="width:400px;">
         <tr>
         <td><label class="control-label" for="username">Username: </label></td>
         <td><input class="form-control" name="username" size='20' placeholder="Enter login username"></td>
         </tr>
         <tr>
         <td><label>Password: </label></td>
         <td><input class="form-control" placeholder="Enter login password" name="password"></td>
         </tr>
         <tr>
         <td><label>User Role: </label></td>
         <td><select class="form-control" name="role">
               <option value="USER" selected>USER</option>
               <option value="MANAGER">VPN MANAGER</option>
               <option value="ADMIN">ADMINISTRATOR</option>
            </select></td>
         </tr>
         <tr>
         <td></td>
         <td><label class="checkbox-inline">
               <input type="checkbox" name="enabled">Enabled
            </label></td>
         </tr>
         <td></td>
         <td><a href="javascript:submitUserForm();" class="btn btn-success ">SAVE</a></td>
         </tr>
         </table>
      </form>
	</div>
   
   <div class="panel-body" id="user2vpnDialog" title="Assign VPN">
      <form role="form" action="UserController.php" method="get" id="user2vpnForm">
         <input type="hidden" name="action">
         <input type="hidden" name="id">
         <table class="table">
         <tr>
         <td><label class="control-label" for="username">Username: </label></td>
         <td><input class="form-control" name="username" disabled></td>
         </tr>
         <tr>
         <td><label>VPN: </label></td>
         <td>
            <select class="form-control" name="vpn_id">
<?php
            $query = $DB->query("SELECT id,description FROM vpn");
            while ($row = $query->fetch(PDO::FETCH_NUM))
            {
               echo "<option value='" . $row[0] . "'>" . $row[1] . "</option>";
            }
?>
            </select>
         </td>
         </tr>
         <tr>
         <td><label>Role: </label></td>
         <td><select class="form-control" name="u2v_role">
               <option value="USER" selected>USER</option>
               <option value="MANAGER">VPN MANAGER</option>
            </select>
         </td>
         </tr>
         <td></td>
         <td><a href="javascript:submitUser2VpnForm();" class="btn btn-success ">SAVE</a></td>
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
      
		$( "#userDialog" ).dialog({
				autoOpen: false,
				height: 350,
				width: 450,
				modal: true,
				//position: { my: "center", at: "center top", of: "#page-wrapper" },
			});
      
      $( "#user2vpnDialog" ).dialog({
				autoOpen: false,
				height: 350,
				width: 450,
				modal: true,
				//position: { my: "center", at: "center top", of: "#page-wrapper" },
			});
	});
   
   function showResult( data, textStatus, jqXHR )
   {
      $('#busy1').activity(false);
      
      getUserList();
      
      $('#userDialog').dialog('close');
      $('#user2vpnDialog').dialog('close');
      
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
   
   function submitUserForm()
   {
      var action = $('input[name=action]').val();

      $('#userDialog').dialog('close');
      $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      
      if ((action == 'update') && confirm("Save user?"))
		{
			$.post("UserController.php", $('#userForm').serialize(), showResult);
		}
      
      if ((action == 'create') && confirm("Create user?"))
		{
			$.post("UserController.php", $('#userForm').serialize(), showResult);
		}
   }

   function submitUser2VpnForm()
   {
      var action = $('input[name=action]').val();
      
      $('#user2vpnDialog').dialog('close');
      $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      
      if ((action == 'u2v_update') && confirm("Update Role?"))
		{
			$.post("UserController.php", $('#user2vpnForm').serialize(), showResult);
		}
      
      if ((action == 'u2v_create') && confirm("Add Role?"))
		{
         alert($('#user2vpnForm').serialize());
			$.post("UserController.php", $('#user2vpnForm').serialize(), showResult);
		}
   }
   
	function addUserForm()
	{
      $('form')[0].reset();
      //fillUserForm(null);
		$('input[name=action]').val("create");
		$( "#userDialog" ).dialog("open");
	}
	
	function delUserPopup(id)
	{
      if (confirm("Delete user " + id + "?"))
		{
         $.ajax({
         url : 'UserController.php?action=delete&id=' + id,
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
   
	function fillUserForm(id)
	{  
		$.ajax({
		url : "UserController.php?action=edit&id="+id,
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
            $('input[name=password]').val(values['password']);
            $('select[name=role]').val(values['role']);
            $('input[name=enabled]').attr('checked', (values['enabled'] == 1));
            $('input[name=action]').val("update");
            $( "#userDialog" ).dialog("open");
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
   
   function fillUser2VpnForm(userId, vpnId)
	{
      var requrl = "";
      
      if (vpnId < 0)
      {
         requrl = "UserController.php?action=u2v_edit&id="+userId,
         $('input[name=action]').val("u2v_create");  
      }
      else
      {
         requrl = "UserController.php?action=u2v_edit&id="+userId+"&vpn_id="+vpnId,
         $('input[name=action]').val("u2v_update");
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
            $('input[name=id]').val(values['user_id']);
            $('select[name=vpn_id]').val(values['vpn_id']);
            $('input[name=username]').val(values['user_name']);
            
            if (vpnId >= 0)
            {
               $('select[name=u2v_role]').val(values['vpn_role']);
            }
            
            $( "#user2vpnDialog" ).dialog("open");
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
   
   function delUser2VpnPopup(userId, vpnId)
   {
      if (confirm("Delete user " + userId + " from vpn " + vpnId + "?"))
		{
         $.ajax({
         url : 'UserController.php?action=u2v_delete&id=' + userId + "&vpn_id=" + vpnId,
         beforeSend : function () {
            $('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
         },
         complete: function () {
            $('#busy1').activity(false);
         },
         success : function (data,stato) {
            showResult( data, 'success' );
            getUserList();
         },
         error : function (richiesta,stato,errori) {
            //alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
         }
         }); 
		}
   }
   
   function getUserList()
   {
      $.ajax({
         url : "UserController.php?action=list",
         success : function (data,stato) {
            values = parseXmlData(data);
               
            if (values['result'] == 1)
            {
               $("#userList").html(values['html']);
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
   
   getUserList();
	
	</script>

</body>

</html>
