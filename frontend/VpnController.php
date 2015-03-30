<?php
require_once 'Controller.php';

define("DH_PARAM_SIZE", 1024);

class VpnController extends Controller
{
   /* class members */
   protected $id;
   protected $errstr;

   /********************************************************
   Explode a string using multiple delimiters
   *********************************************************/
   protected function multiexplode($delimiters,$string) 
   {
       $ready = str_replace($delimiters, $delimiters[0], $string);
       $launch = explode($delimiters[0], $ready);
       return  $launch;
   }
   
   /********************************************************
   Validate action value
   *********************************************************/
   protected function validate_action($value, $action)
   {
      $res = false;
      
      switch ($action)
      {
      case "start":
      case "stop":
         if (($_SESSION['sess_role'] == 'ADMIN') || ($_SESSION['sess_role'] == 'MANAGER'))
         {
            $res = true;
         }
         break;
         
      case "create":
      case "delete":
         if ($_SESSION['sess_role'] == 'ADMIN')
         {
            $res = true;
         }
         break;
      
      case "edit":
      case "update":
         if (($_SESSION['sess_role'] == 'ADMIN') || ($_SESSION['sess_role'] == 'MANAGER'))
         {
            $res = true;
         }
         break;
      
      default:
         $res = true;
         break;
      }

      return $res;
   }
   
   /********************************************************
   Validate user id value
   *********************************************************/
   protected function validate_id($value, $action)
   {
      global $DB;
      
      if ($_SESSION['sess_role'] == 'ADMIN')
         return true;
         
      if ($action == "urlif_create")
         return true;
      
      // check if logged user is allowed
      $query = $DB->prepare("SELECT role FROM user2vpn WHERE vpn_id = ? AND user_id = ?");
      $query->execute(array($_SESSION['sess_user_id'], $value));
      
      if ($role = $query->fetch(PDO::FETCH_NUM))
      {
         return true;
      }
      
      return false;
   }
   
   /********************************************************
   Validate vpn listen port value
   *********************************************************/
   protected function validate_vpnListenPort($value, $action)
   {
      if (!is_numeric($value) || ($value <= 0) || ($value >= 65536))
		{
			return false;
		}
      
      return true;
   }
   
   /********************************************************
   Validate vpn management port value
   *********************************************************/
   protected function validate_vpnManagePort($value, $action)
   {
      if (!is_numeric($value) || ($value <= 0) || ($value >= 65536))
		{
			return false;
		}
      
      return true;
   }
   
   /********************************************************
   Validate vpn name value
   *********************************************************/
   protected function validate_vpnName($value, $action)
   {
      return ( (empty($value) == false) && 
               (preg_match('/\s/',$value) == 0) );
   }
   
