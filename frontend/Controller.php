<?php
require_once 'include/cfgmng.php';

class Controller
{
   /* class members */
 
   /********************************************************
   Class constructor
   *********************************************************/
   function __construct($table = NULL, $id = NULL)
   {
      global $DB;
      
      $this->fields = array();
      $this->errstr = "";
      
      $this->response = array();
      $this->response['result'] = false;
      $this->response['error'] = "unknown";
      $this->response['sess_user_id'] = isset($_SESSION['sess_user_id']) ? $_SESSION['sess_user_id'] : "";
      $this->response['sess_role'] = isset($_SESSION['sess_role']) ? $_SESSION['sess_role'] : "";
      
      if (!isset($table))
      {
         return;
      }
        
      $initialized = false;
      
      if (isset($id))
      {
         $query = $DB->prepare("SELECT * FROM $table WHERE id = ? LIMIT 1");
         $query->execute(array($id));
			
			if ($row = $query->fetch(PDO::FETCH_ASSOC))
			{
            foreach ($row as $key => $value)
            {
               $this->fields[$key] = $value;
            }
            
            $initialized = true;
			}
		}
      
      if (!$initialized)
      {
         $sql = "SHOW COLUMNS FROM $table";
         $query = $DB->query($sql);
         
         while ($row = $query->fetch(PDO::FETCH_NUM))
         {
            $this->fields[$row[0]] = "";
         }  
      }
   }
   
   /********************************************************
   Get all data fields
   *********************************************************/
   public function GetData()
   {
      return $this->fields;
   }
   
   /********************************************************
   Handle URL request
   *********************************************************/
   function handle_request()
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
                  unset($_REQUEST[$key]);
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
      
      sendAjaxResponse($this->response);
   }
   
}


?>
