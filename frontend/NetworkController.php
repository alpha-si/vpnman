<?php
require_once 'Controller.php';

class NetworkController extends Controller
{
   /* class members */
   
   /********************************************************
   Validate action value
   *********************************************************/
   protected function validate_action($value, $action)
   {
      if (!isset($_SESSION['sess_user_id']) || empty($_SESSION['sess_user_id']))
      {
         return false;
      }
         
      if ( !isset($_SESSION['sess_role']) ||
           empty($_SESSION['sess_role']) ||
           ($_SESSION['sess_role'] == 'USER') )
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate network id value
   *********************************************************/
   protected function validate_id($value, $action)
   {
      global $DB;
      
      if (!isset($_SESSION['sess_user_id']) || empty($_SESSION['sess_user_id']))
         return false;
         
      if ($_SESSION['sess_role'] == 'ADMIN')
         return true;
         
      if ($action == "urlif_create")
         return true;
      
      // check if logged user is allowed
      $query = $DB->prepare("SELECT role FROM networks AS n, user2vpn AS uv WHERE n.vpn_id = uv.vpn_id AND uv.user_id = ? AND n.id = ?");
      $query->execute(array($_SESSION['sess_user_id'], $value));
      
      if ($role = $query->fetch(PDO::FETCH_NUM))
      {
         if ($role[0] == 'MANAGER')
         {
            return true;
         }
      }
      
      return false;
   }
   
   /********************************************************
   Validate account id value
   *********************************************************/
   protected function validate_user_id($value, $action)
   {
      global $DB;
      
      if ($value == 0)
      {
         // ok, user is openvpn server
         return true;
      }
      
      $query = $DB->prepare("SELECT * FROM accounts WHERE id = ?");
      $query->execute(array($value));
      
      return ($query->rowCount() > 0);
   }
   
   /********************************************************
   Validate network address value
   *********************************************************/
   protected function validate_network($value, $action)
   {
      if (!ip2long($value))
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate network mask value
   *********************************************************/
   protected function validate_netmask($value, $action)
   {
      if (!ip2long($value))
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate network description value
   *********************************************************/
   protected function validate_description($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate network mapping value
   *********************************************************/
   protected function validate_mapped_to($value, $action)
   {
      if (!empty($value) && !ip2long($value))
      {
         return false;
      }
      
      return true;
   }

   /********************************************************
   Validate network enable value
   *********************************************************/
   protected function validate_enabled($value, $action)
   {
      return true;
   }

   /********************************************************
   Class constructor
   *********************************************************/
   function __construct($id = NULL)
   {
      parent::__construct('networks', $id);
   }
   
   /********************************************************
   List network node (url method)
   *********************************************************/
   function urlif_nodelist()
	{
      global $DB;
      $html = "<option value='0'>OpenVPN Server</option>";
      
      if (isset($_SESSION['sess_vpn_id']))
      {
         $query = $DB->prepare("SELECT id, username FROM accounts WHERE type='NODE' AND vpn_id=?");
         $query->execute(array($_SESSION['sess_vpn_id']));
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $html .= "<option value=" . $row[0] . ">" . $row[1] . "</option>";
         } 
      }
      
		$this->response['html'] = $html;
      $this->response['result'] = true;
      $this->response['error'] = "none";
	}
   
   /********************************************************
   Edit network form (url method)
   *********************************************************/
   function urlif_edit()
   {
      global $_REQUEST;
      global $DB;
	
		if (isset($_REQUEST['id']))
		{
			$query = $DB->prepare("SELECT * FROM networks WHERE id=? LIMIT 1");
         $query->execute(array($_REQUEST['id']));
			
			if ($row = $query->fetch(PDO::FETCH_ASSOC))
			{
            foreach ($row as $key => $value)
            {
               if (($key == 'user_id') && (empty($value)))
               {
                  $value = 0;
               }
               
               $this->response[$key] = $value;
            }
            
            $this->response['result'] = true;
            $this->response['error'] = "none";
			}
         else
         {
            $this->response['error'] = "query error";
         }
		}
      else
      {
         $this->response['error'] = "bad network id";
      }
   }
   
   /********************************************************
   Update network into db (url method)
   *********************************************************/
   function urlif_update()
	{
		global $_REQUEST;
      global $DB;
		
		if (!isset($_REQUEST['id']))
		{
         $this->response['error'] = "missing network id";
			return;
		}
      
      if (!isset($_REQUEST['user_id']))
		{
         $this->response['error'] = "missing account id";
			return;
		}
		
      $values = array();
      $values[0] = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : "";
		$values[1] = isset($_REQUEST['network']) ? $_REQUEST['network'] : ""; 
		$values[2] = isset($_REQUEST['netmask']) ? $_REQUEST['netmask'] : "";
		$values[3] = isset($_REQUEST['description']) ? $_REQUEST['description'] : "";
		$values[4] = isset($_REQUEST['mapped_to']) ? $_REQUEST['mapped_to'] : "";
		$values[5] = isset($_REQUEST['enabled']) ? 1 : 0;
      $values[6] = $_REQUEST['id'];
		
      $query = $DB->prepare("UPDATE networks SET user_id=?, network=?, netmask=?, description=?, mapped_to=?, enabled=? WHERE id=?");
      
      if (!$query)
      {
         $this->response['error'] = "query prepare error";
      }
      else
      {    
         if (!$query->execute($values))
         {
            $this->response['error'] = "query execute error";
         }
         else
         {
            $this->response['result'] = true;
            $this->response['error'] = "network updated";
         }
      } 
	}
   
   /********************************************************
   Insert network into db (url method)
   *********************************************************/
   function urlif_create()
	{
		global $_REQUEST;
      global $DB;
		
      if ( (!isset($_SESSION['sess_vpn_id'])) || ($_SESSION['sess_vpn_id'] == ''))
		{
         $this->response['error'] = "vpn id not set";
         return;
		}
      
		if ( (!isset($_REQUEST['network'])) )
		{
         $this->response['error'] = "network address not specified";
         return;
		}
		
		if ( (!isset($_REQUEST['netmask'])) )
		{
			$this->response['error'] = "network mask not specified";
         return;
		}
		
		if ( (!isset($_REQUEST['user_id'])) )
		{
			$this->response['error'] = "bind account not specified";
         return;
		}
		
      $values = array();
      $values[0] = $_REQUEST['user_id'] > 0 ? $_REQUEST['user_id'] : null;
		$values[1] = $_REQUEST['network']; 
		$values[2] = $_REQUEST['netmask'];
		$values[3] = isset($_REQUEST['description']) ? $_REQUEST['description'] : "";
		$values[4] = isset($_REQUEST['mapped_to']) ? $_REQUEST['mapped_to'] : "";
		$values[5] = isset($_REQUEST['enabled']) ? 1 : 0;
		$values[6] = $_SESSION['sess_vpn_id'];
      
		$query = $DB->prepare("INSERT INTO networks(user_id,network,netmask,description,mapped_to,enabled,vpn_id) VALUES (?,?,?,?,?,?,?)");
      
      if ($query->execute($values))
      {
         $this->response['result'] = true;
         $this->response['error'] = "network created";
      }
      else
      {
         $errors = $query->errorInfo();
         $this->response['error'] = "execute query error: " . $errors[2];
      }
	}
   
   /********************************************************
   Delete network (url method)
   *********************************************************/
   function urlif_delete()
	{
		global $_REQUEST;
      global $DB;
		
		if (!isset($_REQUEST['id']))
		{
         $this->response['error'] = "network id not specified";
         return;
      }
      
      $query = $DB->prepare("DELETE FROM networks WHERE id = ?");
      
      if (!$query->execute(array($_REQUEST['id'])))
      {
         $this->response['error'] = "query error";
         return;
		}
		
		$this->response['error'] = "network deleted!";
      $this->response['result'] = true;
	}
   
   /********************************************************
   List vpn networks (url method)
   *********************************************************/
   function urlif_list()
   {
      global $DB;
      
      $html = "";
      
      if (!isset($_SESSION['sess_vpn_id']))
      {
         $html .= "<tr class=\"odd gradeX\">";
         $html .= "<td colspan='9'>Select a vpn...</td>";
         $html .= "</tr>";
      }
      else
      {
         $query = $DB->prepare("SELECT n.id,n.network,n.netmask,IFNULL(a.username,'SERVER'),n.mapped_to,IFNULL(a.status,'ESTABLISHED'),n.enabled,n.description FROM networks AS n LEFT JOIN accounts AS a ON n.user_id = a.id WHERE n.vpn_id = ?");
         $query->execute(array($_SESSION['sess_vpn_id']));
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $html .= "<tr class=\"odd gradeX\">";
            $html .= "<td>" . $row[0] . "</td>";
            $html .= "<td>" . $row[1] . "</td>";
            $html .= "<td>" . $row[2] . "</td>";
            $html .= "<td>" . $row[3] . "</td>";
            $html .= "<td>" . $row[4] . "</td>";
            $html .= "<td>" . statusIcon($row[5]) . "</td>";
            $html .= "<td>" . enableIcon($row[6]) . "</td>";
            $html .= "<td>" . $row[7] . "</td>";
            $html .= "<td class=\"center\">";
            $html .= "<a href=\"javascript:fillNetworkForm(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">EDIT</a>  ";
            $html .= "<a href=\"javascript:delNetworkPopup(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">DELETE</a>";
            $html .= "</td>";
            $html .= "</tr>";	
         }
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "none";
      $this->response['html'] = $html; //htmlentities($html);
   }
}

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : NULL;
$netctrl = new NetworkController($id);
$netctrl->handle_request();

?>