   /********************************************************
   Validate vpn organization name value
   *********************************************************/
   protected function validate_vpnOrgName($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn organization unit value
   *********************************************************/
   protected function validate_vpnOrgUnit($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn organization mail value
   *********************************************************/
   protected function validate_vpnOrgMail($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn organization country code value
   *********************************************************/
   protected function validate_vpnOrgCountry($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn organization provence/state value
   *********************************************************/
   protected function validate_vpnOrgProv($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn organization city value
   *********************************************************/
   protected function validate_vpnOrgCity($value, $action)
   {
      return !empty($value);
   }
   
   /********************************************************
   Validate vpn protocol type value
   *********************************************************/
   protected function validate_vpnProtoType($value, $action)
   {
      if (($value == "udp") || ($value == "tcp"))
      {
         return true;
      }
      
      return false;
   }
   
   /********************************************************
   Validate vpn authentication type value
   *********************************************************/
   protected function validate_vpnAuthType($value, $action)
   {
      if (($value == "PASS_ONLY") ||
          ($value == "CERT_ONLY") ||
          ($value == "CERT_PASS"))
      {
         return true;
      }
      
      return false;
   }
   
   /********************************************************
   Validate vpn network address value
   *********************************************************/
   protected function validate_vpnNetAddr($value, $action)
   {
      if (!ip2long($value))
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate vpn network mask value
   *********************************************************/
   protected function validate_vpnNetMask($value, $action)
   {
      if (!ip2long($value))
      {
         return false;
      }
         
      return true;
   }
   
   /********************************************************
   Validate vpn organization city value
   *********************************************************/
   protected function validate_conent($value, $action)
   {
      return true;
   }
   
   /********************************************************
   Class constructor
   *********************************************************/
   function __construct($id = NULL)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      
      parent::__construct('vpn', $id);
         
      if (isset($id))
      {
         $this->fields['vpn_home_dir'] = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $id;
		}
      else
      {
         // default values
         $this->fields['org_name'] = $VPNMAN_GLOBAL_CONFIG['SERVER_ORG_NAME'];
         $this->fields['org_unit'] = $VPNMAN_GLOBAL_CONFIG['SERVER_ORG_UNIT'];
         $this->fields['org_mail'] = $VPNMAN_GLOBAL_CONFIG['SERVER_EMAIL'];
         $this->fields['org_country'] = $VPNMAN_GLOBAL_CONFIG['SERVER_COUNTRY_CODE'];
         $this->fields['org_prov'] = $VPNMAN_GLOBAL_CONFIG['SERVER_STATE_PROV'];
         $this->fields['org_city'] = $VPNMAN_GLOBAL_CONFIG['SERVER_LOCALITY'];  
      }
   }
   
   /********************************************************
   Generate openvpn server configuration
   *********************************************************/
   protected function makeSrvCfg()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      $tmplfile = "";
      $check = 0;
      
      // check if openvpn server configuration file exists
      if (!empty($this->fields['srv_cfg_file']) && file_exists($vpncfg['srv_cfg_file']))
      {
         $tmplfile = $this->fields['srv_cfg_file'];
      }
      else
      {
         // get template configuration file
         $tmplfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "/template/srvcfg.tmpl"; 
      }      
      
      if (!file_exists($tmplfile))
      {
         $this->errstr = "[createOpenvpnServerConf] missing file \"$tmplfile\"";
         return false;
      }
      
      if (!file_exists($tmplfile) ||
          !($content = file_get_contents($tmplfile)))
      {
         $this->errstr = "[createOpenvpnServerConf] unable to read template \"$tmplfile\"";
         return false;
      }
      
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
         
         if (sscanf($line, "proto %s", $tmp1) == 1)
         {
            $lines[$i] = "proto " . strtolower($this->fields['proto_type']);
            $check |= 0x01;
         }
         else if ((sscanf($line, "port %d", $tmp1) == 1) || 
                  (sscanf($line, "port %s", $tmp1) == 1) )
         {
            $lines[$i] = "port " . $this->fields['srv_port'];
            $check |= 0x02;
         }
         else if ((sscanf($line, "management localhost %d", $tmp1) == 1) ||
                  (sscanf($line, "management localhost %s", $tmp1) == 1))
         {
            $lines[$i] = "management localhost " . $this->fields['mng_port'];
            $check |= 0x04;
         }
         else if (sscanf($line, "ca %s", $tmp1) == 1)
         {
            $lines[$i] = "ca " . $VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'];
            $check |= 0x08;
         }
         else if (sscanf($line, "cert %s", $tmp1) == 1)
         {
            $lines[$i] = "cert " . $this->fields['crt_file'];
            $check |= 0x10;
         }
         else if (sscanf($line, "key %s", $tmp1) == 1)
         {
            $lines[$i] = "key " . $this->fields['key_file'];
            $check |= 0x20;
         }
         else if (sscanf($line, "dh %s", $tmp1) == 1)
         {
            $lines[$i] = "dh " . $this->fields['dh_file'];
            $check |= 0x40;
         }
         else if (sscanf($line, "up %s", $tmp1) == 1)
         {
            $lines[$i] = "up " . $this->fields['vpn_home_dir'] . "/script_up.sh";
            $check |= 0x80;
         }
         else if (sscanf($line, "down %s", $tmp1) == 1)
         {
            $lines[$i] = "down " . $this->fields['vpn_home_dir'] . "/script_down.sh";
            $check |= 0x100;
         }
         else if (sscanf($line, "server %s %s", $tmp1, $tmp1) == 2)
         {
            $lines[$i] = "server " . $this->fields['net_addr'] . " " . $this->fields['net_mask'];
            $check |= 0x200;
         }
      }
      
      $lines[$i++] = "";
      $lines[$i++] = "### auto-generated ###";
      
      switch ($this->fields['auth_type'])
      {
         case "PASS_ONLY":
            $lines[$i++] = "client-cert-not-required";
            break;
         case "CERT_ONLY":
            $lines[$i++] = "tls-server";
            $lines[$i++] = "auth-user-pass-optional";
            break;
         default:
            $lines[$i++] = "tls-server";
            break;
      }
         
      if ($check != 0x3FF)
      {
         $this->errstr = "[createOpenvpnServerConf] template config parsing error($check)";
         return false;
      }
       
      return $lines;
   }
   
   /********************************************************
   Generate openvpn start_up script
   *********************************************************/
   protected function makeStartUpScript()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      $tmplfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "/template/script_up.tmpl";     
      
      if (!file_exists($tmplfile))
      {
         $this->errstr = "[makeStartUpScript] missing file \"$tmplfile\"";
         return false;
      }
      
      if (!file_exists($tmplfile) ||
          !($content = file_get_contents($tmplfile)))
      {
         $this->errstr = "[makeStartUpScript] unable to read template \"$tmplfile\"";
         return false;
      }
      
      $items = explode("%", $content);
      $values = array();
      
      foreach ($items as &$item)
      {
         if (isset($VPNMAN_GLOBAL_CONFIG[$item]))
         {
            $item = $VPNMAN_GLOBAL_CONFIG[$item];
            continue;
         }
         
         $key = strtolower($item);
         
         if (isset($this->fields[$key]))
         {
            $item = $this->fields[$key];
         }
      }
      
      $content = implode($items);
      
      $outfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'] . "/script_up.sh";
      
      if (!file_put_contents($outfile, $content))
      {
         $this->errstr = "[makeStartUpScript] unable to write \"$outfile\"";
         return false;
      }
      
      chmod($outfile,0755);

      return true;
   }
   
   /********************************************************
   Generate openvpn down script
   *********************************************************/
   protected function makeDownScript()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      $tmplfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "/template/script_down.tmpl";     
      
      if (!file_exists($tmplfile))
      {
         $this->errstr = "[makeDownScript] missing file \"$tmplfile\"";
         return false;
      }
      
      if (!file_exists($tmplfile) ||
          !($content =file_get_contents($tmplfile)))
      {
         $this->errstr = "[makeDownScript] unable to read template \"$tmplfile\"";
         return false;
      }
      
      $items = explode("%", $content);
      $values = array();
      
      foreach ($items as &$item)
      {
         if (isset($VPNMAN_GLOBAL_CONFIG[$item]))
         {
            $item = $VPNMAN_GLOBAL_CONFIG[$item];
            continue;
         }
         
         $key = strtolower($item);
         
         if (isset($this->fields[$key]))
         {
            $item = $this->fields[$key];
         }
      }
      
      $content = implode($items);
      
      $outfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'] . "/script_down.sh";
      
      if (!file_put_contents($outfile, $content))
      {
         $this->errstr = "[makeDownScript] unable to write \"$outfile\"";
         return false;
      }
      
      chmod($outfile,0755);

      return true;
   }
   
   /********************************************************
   Generate ovpnctrl configuration file
   *********************************************************/
   protected function makeOvpnctrlConf()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      $tmplfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "/template/ovpnctrl.tmpl";    
      
      if (!file_exists($tmplfile))
      {
         $this->errstr = "[makeOvpnctrlConf] missing file \"$tmplfile\"";
         return false;
      }
      
      if (!file_exists($tmplfile) ||
          !($content = file_get_contents($tmplfile)))
      {
         $this->errstr = "[makeOvpnctrlConf] unable to read template \"$tmplfile\"";
         return false;
      }
      
      $items = explode("%", $content);
      $values = array();
      
      foreach ($items as &$item)
      {
         if (isset($VPNMAN_GLOBAL_CONFIG[$item]))
         {
            $item = $VPNMAN_GLOBAL_CONFIG[$item];
            continue;
         }
         
         $key = strtolower($item);
         
         if (isset($this->fields[$key]))
         {
            $item = $this->fields[$key];
         }
      }
      
      $content = implode($items);
      
      $outfile = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'] . "/ovpnctrl.conf";
      
      if (!file_put_contents($outfile, $content))
      {
         $this->errstr = "[makeOvpnctrlConf] unable to write \"$outfile\"";
         return false;
      }

      return true;
   }
   
