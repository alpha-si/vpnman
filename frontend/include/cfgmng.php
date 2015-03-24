<?php 
/* cfgmng.php
 * Configuration Manager
 */

include("include/config.inc.php");

function statusIcon( $status, $addr = "", $url = "" )
{
   $html = "";
   switch ($status)
   {
   case 'DISCONNECTED':
      $html =  "<button type=\"button\" class=\"btn btn-danger btn-xs disabled\">OFFLINE</button>";
      break;
   case 'ESTABLISHED':
      if ($url == "")
      {
         $html =  "<button type=\"button\" class=\"btn btn-success btn-xs disabled\">ONLINE</button>";
      }
      else
      {
         $html = "<a href='" . str_replace("%ADDR",$addr,$url) . "' class=\"btn btn-success btn-xs\">ONLINE</button>";
      }
      
      break;
   case 'CONNECTING':
      $html =  "<button type=\"button\" class=\"btn btn-warning btn-xs disabled\">CONNECTING</button>";
      break;
   default:
      $html =  "<button type=\"button\" class=\"btn btn-danger btn-xs disabled\">UNKNOWN</button>";
      break;
   }
   
   return $html;
}

function enableIcon( $enabled )
{
   $html = "";
   
   if ($enabled)
   {
      $html = "<button type=\"button\" class=\"btn btn-success btn-circle\"><i class=\"fa fa-check\"></i>";
   }
   else
   {
      $html = "<button type=\"button\" class=\"btn btn-danger btn-circle\"><i class=\"fa fa-times\"></i>";
   }
   
   return $html;
}

function linkIcons( $url, $account_id )
{
   $html = "<table><tr>";
   
   if ($account_id != "")
   {
      $html .= "<td><a href='AccountController.php?action=download&id=" . $account_id . "'><img src='img/icon_zipbox.png'></a></td>";
   }
   
   if ($url != "")
   {
      $html .= "<td><a href='" . $url . "' class=\"btn btn-success btn-circle\"><i class=\"fa fa-link\"></i><td>";
   }
   
   $html .= "</tr></table>";
         
   return $html;
}

function vpnStatusIcon( $status )
{
   $html = "";
   switch ($status)
   {
   case 'DISCONNECTED':
      $html =  "<button type=\"button\" class=\"btn btn-danger btn-xs\">OFFLINE</button>";
      break;
   case 'ESTABLISHED':
      $html =  "<button type=\"button\" class=\"btn btn-success btn-xs\">ONLINE</button>";
      break;
   case 'CONNECTING':
      $html =  "<button type=\"button\" class=\"btn btn-warning btn-xs\">CONNECTING</button>";
      break;
   default:
      $html =  "<button type=\"button\" class=\"btn btn-danger btn-xs\">UNKNOWN</button>";
      break;
   }
   
   return $html;
}

function sendAjaxResponse($data)
{
   header('Content-Type: text/xml');     
   echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";     
   echo "<response>\n";
   
   foreach ($data as $key => $value) 
   {
      echo "<symbol name=\"" . $key . "\">" . htmlspecialchars($value) . "</symbol>\n";
   }
   
   echo "</response>\n";     
   exit;
}

function parseTemplate(&$env, &$content)
{
   $res = true;
   $items = explode("%", $content);
   $values = array();
   
   $env_upcase = array_change_key_case($env, CASE_UPPER);
      
   foreach ($items as &$item)
   {
      $key = strtoupper($item);
      
      if (isset($env_upcase[$key]))
      {
         $item = $env_upcase[$key];
      }
   }
      
   $content = implode($items);
   
   return $res;
}
 
// start session
session_start();

// check whether the session variable SESS_MEMBER_ID is present or not
if(!isset($_SESSION['sess_username']) || (trim($_SESSION['sess_username']) == '')) 
{
   header("location: login.php");
   exit();
}

// database connection 
$connect_string = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
$DB = new PDO($connect_string, $db_user, $db_password);
$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$VPNMAN_GLOBAL_CONFIG = array();

function get_global_config()
{
   global   $DB;
   global   $VPNMAN_GLOBAL_CONFIG;
   
   $query = $DB->query('SELECT * FROM config');
   
   while ($row = $query->fetch(PDO::FETCH_NUM))
   {
      $VPNMAN_GLOBAL_CONFIG[$row[0]] = $row[1];
   }
}

function get_vpn_config($conn)
{
   $vpncfg = array();
   return $vpncfg;
}

get_global_config();

$VPNMAN_GLOBAL_CONFIG['DB_HOST'] = $db_host;
$VPNMAN_GLOBAL_CONFIG['DB_USER'] = $db_user;
$VPNMAN_GLOBAL_CONFIG['DB_PASS'] = $db_password;
$VPNMAN_GLOBAL_CONFIG['DB_NAME'] = $db_name;

?>
