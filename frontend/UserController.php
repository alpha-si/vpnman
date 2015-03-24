<?php
require_once 'Controller.php';

class UserController extends Controller
{
   /* class members */
   
   /********************************************************
   Validate action value
   *********************************************************/
   protected function validate_action($value, $action)
   {
      /*
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
      */
      return true;
   }
   
   /********************************************************
   Validate user id value
   *********************************************************/
   protected function validate_id($value, $action)
   {
      /*
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
      */
      return true;
   }
   
   /********************************************************
   Validate username value
   *********************************************************/
   protected function validate_username($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate password value
   *********************************************************/
   protected function validate_password($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate role value
   *********************************************************/
   protected function validate_role($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate enabled value
   *********************************************************/
   protected function validate_enabled($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate vpn_id value
   *********************************************************/
   protected function validate_vpn_id($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate user2vpn role value
   *********************************************************/
   protected function validate_u2v_role($value, $action)
   {
      return true;
   }

   /********************************************************
   Class constructor
   *********************************************************/
   function __construct($id = NULL)
   {
      parent::__construct('users', $id);
   }
   
   /********************************************************
   Edit user form (url method)
   *********************************************************/
   function urlif_edit()
   {
      global $_REQUEST;
      global $DB;
	
		if (isset($_REQUEST['id']))
		{
			$query = $DB->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
         $query->execute(array($_REQUEST['id']));
			
			if ($row = $query->fetch(PDO::FETCH_ASSOC))
			{
            foreach ($row as $key => $value)
            {
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
         $this->response['error'] = "bad user id";
      }
   }
   
   /********************************************************
   Update user into db (url method)
   *********************************************************/
   function urlif_update()
	{
		global $_REQUEST;
      global $DB;
		
		if (!isset($_REQUEST['id']))
		{
         $this->response['error'] = "missing user id";
			return;
		}
      
      if (!isset($_REQUEST['username']) ||
          !isset($_REQUEST['password']) ||
          !isset($_REQUEST['role']) )
		{
         $this->response['error'] = "missing mandatory fields";
			return;
		}
		
      $values = array();
		$values[0] = $_REQUEST['username']; 
		$values[1] = $_REQUEST['password'];
		$values[2] = $_REQUEST['role'];
      $values[3] = $_REQUEST['id'];
		
      $query = $DB->prepare("UPDATE users SET username=?, password=?, role=? WHERE id = ?");
      
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
            $this->response['error'] = "user updated";
         }
      } 
	}
   
   /********************************************************
   Insert user into db (url method)
   *********************************************************/
   function urlif_create()
	{
		global $_REQUEST;
      global $DB;
		
		if ( (!isset($_REQUEST['username'])) ||
           (!isset($_REQUEST['password'])) ||
           (!isset($_REQUEST['role'])) )
		{
			$this->response['error'] = "missing mandatory fields";
         return;
		}
		
      $values = array();
      $values[0] = $_REQUEST['username']; 
		$values[1] = $_REQUEST['password'];
		$values[2] = $_REQUEST['role'];
      
		$query = $DB->prepare("INSERT INTO users(username,password,role) VALUES (?,?,?)");
      
      if ($query->execute($values))
      {
         $this->response['result'] = true;
         $this->response['error'] = "user created";
      }
      else
      {
         $this->response['error'] = "execute query error";
      }
	}
   
   /********************************************************
   Delete users(url method)
   *********************************************************/
   function urlif_delete()
	{
		global $_REQUEST;
      global $DB;
      
      $res = false;
		
		if (isset($_REQUEST['id']))
		{
         $query = $DB->prepare("DELETE FROM user2vpn WHERE user_id = ?");
         $res = $query->execute(array($_REQUEST['id']));
         
         $query = $DB->prepare("DELETE FROM users WHERE id = ?");
         $res |= $query->execute(array($_REQUEST['id']));
         
         $query = $DB->prepare("UPDATE accounts SET user_id = 'NULL' WHERE user_id = ?");
         $res |= $query->execute(array($_REQUEST['id']));
		}
		
		if ($res)
      {
         $this->response['result'] = true;
         $this->response['error'] = "user deleted";
      }
      else
      {
         $this->response['error'] = "query error";
      }
	}
   
   /********************************************************
   Edit user2vpn (url method)
   *********************************************************/
   function urlif_u2v_edit()
	{
		global $_REQUEST;
      global $DB;
      
      if (!isset($_REQUEST['id']))
      {
         $this->response['error'] = "user id not specified";
      }
      
      if (!isset($_REQUEST['vpn_id']) || ($_REQUEST['vpn_id'] < 0))
      {
         $query = $DB->prepare("SELECT id AS user_id, username AS user_name,'USER' AS vpn_role FROM users WHERE id = ?");
         $query->execute(array($_REQUEST['id']));
      }
      else
      {
         $query = $DB->prepare("SELECT uv.user_id AS user_id, u.username AS user_name, uv.vpn_id AS vpn_id, uv.role AS vpn_role FROM user2vpn AS uv, users AS u WHERE uv.user_id = u.id AND uv.user_id = ? AND uv.vpn_id = ? LIMIT 1");
         $query->execute(array($_REQUEST['id'], $_REQUEST['vpn_id']));
      }
      
      if ($row = $query->fetch(PDO::FETCH_ASSOC))
      {
         foreach ($row as $key => $value)
         {
            $this->response[$key] = $value;
         }
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "none";
	}
   
   /********************************************************
   Update user2vpn (url method)
   *********************************************************/
   function urlif_u2v_update()
	{
		global $_REQUEST;
      global $DB;
		
      if ( (!isset($_REQUEST['id'])) || 
           (!isset($_REQUEST['vpn_id'])) || 
           (!isset($_REQUEST['u2v_role'])) )
		{
			$this->response['error'] = "missing mandatory fields";
         return;
		}
		
      $values = array();
      $values[0] = $_REQUEST['u2v_role'];
      $values[1] = $_REQUEST['id'];
		$values[2] = $_REQUEST['vpn_id']; 
		
		$query = $DB->prepare("UPDATE user2vpn SET role = ? WHERE user_id = ? AND vpn_id = ?");
      
      if (!$query->execute($values))
      {
         $this->response['error'] = "query error";
      }
      else
      {
         $this->response['result'] = true;
         $this->response['error'] = "vpn role updated!";
      }
	}
   
   /********************************************************
   Create user to vpn role (url method)
   *********************************************************/
   function urlif_u2v_create()
	{
		global $_REQUEST;
      global $DB;
		
		if ( (!isset($_REQUEST['id'])) || 
           (!isset($_REQUEST['vpn_id'])) || 
           (!isset($_REQUEST['u2v_role'])) )
		{
			$this->response['error'] = "missing mandatory fields";
         return;
		}
		
      $values = array();
		$values[0] = $_REQUEST['id']; 
		$values[1] = $_REQUEST['vpn_id'];
		$values[2] = $_REQUEST['u2v_role'];
		
		$query = $DB->prepare("INSERT INTO user2vpn(user_id,vpn_id,role) VALUES (?,?,?)");
			
		if (!$query->execute($values))
      {
         $this->response['error'] = "query error";
      }
      else
      {
         $this->response['result'] = true;
         $this->response['error'] = "vpn role created!";
      }
	}
   
   /********************************************************
   Delete user to vpn role (url method)
   *********************************************************/
   function urlif_u2v_delete()
	{
		global $_REQUEST;
      global $DB;
		
		if ( (!isset($_REQUEST['id'])) || 
           (!isset($_REQUEST['vpn_id'])) )
		{
			$this->response['error'] = "missing mandatory fields";
         return;
		}
		
		$query = $DB->prepare("DELETE FROM user2vpn WHERE user_id = ? AND vpn_id = ?");
      
      if (!$query->execute(array($_REQUEST['id'], $_REQUEST['vpn_id'])))
      {
          $this->response['error'] = "query error";
      }
      else
      {
         $this->response['result'] = true;
         $this->response['error'] = "vpn role deleted!";
      }
	}
   
   /********************************************************
   List users (url method)
   *********************************************************/
   function urlif_list()
   {
      global $DB;
      $html = "";
      
      $query = $DB->query(
         "SELECT u.id,u.username,u.password,u.role AS user_role, vpn_id, uv.description,uv.role AS vpn_role " .
         "FROM users AS u LEFT JOIN (SELECT user_id,role,vpn_id,description FROM user2vpn, vpn WHERE vpn_id = id) AS uv ON u.id = uv.user_id "
         );
         
      $curr_user_id = -1;
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         if ($row[0] != $curr_user_id)
         {  
            $curr_user_id = $row[0];
            
            $html .= "<tr class=\"success\">";
            $html .= "<td>" . $row[0] . "</td>";
            $html .= "<td>" . $row[1] . "</td>";
            $html .= "<td>" . $row[2] . "</td>";
            $html .= "<td colspan=\"2\">" . $row[3] . "</td>";
            $html .= "<td class=\"center\">";
            $html .= "<a href=\"javascript:fillUserForm(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">EDIT</a>  ";
            if ($row[3] != 'ADMIN')
            {
               $html .= "<a href=\"javascript:fillUser2VpnForm('" . $row[0] . "','-1');\" class=\"btn btn-primary btn-xs\">ADD TO VPN</a>  ";
            }
            $html .= "<a href=\"javascript:delUserPopup(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">DELETE</a>";
            $html .= "</td>";
         }

         if ($row[3] != 'ADMIN')
         {
            $html .= "<tr>";
            $html .= "<td></td>";
            $html .= "<td></td>";
            $html .= "<td></td>";
            $html .= "<td>" . $row[5] . "</td>";
            $html .= "<td>" . $row[6] . "</td>";
            $html .= "<td class=\"center\">";
            if (!empty($row[0]) && !empty($row[4]))
            {
               $html .= "<a href=\"javascript:fillUser2VpnForm('" . $row[0] . "','" . $row[4] . "');\" class=\"btn\"><i class=\"fa fa-edit fa-fw\"></i></a>  ";
               $html .= "<a href=\"javascript:delUser2VpnPopup('" . $row[0] . "','" . $row[4] . "');\" class=\"btn\"><i class=\"fa fa-trash-o fa-fw\"></i></a>  ";
            }
            $html .= "</td>";  
            $html .= "</tr>";	
         }
         else
         {
            $html .= "<tr>";
            $html .= "<td></td>";
            $html .= "<td></td>";
            $html .= "<td></td>";
            $html .= "<td><i>all permissions</i></td>";
            $html .= "<td></td>";
            $html .= "<td class=\"center\"></td>";  
            $html .= "</tr>";	
         }
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "none";
      $this->response['html'] = $html; //htmlentities($html);
   }
}

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : NULL;
$userctrl = new UserController($id);
$userctrl->handle_request();

?>