<?php
ob_start();
session_start();

include("include/config.inc.php");
 
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];
	
/* database connection */
$connect_string = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
$DB = new PDO($connect_string, $db_user, $db_password);
$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$query = $DB->prepare(
 "SELECT id, username, password, u.role AS user_role, vpn_id, uv.role AS vpn_role " .
 "FROM users AS u LEFT JOIN user2vpn AS uv ON u.id = uv.user_id " . 
 "WHERE username = ? AND password = ? ORDER BY uv.role DESC, uv.vpn_id DESC");
 
$query->execute(array($username, $password));
 
if($query->rowCount() == 0) 
{
   //die($query);
   // User not found. So, redirect to login_form again.
   header('Location: login.php');
}
else
{
   $userData = $query->fetch();
   /* 
   $hash = hash('sha256', $userData['salt'] . hash('sha256', $password) );
    
   if($hash != $userData['password']) // Incorrect password. So, redirect to login_form again.
   {
	die("error!");
       header('Location: login.html');
   }
   else
   */
   //{ 
   // Redirect to home page after successful login.
   session_regenerate_id();
   $_SESSION['sess_user_id'] = $userData['id'];
   $_SESSION['sess_username'] = $userData['username'];
   
   if (isset($userData['vpn_id']) && !empty($userData['vpn_id']))
   {
      $_SESSION['sess_vpn_id'] = $userData['vpn_id'];
   }
   else
   {
      unset($_SESSION['sess_vpn_id']);
   }
   
   if (isset($userData['vpn_role']) && !empty($userData['vpn_role']))
   {
      $_SESSION['sess_role'] = $userData['vpn_role'];
   }
   else
   {
      $_SESSION['sess_role'] = $userData['user_role'];;
   }
   
   session_write_close();
   header('Location: index.php');
	}
//}
?>
