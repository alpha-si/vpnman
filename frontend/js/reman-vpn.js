var vpnRefreshStatusTimer;

function parseXmlData(req)
{
	data = new Array();
	if (typeof req != 'object')
		alert(typeof req);
	var symbols = req.getElementsByTagName('symbol');

	for (var i=0; i<symbols.length; i++)
	{
		name = symbols[i].getAttribute('name');
		value = symbols[i].textContent;
		data[name] = value;
	}
	
	return data;
}

function refreshVpnStatus()
{
	$.ajax({
		url : "VpnController.php?action=status",
		success : function (data,stato) {
			if (data != null && data !== undefined && typeof data == 'object') 
			{
				values = parseXmlData(data);
            
				$("#vpnStatus").text(values['status']);
            
            if (values['status'] == 'RUNNING')
            {
               $("#vpnStatus").removeClass("btn-danger btn-warning").addClass("btn-success");
               link = "javascript:controlVpn('stop','" + values['id'] + "')";
               $("#vpnStatus").attr("onclick", link);
            }
            else
            {  
               $("#vpnStatus").removeClass("btn-success btm-warning").addClass("btn-danger");
               link = "javascript:controlVpn('start','" + values['id'] + "')";
               $("#vpnStatus").attr("onclick", link);
            }
            
				$("#vpnVer").text(values['server_ver']);
				$("#vpnNodes").text(values['nclients']);
				$("#vpnUptime").text(values['start_time']);
				$("#vpnTxBytes").text(values['bytesout']);
				$("#vpnRxBytes").text(values['bytesin']);
			}
		},
		complete: function() {
			// Schedule the next request when the current one's complete
			vpnRefreshStatusTimer = setTimeout(refreshVpnStatus, 2000);
		},
		error : function (richiesta,stato,errori) {
			//alert("E' evvenuto un errore: refreshVpnStatus = " + stato);
		}
		});
}

function controlVpn(action,id)
{
   ajaxurl = "VpnController.php?action=" + action + "&id=" + id;

   clearTimeout(vpnRefreshStatusTimer);
   
   $("#vpnStatus").removeClass("btn-danger btn-success").addClass("btn-warning");
   $("#vpnStatus").text(action.toUpperCase() + "...");
   
   $.ajax({
      url : ajaxurl,
      beforeSend : function () {
         //$('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      },
      complete: function () {
         //$('#busy1').activity(false);
      },
      success : function (data,stato) {
		values = parseXmlData(data);       
         if (values['result'] != 1)
         {
         alert(values['error']);
         }
      },
      error : function (richiesta,stato,errori) {
      }
   });
   
   vpnRefreshStatusTimer = setTimeout(refreshVpnStatus, 10000);
}

function refreshNodesStatus()
{
	$.ajax({
		url : "VpnController.php?action=clients",
		success : function (data,stato) {
			if (data != null && data !== undefined) 
			{
            values = parseXmlData(data);
            
            if (values['result'] == 1)
            {
               $("#nodesStatus").html(values['html']);
            }
			}
		},
		complete: function() {
			// Schedule the next request when the current one's complete
			setTimeout(refreshNodesStatus, 3000);
		},
		error : function (richiesta,stato,errori) {
		}
		});
}

function refreshVpnListBox()
{
	$.ajax({
		url : "VpnController.php?action=vpnlistbox",
		success : function (data,stato) {
			if (data != null && data !== undefined) 
			{
            values = parseXmlData(data);
               
            if (values['result'] == 1)
            {
               $("#select_vpn").html(values['html']);
            }
			}
		},
		complete: function() {
		},
		error : function (richiesta,stato,errori) {
		}
		});
}

function selectVpn()
{
   $( "#select_vpn" ).change(function() {
      alert("switch to vpn " + $(this).val());
      $.ajax({
      url : 'VpnController.php?action=select&id=' + $(this).val(),
      beforeSend : function () {
         //$('#busy1').activity({valign: 'top', segments: 10, steps: 3, width:5, space: 0, length: 3, color: '#000', speed: 1.5});
      },
      complete: function () {
         //$('#busy1').activity(false);
      },
      success : function (data,stato) {
         location.reload();
      },
      error : function (richiesta,stato,errori) {
         //alert("E' evvenuto un errore. Il stato della chiamata: "+stato);
      }
      });
   });
}

selectVpn();
refreshVpnListBox();