   /********************************************************
   Make client configuration (@@@TODO)
   *********************************************************/
   public function makeClientCfg($cfg, &$data)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $errstr;

      $lines = array();
      $i = 0;
      $data = "";
      
      $lines[$i++] = "remote " . $VPNMAN_GLOBAL_CONFIG['SERVER_ADDR'] . " " . $this->fields['srv_port'];
      $lines[$i++] = "proto " . strtolower($this->fields['proto_type']);
      $lines[$i++] = "dev tun";
      $lines[$i++] = "client";
      $lines[$i++] = "ca " . $VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'];
      
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
   Write openvpn server configuration file
   *********************************************************/
   protected function writeSrvCfg(&$lines)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      
      if (!isset($this->fields['id']) || empty($this->fields['id']))
      {
         $this->errstr = "[writeSrvCfgFile] invalid vpn id";
         return false;
      }
      
      if (!isset($this->fields['srv_cfg_file']) || 
          empty($this->fields['srv_cfg_file']) ||
          ($this->fields['srv_cfg_file'] == ""))
      {
         $this->fields['srv_cfg_file'] = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'] . "/vpn" . $this->fields['id'] . "_server.conf";
      }

      // write configuration file
      /*
      if (!($cfgfile = fopen($this->fields['srv_cfg_file'], 'w')))
      {
         $this->errstr = "[writeSrvCfgFile] open \"" . $this->fields['srv_cfg_file'] . "\" fail";
         return false;
      }
      
      foreach ($lines as $cfgline)
      {
         fwrite($cfgfile, $cfgline);
      }
      
      fclose($cfgfile);
      */
      
      if (!file_put_contents($this->fields['srv_cfg_file'], implode("\n",$lines)))
      {
         $this->errstr = "[writeSrvCfgFile] write \"" . $this->fields['srv_cfg_file'] . "\" fail";
         return false;
      }
      
      if (!file_exists($this->fields['srv_cfg_file']))
      {
         $this->errstr = "[writeSrvCfgFile] \"" . $this->fields['srv_cfg_file'] . "\" not created";
         return false;
      }
      
      $query = $DB->prepare("UPDATE vpn SET srv_cfg_file = ? WHERE id = ?");
      $query->execute(array($this->fields['srv_cfg_file'], $this->fields['id']));
      
