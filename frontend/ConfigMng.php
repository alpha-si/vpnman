<?php
require_once 'include/config.inc.php';

// database connection 
$DB = NULL;
$VPNMAN_GLOBAL_CONFIG = array();

class ConfigMng
{
   /* class members */
   
   /********************************************************
   Validate action value
   *********************************************************/
   protected function validate_action($value, $action)
   {
      global $_SESSION;
      
      if (!isset($_SESSION['sess_role']) || ($_SESSION['sess_role'] != 'ADMIN'))
      {
         return false;
      }
      
      return true;
   }

   protected function loadGlobalConfig()
   {
      global   $DB;
      global   $VPNMAN_GLOBAL_CONFIG;
      
      $query = $DB->query('SELECT * FROM config');
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         $VPNMAN_GLOBAL_CONFIG[$row[0]] = $row[1];
      }
   }
   
   protected function resultIcon($res)
   {
      $html = "";
      
      if ($res)
      {
         $html = "<button type=\"button\" class=\"btn btn-success btn-circle\"><i class=\"fa fa-check\"></i>";
      }
      else
      {
         $html = "<button type=\"button\" class=\"btn btn-danger btn-circle\"><i class=\"fa fa-times\"></i>";
      }
      
      return $html;
   }
   
   /********************************************************
   Connect to database
   *********************************************************/
   protected function dbConnect()
   {
      global $db_host, $db_name, $db_user, $db_password;
      global $DB;
      
      try 
      {
         $connect_string = "mysql:host=$db_host;dbname=$db_name;charset=utf8";
         $DB = new PDO($connect_string, $db_user, $db_password);
         $this->dberror = "";
         $this->dbok = true;
      } 
      catch (PDOException $e) 
      {
          $this->dberror = $e->getMessage();
          $this->dbok = false;
      }
   }
   
   /********************************************************
   Encode php array to xml http response
   *********************************************************/
   public function sendAjaxResponse($data)
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
 
   /********************************************************
   Class constructor
   *********************************************************/
   function __construct($table = NULL, $id = NULL)
   {
      $this->phpmods = array('libxml','openssl','PDO','pdo_mysql','xmlreader','xmlwriter','zlib');
      $this->dberror = "not connected";
      $this->dbok = false;
      
      $this->dbConnect();
      
      $this->loadGlobalConfig();
   }
   
   /********************************************************
   Check database connection
   *********************************************************/
   public function checkDBConnection(&$resdata)
   {
      $resdata = $this->dberror;
      return $this->dbok;
   }
   
   /********************************************************
   Check certification autority keys
   *********************************************************/
   public function checkCAKeys(&$resdata)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']) ||
          empty($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']))
      {
         $resdata = "CA key file not configured!";
         return false;
      }
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']))
      {
         $resdata = "CA key file not found!";
         return false;
      }
      
      if (!($content = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE'])))
      {
         $resdata = "Can't read CA key file!";
         return false;
      }
      
      if (!openssl_pkey_get_private($content))
      {
         $resdata = "Not valid CA key!";
         return false;
      }
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']) ||
          empty($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']))
      {
         $resdata = "CA certificate file not configured!";
         return false;
      }
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']))
      {
         $resdata = "CA certificate file not found!";
         return false;
      }
      
      if (!($content = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $resdata = "Can't read CA certificate file!";
         return false;
      }
      
      if (!openssl_pkey_get_public($content))
      {
         $resdata = "Not valid CA certificate!";
         return false;
      }
             
      $resdata = "OK!";
      return true;
   }
   
   /********************************************************
   Check PHP required modules
   *********************************************************/
   public function checkPHPMods(&$resdata)
   {
      $res = true;
      $resdata = array();
      
      foreach ($this->phpmods as $modname)
      {
         $resdata[$modname] = true;
         
         if (!extension_loaded($modname))
         {
            $resdata[$modname] = false;
            $res = false;
         }
      }
      
      return $res;
   }
   
   /********************************************************
   Check openvpn.sh execution permission
   *********************************************************/
   public function checkScriptExecution(&$resdata)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      $script = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/openvpn.sh";
      $cmd = "sudo -n $script";

      $out_array = array();
      $ret_val = 0;
      $res_str = exec($cmd, $out_array, $ret_val);
      
      if ((!$res_str) || ($ret_val != 0))
      {
         // command fail
         $out = "";
         foreach ($out_array as $line)
            $out .= $line . ",";
            
         $resdata = "[ErrCode $ret_val] $out";
         return false;
      }
      
      $resdata = "OK!";
      return true;
   }
   
   /********************************************************
   Check root directory write permission
   *********************************************************/
   public function checkWritePermission(&$resdata)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          empty($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) )
      {
         $resdata = "vpn root directory not set";
         return false;
      }
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']))
      {
         $resdata = "vpn root directory not exists";
         return false;
      }
      
      $testfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "delete.me";
      
      if (!touch($testfile))
      {
         $resdata = "Can't write to vpn root directory";
         return false;
      }
         
      unlink($testfile);
      
      $resdata = "OK!";
      return true;
   }
   
   /********************************************************
   Check required iptables chain
   *********************************************************/
   public function checkFirewallChain(&$resdata)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      $script = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/openvpn.sh";
      $cmd = "sudo -n $script checkfw";

      $out_array = array();
      $ret_val = 0;
      $res_str = exec($cmd, $out_array, $ret_val);
      
      if ((!$res_str) || ($ret_val != 0))
      {
         // command fail
         $out = "";
         foreach ($out_array as $line)
            $out .= $line . ",";
            
         $resdata = "[ErrCode $ret_val] $out";
         return false;
      }
      
      $resdata = "OK!";
      return true;
   }
   
   /********************************************************
   Run configuration checks
   *********************************************************/
   public function urlif_checks()
   {
      $html = "";
      
      // check PHP modules dependencies
      $res = $this->checkPHPMods($resdata);
      $html .= "<tr><td>PHP Modules</td><td>" . $this->resultIcon($res) . "</td><td></td></tr>";
      foreach ($resdata as $modname => $modres)
      {
         $html .= "<tr><td>$modname</td><td>" . $this->resultIcon($modres) . "</td><td></td></tr>";
      }
      
      // check database connection
      $res = $this->checkDBConnection($resdata);
      $html .= "<tr><td>Database Connection</td><td>" . $this->resultIcon($res) . "</td><td>" . $resdata . "</td></tr>";
      
      // check certification authority keys
      $res = $this->checkCAKeys($resdata);
      $html .= "<tr><td>Certification Authority Keys</td><td>" . $this->resultIcon($res) . "</td><td>" . $resdata . "</td></tr>";
      
      // check openvpn.sh execution permission
      $res = $this->checkScriptExecution($resdata);
      $html .= "<tr><td>Check Script Execution</td><td>" . $this->resultIcon($res) . "</td><td>" . $resdata . "</td></tr>";
      
      // check root directory write permission
      $res = $this->checkWritePermission($resdata);
      $html .= "<tr><td>Check root directory write permission</td><td>" . $this->resultIcon($res) . "</td><td>" . $resdata . "</td></tr>";
      
      // check required iptables chain
      $res = $this->checkFirewallChain($resdata);
      $html .= "<tr><td>Check \"allow_vpn\" firewall chain</td><td>" . $this->resultIcon($res) . "</td><td>" . $resdata . "</td></tr>";
      
      $html .= "</table>";
      
      $this->response['html'] = $html;
      $this->response['error'] = "";
      $this->response['result'] = true;
   }
   
   /********************************************************
   Build new CA keys 
   *********************************************************/
   public function urlif_buildca()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_COUNTRY_CODE']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_EMAIL']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_LOCALITY']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_ORG_NAME']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_ORG_UNIT']) ||
          !isset($VPNMAN_GLOBAL_CONFIG['SERVER_STATE_PROV']))
      {
         $this->response['error'] = "some configuration parameters are not set";
         return false;
      }
      
      // fill certificate data
      $dn = array(
          "countryName" => $VPNMAN_GLOBAL_CONFIG['SERVER_COUNTRY_CODE'],
          "stateOrProvinceName" => $VPNMAN_GLOBAL_CONFIG['SERVER_STATE_PROV'],
          "localityName" => $VPNMAN_GLOBAL_CONFIG['SERVER_LOCALITY'],
          "organizationName" => $VPNMAN_GLOBAL_CONFIG['SERVER_ORG_NAME'],
          "organizationalUnitName" => $VPNMAN_GLOBAL_CONFIG['SERVER_ORG_UNIT'],
          "commonName" => "vpnman_ca",
          "emailAddress" => $VPNMAN_GLOBAL_CONFIG['SERVER_EMAIL']
      );

      // Generate a new private (and public) key pair
      $privkey = openssl_pkey_new();
      
      if ($privkey == FALSE)
      {
         $this->response['error'] = "create ca keys failed"; 
         return false;
      }
      
      // Extract the private key and store it as ca.key
      $ca_key_file = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "ca.key";
      if (!openssl_pkey_export_to_file ($privkey , $ca_key_file))
      {
         $this->response['error'] = "Export ca private key to $ca_key_file failed"; 
         return false;
      }
      
      // Generate certificate
      $csr = openssl_csr_new($dn, $privkey);
      
      if (!$csr)
      {
         $this->response['error'] = "Careate certificate signing request failed"; 
         return false;
      }
      
      // Self-sign csr
      $crt = openssl_csr_sign($csr, NULL, $privkey, 365);
      
      if (!$crt)
      {
         $this->response['error'] = "Certificate self-signing failed"; 
         return false;
      }
      
      // Extract the public key and store it as ca.crt
      $ca_crt_file = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "ca.crt";
      if (!openssl_x509_export_to_file ($crt , $ca_crt_file))
      {
         $this->response['error'] = "Export ca private key to $ca_crt_file failed"; 
         return false;
      }
      
      // Update configuration with new created ca files
      $query = $DB->prepare("UPDATE config SET param_value = ? WHERE param_name = ?");
      if(!$query->execute(array($ca_crt_file,'CA_CRT_FILE')))
      {
         $this->errstr = "Configuration update failed";
         return false;
      }
      
      $query = $DB->prepare("UPDATE config SET param_value = ? WHERE param_name = ?");
      if(!$query->execute(array($ca_key_file,'CA_KEY_FILE')))
      {
         $this->errstr = "Configuration update failed";
         return false;
      }

      $this->response['error'] = "CA keys created!";
      $this->response['result'] = true;
   }
   
   /********************************************************
   Return current configuration
   *********************************************************/
   public function GetLastHtmlData()
   {
      if (isset($this->response['html']))
      {
         return $this->response['html'];
      }
   }
   
   /********************************************************
   Save configuration
   *********************************************************/
   public function urlif_save()
   {
      global $_REQUEST;
      global $DB;

      $modified = 0;
      
      foreach($_REQUEST as $param_key => $param_value)
      {
         $query = $DB->prepare("UPDATE config SET param_value = ? WHERE param_name = ?");
         
         $res = $query->execute(array($param_value, $param_key));
         
         if ($res && ($query->rowCount() > 0))
         {
            $modified++;
         }
      }
      
      $this->response['error'] = "Configuration updated! (# $modified parameter changed)";
      $this->response['result'] = true;
   }
   
   /********************************************************
   Return current configuration
   *********************************************************/
   public function urlif_getcfg()
   {
      global $DB;
      
      $query = $DB->query("SELECT * FROM config");
   
      $this->response['html'] = "";
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         $param_key = $row[0];
         $param_value = $row[1];
         $param_descr = $row[2];
         
         $this->response['html'] .= "<tr><td>$param_key</td><td><input type=\"text\" title=\"$param_descr\" size=\"50\" class=\"form-control\" name=\"$param_key\" value=\"$param_value\"></td></tr>";
      }
      
      $this->response['error'] = "";
      $this->response['result'] = true;
   }
   
   /********************************************************
   Handle URL request
   *********************************************************/
   public function handle_request()
   {
      global $_REQUEST;
      $res = true;
      
      $this->response['result'] = false;
      $this->response['error'] = "unknown";
      
      if (!isset($_SESSION['sess_user_id']) ||
          !isset($_SESSION['sess_role']) ||
          empty($_SESSION['sess_role']))
      {
         $this->response['error'] = "authentication error"; 
      }
      else
      {
         $this->response['user_id'] = $_SESSION['sess_user_id'];
         $this->response['user_role'] = $_SESSION['sess_role'];
      
         if (isset($_REQUEST['action']))
         {
            $urlif_method = "urlif_" . $_REQUEST['action'];
            
            if (method_exists($this, $urlif_method))
            {
               foreach($_REQUEST as $key => $value)
               {
                  $validate_fun = "validate_" . $key;
                  
                  if (method_exists($this, $validate_fun))
                  {
                     $res = $this->{$validate_fun}($value, $urlif_method);
                     
                     if (!$res)
                     {
                        $this->response['error'] = "invalid value \"$value\" for parameter \"$key\"";
                        break;
                     }
                  }
                  else
                  {
                     //unset($_REQUEST[$key]);
                  }
               }
               
               if ($res)
               {
                  $this->{$urlif_method}();
               }
            }
            else
            {
               $this->response['error'] = "invalid action $urlif_method";
            }
         }
         else
         {
            $this->response['error'] = "no action specified";
         }
      }
      
      $this->sendAjaxResponse($this->response);
   }
   
}

// start session
session_start();
      
$CFG = new ConfigMng();

if (basename($_SERVER['PHP_SELF']) == 'ConfigMng.php')
{
   $CFG->handle_request();
}
else
{
   if (basename($_SERVER['PHP_SELF']) != 'vpnmanapi.php')
   {
      // check whether the session variable SESS_MEMBER_ID is present or not
      if(!isset($_SESSION['sess_username']) || (trim($_SESSION['sess_username']) == '')) 
      {
         header("location: login.php");
         exit();
      }
   }
}

?>
