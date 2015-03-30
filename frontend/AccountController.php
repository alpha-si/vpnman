<?php
require_once 'include/zip.lib.php';
require_once 'Controller.php';
require_once 'VpnController.php';

class AccountController extends Controller
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
         
      if (!isset($_SESSION['sess_role']) || empty($_SESSION['sess_role']))
      {
         return false;
      }
      
      if (($_SESSION['sess_role'] == 'USER') && ($value != 'list'))
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate username value
   *********************************************************/
   protected function validate_username($value, $action)
   {
      return ( (empty($value) == false) && 
               (preg_match('/\s/',$value) == 0) );
   }

   /********************************************************
   Validate password value
   *********************************************************/
   protected function validate_passwd($value, $action)
   {
      global $_REQUEST;
      
      if (isset($_REQUEST['auth_type']) && ($_REQUEST['auth_type'] == 'CERT_ONLY'))
      {
         // password is not used
         return true;
      }
      
      return (!empty($value));
   }

   /********************************************************
   Validate description value
   *********************************************************/
   protected function validate_description($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate hw_serial value
   *********************************************************/
   protected function validate_hw_serial($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate coordinates value
   *********************************************************/
   protected function validate_coordinates($value, $action)
   {
      return true;
   }

   /********************************************************
   Validate account type value
   *********************************************************/
   protected function validate_type($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate links value
   *********************************************************/
   protected function validate_links($value, $action)
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
   Validate authentication type value
   *********************************************************/
   protected function validate_auth_type($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Validate account id value
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
   Class constructor
   *********************************************************/
   function __construct($id = NULL)
   {
      parent::__construct('accounts', $id);
      
      if (isset($this->fields['vpn_id']) && !empty($this->fields['vpn_id']))
      {
         $this->vpn = new VpnController($this->fields['vpn_id']);
      }
      else
      {
         // default values
         $this->data['status'] = "UNKNOWN";
         $this->data['enabled'] = "1";
         $this->data['type'] = "CLIENT";
         $this->data['auth_type'] = "CERT_PASS";
         
         if (isset($_SESSION['sess_vpn_id']))
         {
            $this->fields['vpn_id'] = $_SESSION['sess_vpn_id'];
            $this->vpn = new VpnController($_SESSION['sess_vpn_id']);
            $this->fields['auth_type'] = $this->vpn->GetVpnParam('auth_type');
         }
      }
   }
   
   /********************************************************
   Make client configuration file
   *********************************************************/
   protected function makeAccountCfg($cfg, &$data)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $errstr;

      $lines = array();
      $i = 0;
      $data = "";
      
      $lines[$i++] = "remote " . $cfg['VPN_SERVER_ADDR'] . " " . $this->vpn->GetVpnParam('srv_port');
      $lines[$i++] = "proto " . strtolower($this->vpn->GetVpnParam('proto_type'));
      $lines[$i++] = "dev tun";
      $lines[$i++] = "client";
      $lines[$i++] = "ca " . $cfg['CA_CERT_FILENAME'];
      
      if ($cfg['AUTH_TYPE'] != "PASS_ONLY")
      {
         $lines[$i++] = "tls-client";
         $lines[$i++] = "cert " . $cfg['CERT_FILENAME'];
         $lines[$i++] = "key " . $cfg['PKEY_FILENAME'];
      }
      
      if ($cfg['AUTH_TYPE'] != "CERT_ONLY")
      {
         $lines[$i++] = "auth-user-pass";
      }
      
      $lines[$i++] = "persist-tun";
      $lines[$i++] = "persist-key";
      $lines[$i++] = "ping 10";
      $lines[$i++] = "comp-lzo";
      $lines[$i++] = "verb 4";
      $lines[$i++] = "mute 10";
      $lines[$i++] = "resolv-retry infinite";

      foreach ($lines as $cfgline)
      {
         $data .= $cfgline . "\n";
      }
   }
   
   /********************************************************
   Edit account form (url method)
   *********************************************************/
   function urlif_edit()
   {
      if (isset($this->fields['id']) && !empty($this->fields['id']))
      {
         foreach ($this->fields as $key => $value)
         {
            $this->response[$key] = $value;
         }
         
         $this->response['result'] = true;
         $this->response['error'] = "none";
      }
      else
      {
         $this->response['error'] = "bad account id";
      }
   }
   
   /********************************************************
   Update account data
   *********************************************************/
   public function urlif_update()
   {
      global $_REQUEST;
      global $DB;
      
      if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
		{
         $this->response['error'] = "missing account id";
			return;
		}
		
      $values = array();
		$values[0] = isset($_REQUEST['username']) ? $_REQUEST['username'] : ""; 
		$values[1] = isset($_REQUEST['passwd']) ? $_REQUEST['passwd'] : "";
		$values[2] = isset($_REQUEST['description']) ? $_REQUEST['description'] : "";
		$values[3] = isset($_REQUEST['hw_serial']) ? $_REQUEST['hw_serial'] : "";
		$values[4] = ((isset($_REQUEST['type'])) && ($_REQUEST['type'] == "CLIENT")) ? "CLIENT" : "NODE";
		$values[5] = isset($_REQUEST['enabled']) ? 1 : 0;
		$values[6] = isset($_REQUEST['coordinates']) ? $_REQUEST['coordinates'] : "";
      $values[7] = isset($_REQUEST['auth_type']) ? $_REQUEST['auth_type'] : "CERT_PASS";
      $values[8] = isset($_REQUEST['links']) ? $_REQUEST['links'] : ""; 
		$values[9] = $_REQUEST['id'];
      
      $query = $DB->prepare("UPDATE accounts SET username=?,passwd=?,description=?,hw_serial=?,type=?,enabled=?,coordinates=?,auth_type=?,links=? WHERE id=?");
      
		if (!$query->execute($values))
      {
         $this->response['error'] = "update query error";
      }
      else
      {
         $this->response['result'] = true;
         $this->response['error'] = "account updated";
      }
   }
   
   /********************************************************
   Create new account
   *********************************************************/
   public function urlif_create()
   {
      global $_REQUEST;
      global $DB;
      
      if (!isset($_SESSION['sess_vpn_id']) || empty($_SESSION['sess_vpn_id']))
      {
         $this->response['error'] = "no vpn selected";
         return;
      }
      
      // check mandatory parameters
      if (!isset($_REQUEST['username']) ||
          !isset($_REQUEST['passwd']) ||
          !isset($_REQUEST['auth_type']) ||
          !isset($_REQUEST['type']) )
      {
         $this->response['error'] = "missing mandatory values";
         return;
      }
		
      $values = array();
		$values[0] = $_REQUEST['username']; 
		$values[1] = $_REQUEST['passwd'];
		$values[2] = isset($_REQUEST['description']) ? $_REQUEST['description'] : "";
		$values[3] = isset($_REQUEST['hw_serial']) ? $_REQUEST['hw_serial'] : "";
		$values[4] = ((isset($_REQUEST['type'])) && ($_REQUEST['type'] == "CLIENT")) ? "CLIENT" : "NODE";
      $values[5] = isset($_REQUEST['enabled']) ? 1 : 0;
      $values[6] = isset($_REQUEST['coordinates']) ? $_REQUEST['coordinates'] : "";
      $values[7] = $_REQUEST['auth_type'];
      $values[8] = $_SESSION['sess_vpn_id'];
      
      $auth_key = "";
      $auth_csr = "";
      $auth_crt = "";
		
      // always create certificate, it may be used if authentication type is changed
      if (true/*$_REQUEST['auth_type'] != "PASS_ONLY"*/)
      {
         $acccfg = array();
         $acccfg['VPN_CLIENT_CRT_COUNTRY'] = $this->vpn->GetVpnParam('org_country');
         $acccfg['VPN_CLIENT_CRT_STATE_PROV'] = $this->vpn->GetVpnParam('org_prov');
         $acccfg['VPN_CLIENT_CRT_LOCALITY'] = $this->vpn->GetVpnParam('org_city');
         $acccfg['VPN_CLIENT_CRT_ORG'] = $this->vpn->GetVpnParam('org_name');
         $acccfg['VPN_CLIENT_CRT_UNIT'] = $this->vpn->GetVpnParam('org_unit');
         $acccfg['VPN_CLIENT_COMMON_NAME'] = $_REQUEST['username'];
         $acccfg['VPN_CLIENT_EMAIL'] = $this->vpn->GetVpnParam('org_mail');
         
         if (!$this->vpn->createClientKeys($acccfg, $auth_key, $auth_csr, $auth_crt))
         {
            $this->response['error'] = $this->vpn->GetLastError();
            return;
         }

			if (empty($auth_key) || empty($auth_crt))
			{
				$this->response['error'] = "[AccountController::Create] empty certificate!";
            return;
			}
      }
      
      // insert new account into db
      $query = $DB->prepare("INSERT INTO accounts(username,passwd,description,hw_serial,type,enabled,coordinates,auth_type,vpn_id) VALUES (?,?,?,?,?,?,?,?,?)");

		if (!$query->execute($values))
      {
         $this->response['error'] = "[AccountController::Create] insert query error";
         return;
      }
      
      $this->fields['id'] =  $DB->lastInsertId();

      // store keys into db
      if (true/*$_REQUEST['auth_type'] != "PASS_ONLY"*/)
      {
         $query = $DB->prepare( "UPDATE accounts SET auth_key=?, auth_csr=?, auth_crt=? WHERE id=?");
         
         if (!$query->execute(array($auth_key, $auth_csr, $auth_crt, $this->fields['id'])))
         {
            $this->response['error'] = "[AccountController::Create] update query error";
            return;
         }
      }

		$this->response['result'] = true;
      $this->response['error'] = "account created!";
   }
   
   /********************************************************
   Delete account
   *********************************************************/
   public function urlif_delete()
   {   
      global $DB;
      global $_REQUEST;
      
      if (!isset($_REQUEST['id']))
      {
         $this->response['error'] = "account id not specified";
         return;
      }
      
      // delete connection history data
      $query = $DB->prepare("DELETE FROM connection_history WHERE user_id=?");
      if (!$query->execute(array($_REQUEST['id'])))
      {
         $this->response['error'] = "query error";
         return;
      }
      
      // delete account
      $query = $DB->prepare("DELETE FROM accounts WHERE id=?");
      if (!$query->execute(array($_REQUEST['id'])))
      {
         $this->response['error'] = "query error";
         return;
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "account deleted!";
   }
   
   /********************************************************
   Download zip configuration file for node account
   *********************************************************/
   public function downloadZipNode()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      global $_REQUEST;
      
      //create the zip
      $zip = new zipfile();
      
      // open template config
      $template = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "template/nodecfg.tmpl";
      if (!file_exists($template) || !($content = file_get_contents($template)))
      {
         $this->response['error'] = "[downloadZipNode] unable to open " . $template; 
         return false;
      }
      
      // build environment array
      $env = array_merge($VPNMAN_GLOBAL_CONFIG, $this->vpn->GetVpnData(), $this->fields);
      
      $basename = $this->vpn->GetVpnParam('description') . "_" . $this->fields['username'];
      $cfgfilename = $basename. ".conf";
      $filesdir = $basename . "_files/";
      $cafilename = $filesdir . "ca.crt";
      $keyfilename = $filesdir . $this->fields['username'] . ".key";
      $crtfilename = $filesdir . $this->fields['username'] . ".crt";
      $upscript = $filesdir . "node_up_script.sh";
      $downscript = $filesdir . "node_down_script.sh";
      
      // parse template
      parseTemplate($env, $content);
      
      // set authentication directives according with account authentication type
      $lines = explode("\n",$content);
   
      $i = 0;
      
      for ($i; $i < count($lines); $i++)
      {
         $line = trim($lines[$i]);
         
         if (empty($line) || $line[0] == '#')
         {
            continue;
         }
         
         $tmp1 = "";
         
         if ((strpos($line, "auth-user-pass") != FALSE) ||
             ($line == "tls-client") ||
             (sscanf($line, "cert %s", $tmp1) == 1) ||
             (sscanf($line, "key %s",$tmp1) == 1) ||
             (sscanf($line, "ca %s",$tmp1) == 1))
         {
            $lines[$i] = "";
         }
      }
      
      $lines[$i++] = "";
      $lines[$i++] = "### auto-generated ###";
      $lines[$i++] = "ca $cafilename";
      
      switch ($this->fields['auth_type'])
      {
         case "PASS_ONLY":
            $lines[$i++] = "auth-user-pass";
            break;
         case "CERT_ONLY":
            $lines[$i++] = "tls-client";
            $lines[$i++] = "cert $crtfilename";
            $lines[$i++] = "key $keyfilename";
            break;
         case "CERT_PASS":
            $lines[$i++] = "auth-user-pass";
            $lines[$i++] = "tls-client";
            $lines[$i++] = "cert $crtfilename";
            $lines[$i++] = "key $keyfilename";
            break;
         default:
            break;
      }
      
      $content = implode("\n",$lines);
      
      // add configuration file to the zip archive
      $zip->addFile($content, $cfgfilename);
      
      // put ca into zip archive
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']) || 
          !($content = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $this->response['error'] = "[downloadZipNode] unable to open " . $VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']; 
         return false;
      }
      $zip->addFile($content, $cafilename);
      
      // add client private key to the zip archive
      $zip->addFile($this->fields['auth_key'], $keyfilename);
      
      // add client certificate to the zip archive
      $zip->addFile($this->fields['auth_crt'], $crtfilename);
      
      // add up script to the zip archive
      $filename = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/node_up_script.sh";
      if (!file_exists($filename) || !($content = file_get_contents($filename)))
      {
         $this->response['error'] = "[downloadZipNode] unable to open " . $filename; 
         return false;
      }
      $zip->addFile($content, $upscript);
      
      // add down script to the zip archive
      $filename = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/node_down_script.sh";
      if (!file_exists($filename) || !($content = file_get_contents($filename)))
      {
         $this->response['error'] = "[downloadZipNode] unable to open " . $filename; 
         return false;
      }
      $zip->addFile($content, $downscript);
   
      // download
      header("Content-type: application/octet-stream");
      header("Content-Disposition: attachment; filename=" . $basename . ".zip");
      header("Content-Description: Files of an applicant");

      //get the zip content and send it back to the browser
      echo $zip->file();
      exit;
   }
   
   /********************************************************
   Download zip configuration file
   *********************************************************/
   public function downloadZipClient()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      global $_REQUEST;
       
      $query = $DB->prepare("SELECT username, type, auth_type, auth_key, auth_csr, auth_crt FROM accounts WHERE id=?");
      $query->execute(array($_REQUEST['id']));
      
      if ($row = $query->fetch())
      {
         //create the zip
         $zip = new zipfile();
    
         $basename = $this->vpn->GetVpnParam('description') . "_" . $row[0];
         $cfgfilename = $basename . ".ovpn";
         $filesdir = $basename . "_files/";
         $cafilename = $filesdir . "ca.crt";
         $keyfilename = $filesdir . $row[0] . ".key";
         $csrfilename = $filesdir . $row[0] . ".csr";
         $crtfilename = $filesdir . $row[0] . ".crt";
         
         // make openvpn configuration file
         $vpnid = 0;
         
         $cfg = array();
         $cfg['VPN_SERVER_ADDR'] = $VPNMAN_GLOBAL_CONFIG['SERVER_ADDR'];
         $cfg['VPN_SERVER_PORT'] = $this->vpn->GetVpnParam('srv_port');
         $cfg['VPN_SERVER_PROTO'] = $this->vpn->GetVpnParam('proto_type');
         $cfg['CA_CERT_FILENAME'] = $cafilename;
         $cfg['AUTH_TYPE'] = $this->fields['auth_type'];
         $cfg['PKEY_FILENAME'] = $keyfilename;
         $cfg['CERT_FILENAME'] = $crtfilename;
         
         $data = "";
         
         $this->makeAccountCfg($cfg, $data);
         
         $zip->addFile($data, $cfgfilename);
         
         $ca_crt = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']);
         
         // make ca certificate file
         $zip->addFile($ca_crt, $cafilename);
         
         // add client private key file
         $zip->addFile($row[3], $keyfilename);
      
         // make client csr file
         $zip->addFile($row[4], $csrfilename);
         
         // make client certificate file
         $zip->addFile($row[5], $crtfilename);
      
         // download
         header("Content-type: application/octet-stream");
         header("Content-Disposition: attachment; filename=" . $basename . ".zip");
         header("Content-Description: Files of an applicant");

         //get the zip content and send it back to the browser
         echo $zip->file();
         exit;
      }
   }
   
   /********************************************************
   Download zip configuration file
   *********************************************************/
   public function urlif_download()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      global $_REQUEST;
      
      $this->response['error'] = "invalid account id";
      
      if (!isset($_REQUEST['id']))
      {
         return;
      }
      
      if ($this->fields['type'] == 'NODE')
      {
         $this->downloadZipNode();
      }
      else
      {
         $this->downloadZipClient();
      }
   }
   
   /********************************************************
   List vpn accounts (url method)
   *********************************************************/
   function urlif_list()
   {
      global $DB;
      $html = "";
      
      if (!isset($_SESSION['sess_vpn_id']))
      {
         $html .= "<tr class=\"odd gradeX\">";
			$html .= "<td colspan='11'>Select a vpn...</td>";
			$html .= "</tr>";
      }
      else
      {
         if ($_SESSION['sess_role'] != 'USER')
         {
            $query = $DB->prepare("SELECT id,username,passwd,description,hw_serial,type,status,enabled,links,auth_type FROM accounts WHERE vpn_id = ?"); 
            $query->execute(array($_SESSION['sess_vpn_id']));
         }
         else
         {
            $query = $DB->prepare("SELECT id,username,passwd,description,hw_serial,type,status,enabled,links,auth_type FROM accounts WHERE vpn_id = ? AND user_id = ?"); 
            $query->execute(array($_SESSION['sess_vpn_id'],$_SESSION['sess_user_id']));
         }
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $html .= "<tr class=\"odd gradeX\">";
            $html .= "<td>" . $row[0] . "</td>";
            $html .= "<td>" . $row[1] . "</td>";
            $html .= "<td>" . $row[2] . "</td>";
            $html .= "<td>" . $row[3] . "</td>";
            $html .= "<td>" . $row[4] . "</td>";
            $html .= "<td>" . $row[5] . "</td>";
            $html .= "<td>" . $row[9] . "</td>";
            $html .= "<td>" . statusIcon($row[6]) . "</td>";
            $html .= "<td>" . enableIcon($row[7]) . "</td>";
            $html .= "<td>" . linkIcons($row[8],$row[0]) . "</td>";
            $html .= "<td class=\"center\">";
            $html .= "<a href=\"javascript:fillAccountForm(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">EDIT</a>  ";
            
            if ($_SESSION['sess_role'] != 'USER')
            {
               $html .= "<a href=\"javascript:delAccountPopup(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">DELETE</a>";
            }
            else
            {
               $html .= "<a href=\"#\" class=\"btn btn-primary btn-xs disabled\">DELETE</a>";
            }
            
            $html .= "</td>";
            $html .= "</tr>";	
         }
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "none";
      $this->response['html'] = $html;
   }
   
   /********************************************************
   Get OpenWRT xml config (url method)
   *********************************************************/
   
   function fillxmlcfg(&$writer)
   {  
      global $DB;
      global $_REQUEST;
      global $VPNMAN_GLOBAL_CONFIG;
      
      $ucicmd_template = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "/template/ucicmd.tmpl"; 
      if (file_exists($ucicmd_template) && ($ucicmd = file_get_contents($ucicmd_template)))
      {
         $env = array_merge($VPNMAN_GLOBAL_CONFIG, $this->vpn->GetVpnData(), $this->fields);
         parseTemplate($env, $ucicmd);
         $writer->startElement('UCICMD');
         $writer->text($ucicmd);
         $writer->endElement();
         /*
         foreach ($env as $k => $v)
         {
            $writer->writeElement($k, $v);
         }
         */
      }

      if (file_exists($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']) &&
          ($cacert = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', 'ca.crt');
         $writer->writeAttribute('type', 'ca certificate');
         $writer->text($cacert);
         $writer->endElement();
      }
      
      $writer->startElement('FILE');
      $writer->writeAttribute('name', 'vpnman_router.crt');
      $writer->writeAttribute('type', 'certificate');
      $writer->text($this->fields['auth_crt']);
      $writer->endElement();
      
      $writer->startElement('FILE');
      $writer->writeAttribute('name', 'vpnman_router.key');
      $writer->writeAttribute('type', 'private key');
      $writer->text($this->fields['auth_key']);
      $writer->endElement();
   } 
   
   /********************************************************
   Convert an OpenVPN configuration directives to the OpenWRT UCI command
   *********************************************************/
   protected function openvpn2uci($ovpndir)
   {
      $ucidir = "";
      
      $ovpndir = trim($ovpndir);
      
      if (!empty($ovpndir) && !($ovpndir[0] == '#'))
      {       
         $elements = explode(' ', $ovpndir);
         
         // replace '-' with '_'
         $elements[0] = str_replace("-", "_", $elements[0]);
         
         if ($elements[0] == 'remote')
         {
            $ucidir = "list 'remote' '";
         }
         else
         {
            $ucidir = "option '" . $elements[0] . "' '";
         }
         
         if (count($elements) < 2)
         {
            switch ($elements[0])
            {
               case 'comp_lzo':
                  $elements[1] = 'yes';
                  break;
               default:
                  $elements[1] = '1';
                  break;
            }
         }
         
         unset($elements[0]);
         
         $ucidir .= implode(" ", $elements) . "'\n";
      }
         
      return $ucidir;
   }
   
   /********************************************************
   Download zip configuration file for node account
   *********************************************************/
   public function downloadWrtXml(&$writer)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      global $_REQUEST;
      
      $result = true;
      
      // build environment array
      $env = array_merge($VPNMAN_GLOBAL_CONFIG, $this->vpn->GetVpnData(), $this->fields);
      
      // 
      // open template config
      //
      
      $template = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "template/nodecfg.tmpl";
      
      if (!file_exists($template) || !($content = file_get_contents($template)))
      {
         $this->response['error'] = "[downloadWrtNode] unable to open " . $template; 
         $result = false;
      }
      
      $basename = $this->vpn->GetVpnParam('description') . "_" . $this->fields['username'];
      $cafilename = "ca.crt";
      $keyfilename = $basename . ".key";
      $crtfilename = $basename . ".crt";
      $loginfilename = $basename . ".pwd";
      $upscript = "node_up_script.sh";
      $downscript = "node_down_script.sh";
      
      // parse template
      parseTemplate($env, $content);
      
      // set authentication directives according with account authentication type
      $lines = explode("\n",$content);
   
      $i = 0;
      
      for ($i; $i < count($lines); $i++)
      {
         $line = trim($lines[$i]);
         
         if (empty($line) || $line[0] == '#')
         {
            continue;
         }
         
         $tmp1 = "";
         
         if ((strpos($line, "auth-user-pass") != FALSE) ||
             ($line == "tls-client") ||
             (sscanf($line, "cert %s", $tmp1) == 1) ||
             (sscanf($line, "key %s",$tmp1) == 1) ||
             (sscanf($line, "ca %s",$tmp1) == 1) ||
             (sscanf($line, "up %s",$tmp1) == 1) ||
             (sscanf($line, "down %s",$tmp1) == 1))
         {
            $lines[$i] = "";
         }
      }
      
      $lines[$i++] = "";
      $lines[$i++] = "### auto-generated ###";
      $lines[$i++] = "up %FILEPATH%/$upscript";
      $lines[$i++] = "down %FILEPATH%/$downscript";
      $lines[$i++] = "ca %FILEPATH%/$cafilename";
      
      switch ($this->fields['auth_type'])
      {
         case "PASS_ONLY":
            $lines[$i++] = "auth-user-pass %FILEPATH%/$loginfilename";
            break;
         case "CERT_ONLY":
            $lines[$i++] = "tls-client";
            $lines[$i++] = "cert %FILEPATH%/$crtfilename";
            $lines[$i++] = "key %FILEPATH%/$keyfilename";
            break;
         case "CERT_PASS":
            $lines[$i++] = "auth-user-pass %FILEPATH%/$loginfilename";
            $lines[$i++] = "tls-client";
            $lines[$i++] = "cert %FILEPATH%/$crtfilename";
            $lines[$i++] = "key %FILEPATH%/$keyfilename";
            break;
         default:
            break;
      }
      
      // 
      // convert OpenVPN configuration to OpenWRT UCI
      //
      
      $writer->startElement('UCICMD');
  
      foreach ($lines as $line)
      {
         $writer->text($this->openvpn2uci($line));
      }
      
      $writer->endElement();
      
      //
      // put ca into xml
      //

      if (file_exists($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']) &&
          ($cacert = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $cafilename);
         $writer->writeAttribute('type', 'certificate');
         $writer->text($cacert);
         $writer->endElement();
      }
      else
      {
      }
     
      //
      // put certificate into xml
      //
      if ($this->fields['auth_type'] != 'PASS_ONLY')
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $crtfilename);
         $writer->writeAttribute('type', 'certificate');
         $writer->text($this->fields['auth_crt']);
         $writer->endElement();
      }
      
      //
      // put private key into xml
      //
      if ($this->fields['auth_type'] != 'PASS_ONLY')
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $keyfilename);
         $writer->writeAttribute('type', 'privatekey');
         $writer->text($this->fields['auth_key']);
         $writer->endElement();
      }
      
      // 
      // add up script to the xml
      //
      
      $filename = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/node_up_script.sh";
      if (file_exists($filename) && ($content = file_get_contents($filename)))
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $upscript);
         $writer->writeAttribute('type', 'script');
         $writer->text($content);
         $writer->endElement();
      }
      else
      {
         $this->response['error'] = "[downloadWrtCfg] unable to read " . $filename;
         $result = false;
      }
      
      // 
      // add down script to the xml
      //
      
      $filename = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/node_down_script.sh";
      if (file_exists($filename) && ($content = file_get_contents($filename)))
      {
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $downscript);
         $writer->writeAttribute('type', 'script');
         $writer->text($content);
         $writer->endElement();
      }
      else
      {
         $this->response['error'] = "[downloadWrtCfg] unable to read " . $filename; 
         $result = false;
      }
      
      if ($this->fields['auth_type'] != 'CERT_ONLY')
      {
         $content = $this->fields['username'] . "\n" . $this->fields['passwd'];
         $writer->startElement('FILE');
         $writer->writeAttribute('name', $loginfilename);
         $writer->writeAttribute('type', 'login');
         $writer->text($content);
         $writer->endElement();   
      }
      
      return $result;
   }
   
}

if (basename($_SERVER['PHP_SELF']) == 'AccountController.php')
{
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : NULL;
   $acc_ctrl = new AccountController($id);
   $acc_ctrl->handle_request();
}

?>