      return $this->fields['srv_cfg_file'];
   }
   
   protected function scanPort ($portNumber, $proto = "udp") 
   {  
      $result = 0;
      
      if ($proto = "udp")
      {
         $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);  
      }
      else
      {
         $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      }
      
      if ($sock) 
      {   
         $res = socket_bind($sock, '127.0.0.1', $portNumber);
         
         if ($res)
         {
            $result = 1;
         }
         
         socket_close($sock);   
      }  
      
      return $result;
   }

   /********************************************************
   Creates server private key, certificate and DH parameters
   *********************************************************/
   protected function createServerKeys()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      // check vpn directory
      if (!file_exists($this->fields['vpn_home_dir']))
      {
         $this->errstr = "[createServerKeys] failed opening '" . $this->fields['vpn_home_dir'] . "'"; 
         return false;
      }
      
      // check vpn id
      if (!isset($this->fields['id']))
      {
         $this->errstr = "[createServerKeys] bad vpn id"; 
         return false;
      }
      
      // build keys directory
      $keys_dir = $this->fields['vpn_home_dir'] . "/keys";
      
      if (!file_exists($keys_dir) && !mkdir($keys_dir, 0775))
      {
         $this->errstr = "[createServerKeys] mkdir \"$keys_dir\" failed"; 
         return false;
      }
   
      ////////////////////////////////////////////////////////////// 
      // Fill in data for the distinguished name to be used in the cert
      // You must change the values of these keys to match your name and
      // company, or more precisely, the name and company of the person/site
      // that you are generating the certificate for.
      // For SSL certificates, the commonName is usually the domain name of
      // that will be using the certificate, but for S/MIME certificates,
      // the commonName will be the name of the individual who will use the
      // certificate.
      $dn = array(
          "countryName" => $this->fields['org_country'],
          "stateOrProvinceName" => $this->fields['org_prov'],
          "localityName" => $this->fields['org_city'],
          "organizationName" => $this->fields['org_name'],
          "organizationalUnitName" => $this->fields['org_unit'],
          "commonName" => $this->fields['description'] . "_srv",
          "emailAddress" => $this->fields['org_mail']
      );

      // Generate a new private (and public) key pair
      $privkey = openssl_pkey_new();
      
      if ($privkey == FALSE)
      {
         $this->errstr = "[createServerKeys] create pkey failed"; 
         return false;
      }

      // Generate a certificate signing request
      $csr = openssl_csr_new($dn, $privkey);

      if ($csr == FALSE)
      {
         $this->errstr = "[createServerKeys] create csr failed"; 
         return false;
      }
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']) ||
          !($content = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE'])))
      {
         $this->errstr = "[createServerKeys] unable to open " . $VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']; 
         return false;
      }
      
      // We need our CA cert and its private key
      $caprivkey = array($content, $VPNMAN_GLOBAL_CONFIG['CA_KEY_PASSPHRASE']);

      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']) || 
          !($cacert = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $this->errstr = "[createServerKeys] unable to open " . $VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']; 
         return false;
      }

      // Configure parameter for sign
      $SSLcnf = array('encrypt_key' => true,
              'private_key_type' => OPENSSL_KEYTYPE_RSA,
              'digest_alg' => 'sha1',
              'x509_extensions' => 'v3_ca'
              );
              
      // Sign the server certificate
      if (!($sscert = openssl_csr_sign($csr, $cacert, $caprivkey, 365, $SSLcnf)))
      {
         $this->errstr = "[createServerKeys] server certificate sign failed"; 
         return false;
      }
         
      // Store server private key in the keys directory
      $this->fields['key_file'] = $keys_dir . "/vpn" . $this->fields['id'] . "_srv.key";
      if (!openssl_pkey_export_to_file($privkey, $this->fields['key_file']))
      {
         $this->errstr = "[createServerKeys] create \"" . $this->fields['key_file'] . "\" failed"; 
         return false;
      }
      
      // Store server certificate sign request in the keys directory
      $this->fields['SRV_CSR_FILE'] = $keys_dir . "/vpn" . $this->fields['id'] . "_srv.csr";
      if (!openssl_csr_export_to_file($csr, $this->fields['SRV_CSR_FILE']))
      {
         $this->errstr = "[createServerKeys] create \"" . $this->fields['SRV_CSR_FILE'] . "\" failed";
         return false;
      }
      // Store server certificate in the keys directory
      $this->fields['crt_file'] = $keys_dir . "/vpn" . $this->fields['id'] . "_srv.crt";
      if (!openssl_x509_export_to_file($sscert, $this->fields['crt_file'], false))
      {
         $this->errstr = "[createServerKeys] create \"" . $this->fields['crt_file'] . "\" failed";
         return false;
      }

      return true;
   }

   /********************************************************
   Creates client private key and certificate
   *********************************************************/
   public function createClientKeys($acccfg, &$auth_key, &$auth_csr, &$auth_crt)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      // check vpn id
      if (!isset($this->fields['id']))
      {
         $this->errstr = "[createClientKeys] bad vpn id"; 
         return false;
      }
      
      // Fill in data for the distinguished name to be used in the cert
      // You must change the values of these keys to match your name and
      // company, or more precisely, the name and company of the person/site
      // that you are generating the certificate for.
      // For SSL certificates, the commonName is usually the domain name of
      // that will be using the certificate, but for S/MIME certificates,
      // the commonName will be the name of the individual who will use the
      // certificate.
      $dn = array(
          "countryName" => $acccfg['VPN_CLIENT_CRT_COUNTRY'],
          "stateOrProvinceName" => $acccfg['VPN_CLIENT_CRT_STATE_PROV'],
          "localityName" => $acccfg['VPN_CLIENT_CRT_LOCALITY'],
          "organizationName" => $acccfg['VPN_CLIENT_CRT_ORG'],
          "organizationalUnitName" => $acccfg['VPN_CLIENT_CRT_UNIT'],
          "commonName" => $acccfg['VPN_CLIENT_COMMON_NAME'],
          "emailAddress" => $acccfg['VPN_CLIENT_EMAIL']
      );

	//@@@DEBUG
		foreach ($dn as $key => $value)
		{
			if (empty($value))
			{
				$this->errstr = "[createClientKeys] missing $key value!"; 
         	return false;
			}
		}
	//@@@@@@@

      // Generate a new private (and public) key pair
      $privkey = openssl_pkey_new();
      
      if ($privkey == FALSE)
      {
         $this->errstr = "[createClientKeys] create private key failed"; 
         return false;
      }

      // Generate a certificate signing request
      $csr = openssl_csr_new($dn, $privkey);
      
      if ($csr == FALSE)
      {
         $this->errstr = "[createClientKeys] create csr failed"; 
         return false;
      }
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']) ||
          !($content = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE'])))
      {
         $this->errstr = "[createClientKeys] unable to open " . $VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']; 
         return false;
      }
      
      // We need our CA cert and its private key
      $caprivkey = array($content, $VPNMAN_GLOBAL_CONFIG['CA_KEY_PASSPHRASE']);
      
      if (!file_exists($VPNMAN_GLOBAL_CONFIG['CA_KEY_FILE']) ||
          !($cacert = file_get_contents($VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE'])))
      {
         $this->errstr = "[createClientKeys] unable to open " . $VPNMAN_GLOBAL_CONFIG['CA_CRT_FILE']; 
         return false;
      }

      // Configure parameter for sign
      $SSLcnf = array('encrypt_key' => true,
              'private_key_type' => OPENSSL_KEYTYPE_RSA,
              'digest_alg' => 'sha1',
              'x509_extensions' => 'v3_ca'
              );
              
      // Sign the client certificate
      $sscert = openssl_csr_sign($csr, $cacert, $caprivkey, 365, $SSLcnf);
      
      if (!$sscert)
      {
         $this->errstr = "[createClientKeys] unable to sign certificate"; 
         return false;
      }
         
      // Export client private key
      if (!openssl_pkey_export($privkey, $auth_key))
      {
         $this->errstr = "[createClientKeys] create \"$pkeyfile\" failed"; 
         return false;
      }
      
      // Export client certificate sign request
      if (!openssl_csr_export($csr, $auth_csr))
      {
         $this->errstr = "[createClientKeys] create \"$csrfile\" failed";
         return false;
      }
      // Export client certificate
      if (!openssl_x509_export($sscert, $auth_crt, false))
      {
         $this->errstr = "[createClientKeys] create \"$crtfile\" failed";
         return false;
      }

      return true;
   }

   /********************************************************
   Creates DH parameters
   *********************************************************/
   protected function createDHpars($dh_bits = DH_PARAM_SIZE)
   {
      $keydir = $this->fields['vpn_home_dir'] . "/keys";
      
      if (!file_exists($keydir))
      {
         return false;
      }
      
      $dh_filename = $keydir . "/vpn" . $this->fields['id'] . "_dh" . $dh_bits . ".pem";
      
      $cmd = "openssl dhparam -out $dh_filename $dh_bits";
      
      exec($cmd);
      
      if (!file_exists($dh_filename))
      {
         return false;
      }
      
      return $dh_filename;
   }

   /********************************************************
   Auto select server listening port (@@@TODO)
   *********************************************************/
   protected function autoSelectServerPort()
   {
      global $DB;
      
      $port = 0;
      
      $query = $DB->query("SELECT srv_port,mng_port FROM vpn");
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         if ($port == 0)
         {
            $port = $row[0] + 1;
            
            if ($port == $row[1])
            {
               $port++;
            }
         }
         else
         {
            if ($port == $row[0] || $port == $row[1])
            {
               $port = $row[0] + 1;
            
               if ($port == $row[1])
               {
                  $port++;
               }
            }            
         }
      }
         
      return $port;
   }

   /********************************************************
   Auto select server management port (@@@TODO)
   *********************************************************/
   protected function autoSelectManagementPort()
   {
      return autoSelectServerPort();
   }

   /********************************************************
   Check server listening port (@@@TODO)
   *********************************************************/
   protected function checkServerPort($port)
   {
      //@@@TODO: check if port is used
      
      return (($port > 1024) && ($port < 65536));
   }

   /********************************************************
   Check server management port (@@@TODO)
   *********************************************************/
   protected function checkManagementPort($port)
   {
      //@@@TODO: check if port is used
      
      return (($port > 1024) && ($port < 65536));
   }
   
   /********************************************************
   Get all vpn params
   *********************************************************/
   public function GetVpnData()
   {
      return $this->fields;
   }
   
   /********************************************************
   Get single vpn param
   *********************************************************/
   public function GetVpnParam($param_name)
   {
      return $this->fields[$param_name];
   }
   
   /********************************************************
   Create vpn
   *********************************************************/
   public function Create(&$values)
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      
      $cfg = array();

      $this->fields['srv_port'] = $values['vpnListenPort'];
      $this->fields['mng_port'] = $values['vpnManagePort'];
      $this->fields['description'] = $values['vpnName'];
      $this->fields['proto_type'] = $values['vpnProtoType'];
      $this->fields['auth_type'] = $values['vpnAuthType'];
      $this->fields['org_name'] = $values['vpnOrgName'];
      $this->fields['org_unit'] = $values['vpnOrgUnit'];
      $this->fields['org_mail'] = $values['vpnOrgMail'];
      $this->fields['org_country'] = $values['vpnOrgCountry'];
      $this->fields['org_prov'] = $values['vpnOrgProv'];
      $this->fields['org_city'] = $values['vpnOrgCity'];
      $this->fields['net_addr'] = $values['vpnNetAddr'];
      $this->fields['net_mask'] = $values['vpnNetMask'];
      
      // insert the new vpn into DB
      $query = $DB->prepare(
         "INSERT INTO vpn(description,srv_port,mng_port,proto_type,auth_type,org_name,org_unit,org_mail,org_country,org_prov,org_city,net_addr,net_mask) " . 
         "VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
         
      $query->execute(array($this->fields['description'],
                            $this->fields['srv_port'],
                            $this->fields['mng_port'],
                            $this->fields['proto_type'],
                            $this->fields['auth_type'],
                            $this->fields['org_name'],
                            $this->fields['org_unit'],
                            $this->fields['org_mail'],
                            $this->fields['org_country'],
                            $this->fields['org_prov'],
                            $this->fields['org_city'],
                            $this->fields['net_addr'],
                            $this->fields['net_mask']));
      
      if (($this->fields['id'] = $DB->lastInsertId()) == 0)
      {
         $this->errstr = "[create_vpn] insert new vpn fail";
         return false;
      }
      
      //$this->fields['description'] = $this->vpnid;

      // make vpn configuration directory
      $this->fields['vpn_home_dir'] = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'];
      
      if (!file_exists($this->fields['vpn_home_dir']) && !mkdir($this->fields['vpn_home_dir'], 0775))
      {
         $this->errstr = "[create_vpn] make \"" . $this->fields['vpn_home_dir'] . "\" fail";
         return false;
      }
      
      // build vpn server keys
      $res = $this->createServerKeys();
      
      if (!$res)
      {
         return false;
      }
      
      $this->fields['dh_file'] = $this->createDHpars(DH_PARAM_SIZE);
      
      if ($this->fields['dh_file'] == false)
      {
         $this->errstr = "[create_vpn] make dh parameters fail";
         return false;
      }
      
      // create openvpn server configuration file
      $lines = $this->makeSrvCfg();
      
      if ($lines == false)
      {
         return false;
      }
      
      if ($this->writeSrvCfg($lines) == false)
      {
         return false;
      }
      
      // insert openvpn configuration into DB
      $query = $DB->prepare("UPDATE vpn SET srv_cfg_file = ? WHERE id = ?");
      
      if(!$query->execute(array($this->fields['srv_cfg_file'], $this->fields['id'])))
      {
         $this->errstr = "[create_vpn] query fail";
         return false;
      }
      
      // create ovpnctrl config file
      if (!$this->makeOvpnctrlConf())
      {
         return false;
      }
      
      // create script_up.sh
      if (!$this->makeStartUpScript())
      {
         return false;
      }
      
      // create script_down.sh
      if (!$this->makeDownScript())
      {
         return false;
      }

      return true;
   }
   
   
   
   /********************************************************
   Start openvpn server
   *********************************************************/
   public function urlif_start()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          empty($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          !isset($this->fields['srv_cfg_file']) ||
          empty($this->fields['srv_cfg_file']) )
      {
         $this->response['error'] = "can't make start command";
         return false;
      }
      
      $script = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/openvpn.sh";
      $vpncfg = $this->fields['srv_cfg_file'];

      $cmd = "sudo -n $script start $vpncfg";

      $out_array = array();
      $ret_val = 0;
      $res_str = exec($cmd, $out_array, $ret_val);
      
      if ((!$res_str) || ($ret_val != 0))
      {
         // command fail
         $out = "";
         foreach ($out_array as $line)
            $out .= $line . ",";
            
         $this->response['error'] = "[ErrCode $ret_val] CMD=\"$cmd\" OUT=\"$out\"";
      }
      else
      {
         $this->response['result'] = 1;
         $this->response['error'] = "vpn start!";
      } 
   }

   /********************************************************
   Stop openvpn server
   *********************************************************/
   public function urlif_stop()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      
      if (!isset($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          empty($VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH']) ||
          !isset($this->fields['srv_cfg_file']) ||
          empty($this->fields['srv_cfg_file']) )
      {
         $this->response['error'] = "can't make stop command";
         return false;
      }
      
      $script = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "bin/openvpn.sh";
      $vpncfg = $this->fields['srv_cfg_file'];

      $cmd = "sudo -n $script stop $vpncfg";

      $out_array = array();
      $ret_val = 0;
      $res_str = exec($cmd, $out_array, $ret_val);
      
      if ((!$res_str) || ($ret_val != 0))
      {
         // command fail
         $out = "";
         foreach ($out_array as $line)
            $out .= $line . ",";
            
         $this->response['error'] = "[ErrCode $ret_val] CMD=\"$cmd\" OUT=\"$out\"";
      }
      else
      {
         $this->response['result'] = 1;
         $this->response['error'] = "vpn stop!";
      } 
   }
   
   /********************************************************
   Get last error
   *********************************************************/
   public function GetLastError()
   {
      return $this->errstr;
   }
   
   /********************************************************
   Edit vpn form (url method)
   *********************************************************/
   function urlif_edit()
	{
		global $_REQUEST;
      
      foreach ($this->fields as $key => $value)
      {
         $this->response[$key] = $value;
      }
      
      $this->response['error'] = "none";
      $this->response['result'] = true;
	}
   
   /********************************************************
   Update vpn (url method)
   *********************************************************/
   public function urlif_update()
   {
      global $_REQUEST;
      global $DB;
      
		if (!isset($_REQUEST['id']))
		{
         $this->response['error'] = "vpn id not specified";
			return;
		}
      
      if (  !isset($_REQUEST['vpnName']) ||
            !isset($_REQUEST['vpnListenPort']) ||
            !isset($_REQUEST['vpnManagePort']) ||
            !isset($_REQUEST['vpnOrgName']) ||
            !isset($_REQUEST['vpnOrgName']) ||
            !isset($_REQUEST['vpnOrgUnit']) ||
            !isset($_REQUEST['vpnOrgMail']) ||
            !isset($_REQUEST['vpnOrgCountry']) ||
            !isset($_REQUEST['vpnOrgProv']) ||
            !isset($_REQUEST['vpnOrgCity']) ||
            !isset($_REQUEST['vpnOrgName']) ||
            !isset($_REQUEST['vpnProtoType']) ||
            !isset($_REQUEST['vpnAuthType']) ||
            !isset($_REQUEST['vpnNetAddr']) ||
            !isset($_REQUEST['vpnNetMask']) )
		{
         $this->response['error'] = "not all needed parameters specified";
			return;
		}
		
      $vpn_id = $_REQUEST['id']; 
		$descr = isset($_REQUEST['vpnName']) ? $_REQUEST['vpnName'] : ""; 
		$org_name = isset($_REQUEST['vpnOrgName']) ? $_REQUEST['vpnOrgName'] : "";
		$org_unit = isset($_REQUEST['vpnOrgUnit']) ? $_REQUEST['vpnOrgUnit'] : "";
		$org_mail = isset($_REQUEST['vpnOrgMail']) ? $_REQUEST['vpnOrgMail'] : "";
		$org_country = isset($_REQUEST['vpnOrgCountry']) ? $_REQUEST['vpnOrgCountry'] : "";
		$org_prov = isset($_REQUEST['vpnOrgProv']) ? $_REQUEST['vpnOrgProv'] : "";
		$org_city = isset($_REQUEST['vpnOrgCity']) ? $_REQUEST['vpnOrgCity'] : ""; 
		$proto_type = isset($_REQUEST['vpnProtoType']) ? $_REQUEST['vpnProtoType'] : "udp";
      $auth_type = isset($_REQUEST['vpnAuthType']) ? $_REQUEST['vpnAuthType'] : "CERT_PASS";
		$srv_port = isset($_REQUEST['vpnListenPort']) ? $_REQUEST['vpnListenPort'] : "";
      $mng_port = isset($_REQUEST['vpnManagePort']) ? $_REQUEST['vpnManagePort'] : "";
      $net_addr = isset($_REQUEST['vpnNetAddr']) ? $_REQUEST['vpnNetAddr'] : "";
      $net_mask = isset($_REQUEST['vpnNetMask']) ? $_REQUEST['vpnNetMask'] : "";
      
      $query = $DB->prepare(
         "UPDATE vpn SET description=?,org_name=?,org_unit=?,org_mail=?," .
         "org_country=?,org_prov=?,org_city=?,proto_type=?," . 
         "auth_type=?,srv_port=?,mng_port=?,net_addr=?,net_mask=? " .
         "WHERE id = ?");
      
      $res = $query->execute(array($descr,$org_name,$org_unit,$org_mail,$org_country,$org_prov,$org_city,$proto_type,$auth_type,$srv_port,$mng_port,$net_addr,$net_mask,$vpn_id));
			
		if (!$res)
      {
         $this->response['error'] = "query error";
      }
      else
      {
         $this->response['error'] = "vpn updated!";
         $this->response['result'] = true;
      }
   }
   
   /********************************************************
   Delete vpn (url method)
   *********************************************************/
   function urlif_delete()
   {
      global $VPNMAN_GLOBAL_CONFIG;
      global $DB;
      
      if (!isset($this->fields['id']) || empty($this->fields['id']))
      {
         $this->response['error'] = "vpn id not specified";
         return;
      }
      
      if (!isset($this->fields['vpn_home_dir']) || empty($this->fields['vpn_home_dir']))
      {
         $this->fields['vpn_home_dir'] = $VPNMAN_GLOBAL_CONFIG['VPN_ROOT_PATH'] . "vpn" . $this->fields['id'];
      }
      
      if (!isset($this->fields['VPN_HOME_KEYS_DIR']) || empty($this->fields['VPN_HOME_KEYS_DIR']))
      {
         $this->fields['VPN_HOME_KEYS_DIR'] =  $this->fields['vpn_home_dir'] . "/keys";
      }
      
      if (!isset($this->fields['key_file']) || empty($this->fields['key_file']))
      {
         $this->fields['key_file'] =  $this->fields['VPN_HOME_KEYS_DIR'] . "/vpn" . $this->fields['id'] . "_srv.key";
      }
      
      if (!isset($this->fields['csr_file']) || empty($this->fields['csr_file']))
      {
         $this->fields['csr_file'] =  $this->fields['VPN_HOME_KEYS_DIR'] . "/vpn" . $this->fields['id'] . "_srv.csr";
      }
      
      if (!isset($this->fields['crt_file']) || empty($this->fields['crt_file']))
      {
         $this->fields['crt_file'] =  $this->fields['VPN_HOME_KEYS_DIR'] . "/vpn" . $this->fields['id'] . "_srv.crt";
      }
      
      if (!isset($this->fields['dh_file']) || empty($this->fields['dh_file']))
      {
         $this->fields['dh_file'] =  $this->fields['VPN_HOME_KEYS_DIR'] . "/vpn" . $this->fields['id'] . "_dh" . DH_PARAM_SIZE . ".pem";
      }
      
      if (!isset($this->fields['srv_cfg_file']) || empty($this->fields['srv_cfg_file']))
      {
         $this->fields['srv_cfg_file'] =  $this->fields['vpn_home_dir'] . "/vpn" . $this->fields['id'] . "_server.conf";
      }
      
      if (!isset($this->fields['script_up_file']) || empty($this->fields['script_up_file']))
      {
         $this->fields['script_up_file'] =  $this->fields['vpn_home_dir'] . "/script_up.sh";
      }
      
      if (!isset($this->fields['script_down_file']) || empty($this->fields['script_down_file']))
      {
         $this->fields['script_down_file'] =  $this->fields['vpn_home_dir'] . "/script_down.sh";
      }
      
      if (!isset($this->fields['ovpnctrl_file']) || empty($this->fields['ovpnctrl_file']))
      {
         $this->fields['ovpnctrl_file'] =  $this->fields['vpn_home_dir'] . "/ovpnctrl.conf";
      }
      
      if (file_exists($this->fields['ovpnctrl_file']) && !unlink($this->fields['ovpnctrl_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['ovpnctrl_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['script_up_file']) && !unlink($this->fields['script_up_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['script_up_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['script_down_file']) && !unlink($this->fields['script_down_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['script_down_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['srv_cfg_file']) && !unlink($this->fields['srv_cfg_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['srv_cfg_file'] . "' fail";
         return;
      }
           
      if (file_exists($this->fields['dh_file']) && !unlink($this->fields['dh_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['dh_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['crt_file']) && !unlink($this->fields['crt_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['crt_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['csr_file']) && !unlink($this->fields['csr_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['csr_file'] . "' fail";
         return;
      }
      
      if (file_exists($this->fields['key_file']) && !unlink($this->fields['key_file']))
      {
         $this->response['error'] = "delete '" . $this->fields['key_file'] . "' fail";
         return;
      }

      $query = $DB->prepare("DELETE FROM vpn WHERE id = ?");
      
      if (!$query->execute(array($this->fields['id'])))
      {
         $this->response['error'] = "query error";
         return;
      }
      
      $this->response['error'] = "vpn deleted!";
      
      if (file_exists($this->fields['VPN_HOME_KEYS_DIR']) && !rmdir($this->fields['VPN_HOME_KEYS_DIR']))
      {
         $this->response['error'] = "delete '" . $this->fields['VPN_HOME_KEYS_DIR'] . "' fail";
         //return false;
      }
      
      if (file_exists($this->fields['vpn_home_dir']) && !rmdir($this->fields['vpn_home_dir']))
      {
         $this->response['error'] = "delete '" . $this->fields['vpn_home_dir'] . "' fail";
         //return false;
      }

      $this->response['result'] = true;
   }
   
   /********************************************************
   Get vpn server configuraton (url method)
   *********************************************************/
   function urlif_getcfg()
   {
      global $_REQUEST;

      if (!isset($_REQUEST['id']))
		{
         $this->response['error'] = "vpn id not specified";
			return;
		}
      
      if (!empty($this->fields['srv_cfg_file']) && file_exists($this->fields['srv_cfg_file']))
      {
         $this->response['data'] = file_get_contents($this->fields['srv_cfg_file']);
         $this->response['result'] = true;
      }
      else
      {
         $this->response['error'] = "cannot read configuration file";
      }      
   }
   
   /********************************************************
   Save vpn server configuraton (url method)
   *********************************************************/
   function urlif_savecfg()
   {
      global $_REQUEST;
      
      if (!isset($_REQUEST['content']) || 
          !isset($_REQUEST['id']))
      {
         $this->response['error'] = "bad request";
         return;
      }
      
      $lines = explode("\n", $_REQUEST['content']);
      
      if (!$this->writeSrvCfg($lines))
      {
         $this->response['error'] = "cannot write configuration";
         return;
      }
      
      $this->response['result'] = true;
      $this->response['error'] = "server configuration updated";    
   }
  
   /********************************************************
   Create vpn (url method)
   *********************************************************/
   function urlif_create()
   {
      global $_REQUEST;

      if ($this->Create($_REQUEST))
      {
         $this->response['result'] = true;
         $this->response['error'] = "vpn created!";    
      }
      else
      {
         $errstr = $this->GetLastError();
         $this->urlif_delete();
         $this->response['result'] = false;
         $this->response['error'] = $errstr;
      }
   }
   
   /********************************************************
   Get vpn list box content (url method)
   *********************************************************/
   function urlif_vpnlistbox()
   {
      global $DB;
      $html = "";
      
      if (isset($_SESSION['sess_user_id']) && isset($_SESSION['sess_role']))
      {
         if ($_SESSION['sess_role'] == 'ADMIN')
         {
            $query = $DB->query("SELECT id,description,'ADMIN' FROM vpn ORDER BY id");
         }
         else
         {
            $query = $DB->prepare("SELECT v.id, v.description, uv.role FROM vpn AS v, user2vpn AS uv WHERE uv.vpn_id = v.id AND uv.user_id=? ORDER BY id");
            $query->execute(array($_SESSION['sess_user_id']));
         }
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            if (!isset($_SESSION['sess_vpn_id']))
            {
               $_SESSION['sess_vpn_id'] = $row[0];
            }
            
            if ($row[0] == $_SESSION['sess_vpn_id'])
            {
               $html .= "<option value=\"" . $row[0] . "\" selected>VPN" . $row[0] . " - " . $row[1] . "</option>";
            }
            else
            {
               $html .= "<option value=\"" . $row[0] . "\">VPN" . $row[0] . " - " . $row[1] . "</option>";
            }
         }
      }
      
      $this->response['html'] = $html;
      $this->response['error'] = "";
      $this->response['result'] = true;
   }
   
   /********************************************************
   List vpn form (url method)
   *********************************************************/
   function urlif_getgpspos()
	{
      global $DB;
      
      if (!isset($_SESSION['sess_vpn_id']))
      {
         $this->response['error'] = "vpn not selected";
         return;
      }
      
		$data = array();

		$query = $DB->prepare("SELECT username,coordinates FROM accounts WHERE LENGTH(coordinates) > 0 AND vpn_id = ?");
      $query->execute(array($_SESSION['sess_vpn_id']));
      
		while ($row = $query->fetch(PDO::FETCH_NUM))
		{
			$data[$row[0]] = $row[1];
		}
      
      $this->response = $data;
	}
   
   /********************************************************
   Get vpn status (url method)
   *********************************************************/
   function urlif_status()
	{
      global $DB;

      $this->response['id'] = $_SESSION['sess_vpn_id'];
      $this->response['status'] = "NOT RUNNING";
      $this->response['start_time'] = "-";
      $this->response['bytesin'] = "-";
      $this->response['bytesout'] = "-";
      
      if (isset($_SESSION['sess_vpn_id']))
      {
         $query = $DB->prepare("SELECT * FROM server_info WHERE vpn_id=?");
         $query->execute(array($_SESSION['sess_vpn_id']));
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $this->response[$row[0]] = $row[1];
         }
         
         $query = $DB->prepare("SELECT TIMEDIFF(NOW(), STR_TO_DATE(value, '%Y-%m-%d %T')) FROM server_info WHERE attribute = 'start_time' AND vpn_id=?");
         $query->execute(array($_SESSION['sess_vpn_id']));
         
         if ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $this->response['start_time'] = $row[0];
         }
         
         $this->response['bytesin'] = number_format(((float)$this->response['bytesin'] / (float)(1048576)), 2) . " MB";
         $this->response['bytesout'] = number_format(((float)$this->response['bytesout'] / (float)(1048576)), 2) . " MB";
         
         $query = $DB->prepare("SELECT TIME_TO_SEC(TIMEDIFF(NOW(), STR_TO_DATE(value, '%Y-%m-%d %T'))) FROM server_info WHERE attribute = 'keepalive' AND vpn_id = ?");
         $query->execute(array($_SESSION['sess_vpn_id']));
         
         $this->response['status'] = 'NOT RUNNING';
         if ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $this->response['status'] = $row[0] < 10 ? 'RUNNING' : 'NOT RUNNING';
         }
      }
      
		$this->response['result'] = true;
	}
   
   /********************************************************
   Get vpn node status (url method)
   *********************************************************/
   function urlif_clients()
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
         $query = 
            "SELECT c.id as session_id, u.username, u.description,u.hw_serial,u.type,u.status,c.start_time,c.end_time,c.bytes_received,c.bytes_sent,c.trusted_ip as real_ip,c.ifconfig_pool_remote_ip as vpn_ip,u.links " .
            "FROM accounts as u, connection_history as c " .
            "WHERE c.user_id = u.id AND u.vpn_id = ? AND c.id IN (SELECT MAX(id) FROM connection_history WHERE user_id = c.user_id) " .
            "ORDER BY u.id,c.id desc";
            
         $stmt = $DB->prepare($query);
         $stmt->execute(array($_SESSION['sess_vpn_id']));
         
         while ($row = $stmt->fetch(PDO::FETCH_NUM))
         {
            $html .= "<tr class=\"odd gradeX\">";
            $html .= "<td>" . $row[1] . "</td>"; // username
            $html .= "<td>" . $row[2] . "</td>";	// description
            $html .= "<td>" . $row[3] . "</td>";	// hw serial
            $html .= "<td>" . $row[4] . "</td>";	// node type
            $html .= "<td>" . statusIcon($row[5], $row[11], $row[12]) . "</td>";	// status
            $html .= "<td>" . $row[6] . "</td>";	// start time
            $html .= "<td>" . $row[7] . "</td>";	// end time
            $html .= "<td>" . number_format(((float)$row[8] / (float)(1048576)), 2) . " MB" . "</td>";	// bytes rx
            $html .= "<td>" . number_format(((float)$row[9] / (float)(1048576)), 2) . " MB" . "</td>";	// bytes tx
            $html .= "<td>" . $row[10] . "</td>";// real ip
            $html .= "<td>" . $row[11] . "</td>";// vpn ip
            //$html .= "<td class=\"center\"><button type=\"button\" class=\"btn btn-primary btn-xs\">ONLINE</button></td>";
            $html .= "</tr>";
         }
      }
      
      $this->response['html'] = $html;
      $this->response['error'] = "";
      $this->response['result'] = true;
	}
   
   /********************************************************
   List vpn form (url method)
   *********************************************************/
   function urlif_list()
   {
      global $DB;
      $html = "";
      
      if (!isset($_SESSION['sess_role']))
      {
         $this->response['error'] = "forbidden operation";
         return;
      }
      
      if ($_SESSION['sess_role'] == 'ADMIN')
      {
         $query = $DB->query("SELECT id,description,srv_port,mng_port,template,proto_type,auth_type,net_addr,net_mask FROM vpn");
      }
      else
      {
         $query = $DB->prepare("SELECT id,description,srv_port,mng_port,template,proto_type,auth_type,net_addr,net_mask,uv.role FROM vpn AS v, user2vpn AS uv WHERE v.id = uv.vpn_id AND uv.user_id = ?");
         $query->execute(array($_SESSION['sess_user_id']));
      }
      
      while ($row = $query->fetch(PDO::FETCH_NUM))
      {
         $role = ($_SESSION['sess_role'] == 'ADMIN') ? 'ADMIN' : $row[9];
         
         $html .= "<tr class=\"odd gradeX\">";
         $html .= "<td>" . $row[0] . "</td>";
         $html .= "<td>" . $row[1] . "</td>";
         $html .= "<td>" . vpnStatusIcon("") . "</td>";
         $html .= "<td>" . $row[7] . "</td>";
         $html .= "<td>" . $row[8] . "</td>";
         $html .= "<td>" . $row[2] . "</td>";
         $html .= "<td>" . $row[3] . "</td>";
         $html .= "<td>" . $row[5] . "</td>";
         $html .= "<td>" . $row[6] . "</td>";
         $html .= "<td class=\"center\">";
         
         if ($role != 'USER')
         {
            $html .= "<a href=\"javascript:fillVpnForm(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">EDIT</a>  ";
         }
         else
         {
            $html .= "<a href=\"#\" class=\"btn btn-primary btn-xs disabled\">EDIT</a>  ";
         }
         
         if ($role == 'ADMIN')
         {
            $html .= "<a href=\"javascript:delVpnPopup(" . $row[0] . ");\" class=\"btn btn-primary btn-xs\">DELETE</a>";
         }
         else
         {
            $html .= "<a href=\"#\" class=\"btn btn-primary btn-xs disabled\">DELETE</a>";
         }
         $html .= "</td>";
         $html .= "</tr>";	
      }
      
      $this->response['html'] = $html;
      $this->response['error'] = "none";
      $this->response['result'] = true;
   }
   
   function urlif_select()
   {
      if (isset($_REQUEST['id']))
      {
         $_SESSION['sess_vpn_id'] = $_REQUEST['id'];
         session_write_close();
      }
   }
}

if (basename($_SERVER['PHP_SELF']) == 'VpnController.php')
{
   $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : NULL;
   $vpnctrl = new VpnController($id);
   $vpnctrl->handle_request();
}

?>
