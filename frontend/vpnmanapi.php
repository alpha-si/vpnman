<?php
//phpinfo();
//exit;

require_once 'AccountController.php';
/*
function buildXmlConf($cfg)
{
    @date_default_timezone_set("GMT");
 
    $writer = new XMLWriter();
 
    // Output directly to the user
    $writer->openMemory(); 
    $writer->startDocument('1.0');
    $writer->setIndent(3);
  
   foreach ($cfg as $key => $value)
   {
      $writer->startElement('VPNMAN_ROUTER_CONFIG');
      
   }
    $writer->startElement('VPNMAN_ROUTER_CONFIG');
    $writer->writeElement('VERSION', $cfg['version']);
    $writer->writeElement('RESULT', $cfg['result']);
    $writer->writeElement('ERROR', $cfg['error']);
    $writer->writeElement('UCICMD', $cfg['uci_cmd']);
    $writer->writeElement('PWD', $cfg['ap_pwd']);
    $writer->writeElement('CONF', $cfg['ap_cfg']);
    $writer->writeElement('CA', $cfg['srv_ca']);
    $writer->endDocument();
 
    return $writer->outputMemory();
}
*/
function getwrtcfg()
{
   global $DB;
   global $_REQUEST;
   
   $data = array();
   $data['version'] = 1;
   $data['result'] = 0;
   $data['error'] = "";
   $data['account_id'] = 0;
   
   if (!isset($_REQUEST['username']) ||
       !isset($_REQUEST['password']) ||
       !isset($_REQUEST['accountname']) ||
       !isset($_REQUEST['vpnname']))
   {
      $data['error'] = "invalid request";
   }
   else
   {
      $query = $DB->prepare("SELECT role FROM users WHERE username = ? AND password = ?");
      $query->execute(array($_REQUEST['username'], $_REQUEST['password']));
    
      if ($row = $query->fetch(PDO::FETCH_NUM))
      {
         if ($row[0] == 'ADMIN')
         {
            $query = $DB->prepare("SELECT id, 'ADMIN' AS role FROM vpn WHERE description = ?");
            $query->execute(array($_REQUEST['vpnname']));
         }
         else
         {    
            $query = $DB->prepare("SELECT uv.vpn_id, uv.role FROM users AS u, user2vpn AS uv, vpn AS v WHERE u.id = uv.user_id AND uv.vpn_id = v.id AND u.username = ? AND u.password = ? AND v.description = ?");
            $query->execute(array($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['vpnname']));
         }
         
         $row = $query->fetch(PDO::FETCH_NUM);
    
         if (($row == FALSE) || ($row[1] == 'USER')) 
         {
            $data['error'] = "invalid vpn name";
         }
         else
         { 
            $vpn_id = $row[0];
            $vpn_role = $row[1];
            
            $query = $DB->prepare("SELECT id FROM accounts AS a WHERE enabled=1 AND username = ?  AND vpn_id = ?");
            $query->execute(array($_REQUEST['accountname'], $vpn_id));
            
            if (!($row = $query->fetch(PDO::FETCH_NUM)))
            {
               $data['error'] = "invalid account name";
            }
            else
            {
               $data['account_id'] = $row[0];
               $data['result'] = 1;
            }
         }
      }
      else
      {
         $data['error'] = "permission denied";
      }
   }

   @date_default_timezone_set("GMT");
 
   $writer = new XMLWriter();

   // Output directly to the user
   $writer->openMemory(); 
   $writer->startDocument('1.0');
   $writer->setIndent(3);
   $writer->startElement('VPNMAN_CONFIG');
   $writer->writeAttribute('version', $data['version']);
   $writer->writeAttribute('result', $data['result']);
   $writer->writeAttribute('error', $data['error']);
   
   if ($data['result'] == 1)
   {
      $account = new AccountController($data['account_id']);
      $account->downloadWrtXml($writer);
   }
   
   $writer->endDocument();
   
   return $writer->outputMemory();
}

if (isset($_REQUEST['action']))
{
   switch ($_REQUEST['action'])
   {
      case 'getxmlcfg':
         header('Content-Type: text/xml;'); 
         echo getwrtcfg();
         break;
      default:
         break;
   }
}
?>
