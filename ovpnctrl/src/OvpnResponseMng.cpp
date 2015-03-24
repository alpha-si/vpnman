/*
 * OvpnResponseMng.cpp
 *
 *  Created on: 23/mar/2014
 *  Author: alpha
 */

#include "globals.h"
#include "OvpnResponseMng.h"


OvpnResponseMng::OvpnResponseMng()
{
	m_pOvpnIf = 0;
	m_iCmdInProgress = NO_COMMAND;
}

OvpnResponseMng::~OvpnResponseMng()
{
	// TODO Auto-generated destructor stub
}

int OvpnResponseMng::findUserId( const char* p_acUsername )
{
	char l_acQuery[128];
	int l_iUserId;

	sprintf( l_acQuery, "SELECT id FROM accounts WHERE username = '%s'", p_acUsername );

	if (g_Db.ExecuteScalar(l_acQuery, l_iUserId) == false)
	{
		l_iUserId = -1;
	}

	return l_iUserId;
}


void OvpnResponseMng::handleClientConnect( ClientNotification& l_Client )
{
	char l_acString[1024];
	char l_acUsername[32];
	char l_acPassword[32];
	bool l_boUser, l_boPass;
	int l_iRes;
	MYSQL_RES* l_pResSet;
	MYSQL_ROW l_pResRow;
	std::string l_Resp;

   l_acUsername[0] = 0;
   l_acPassword[0] = 0;
   
	l_boUser = l_Client.GetEnv("username", l_acUsername, sizeof(l_acUsername));
	l_boPass = l_Client.GetEnv("password", l_acPassword, sizeof(l_acPassword));
   
   if ((l_boUser == false) || (strlen(l_acUsername) <= 0))
   {
      l_boUser = l_Client.GetEnv("common_name", l_acUsername, sizeof(l_acUsername));
      
      if (l_boUser == true)
      {
         //strncpy(l_acPassword, l_acUsername, sizeof(l_acPassword));
         l_boPass = true;
      }
      else
      {
         l_boPass = false;
      }
   }

   if (l_Client.GetType() != CLIENT_NOTIFICATION_REAUTH)
   {
      LOG(INFO) << "Notification client connect (" << l_acUsername << ")";
   }
   else
   {
      LOG(INFO) << "Notification client reauth (" << l_acUsername << ")";
   }
   
	if (l_boUser && l_boPass)
	{
		sprintf( l_acString,
				 "client-auth %d %d\r\n",
				 l_Client.GetCID(),
				 l_Client.GetKID() );

		l_Resp = std::string(l_acString);

		sprintf(l_acString,
				"SELECT COUNT(*) FROM accounts WHERE vpn_id = '%d' AND enabled = true AND username = '%s' AND (passwd = '%s' OR auth_type = 'CERT_ONLY')",
				g_iVpnId,
				l_acUsername,
				l_acPassword );

		if ( (g_Db.ExecuteScalar(l_acString, l_iRes) == true) && (l_iRes >= 1) )
		{
			// find iroute networks
			sprintf(l_acString,
					"SELECT network,netmask,mapped_to FROM networks AS n, accounts AS u WHERE n.user_id = u.id AND n.vpn_id = '%d' AND u.username = '%s'",
					g_iVpnId,
					l_acUsername);

			l_pResSet = g_Db.ExecuteQuery(l_acString);

			while ( (l_pResSet != NULL) && (l_pResRow = mysql_fetch_row(l_pResSet)) )
			{
				sprintf( l_acString,
						 "iroute %s %s\r\n",
						 l_pResRow[0],
						 l_pResRow[1] );

				l_Resp += l_acString;

				// check if subnet mapping is required
				if ( (l_pResRow[2] != 0) && (strlen(l_pResRow[2]) > 0) )
				{
					sprintf( l_acString,
							 "push \"echo vpnman-subnet-map %s %s %s\"\r\n",
							 l_pResRow[0],
							 l_pResRow[1],
							 l_pResRow[2] );

					l_Resp += l_acString;
				}
			}

			g_Db.FreeResult(l_pResSet);

			// find push networks
			sprintf( l_acString,
					"SELECT network,netmask FROM networks AS n, accounts AS a WHERE n.vpn_id = '%d' AND n.user_id = a.id AND a.status = 'ESTABLISHED'",
					g_iVpnId );

			l_pResSet = g_Db.ExecuteQuery(l_acString);

			while ( (l_pResSet != NULL) && (l_pResRow = mysql_fetch_row(l_pResSet)) )
			{
				sprintf( l_acString,
						 "push \"route  %s %s\"\r\n",
						 l_pResRow[0],
						 l_pResRow[1] );

				l_Resp += l_acString;
			}

			g_Db.FreeResult(l_pResSet);

			l_Resp += "END\r\n";

			if (l_Client.GetType() != CLIENT_NOTIFICATION_REAUTH)
			{
				sprintf(l_acString,
					"UPDATE accounts SET status = 'CONNECTING' WHERE username = '%s'",
					l_acUsername );

				// update accounts table
				g_Db.ExecuteUpdate(l_acString);
			}
		}
		else
		{
			sprintf( l_acString,
					 "client-deny %d %d \"%s (%s,%s)\"\r\n",
					 l_Client.GetCID(),
					 l_Client.GetKID(),
					 "Login Failed",
                l_acUsername,
                l_acPassword );

			l_Resp = std::string(l_acString);
		}
	}
	else
	{
		sprintf( l_acString,
				 "client-deny %d %d \"%s\"\r\n",
				 l_Client.GetCID(),
				 l_Client.GetKID(),
				 "Unexpected connect request" );

		l_Resp = std::string(l_acString);
	}

	//std::cout << "[INFO] " << l_Resp.c_str() << std::endl;
	m_pOvpnIf->SendCmd(l_Resp);

	LOG(INFO) << "Notification CLIENT: " << l_Resp;
}


void OvpnResponseMng::handleClientEstablished( ClientNotification& l_Client )
{
	char l_acString[1024];
	char l_acUsername[32];
	bool l_boUser;
	std::string l_Resp;
	int l_iUserId;
   MYSQL_RES* l_pResSet;
	MYSQL_ROW l_pResRow;

   l_acUsername[0] = 0;
   
	l_boUser = l_Client.GetEnv("username", l_acUsername, sizeof(l_acUsername));
   
   if ((l_boUser == false) || (strlen(l_acUsername) == 0))
   {
      l_boUser = l_Client.GetEnv("common_name", l_acUsername, sizeof(l_acUsername));
   }
   
   LOG(INFO) << "Notification client established (" << l_acUsername << ")";

	if (l_boUser)
	{
		l_iUserId = findUserId(l_acUsername);

		sprintf(l_acString,
				"UPDATE accounts SET status = 'ESTABLISHED' WHERE username = '%s'",
				l_acUsername );

		// update accounts table
		g_Db.ExecuteUpdate(l_acString);
      
      // checks if the user is present inside connection_history with a still opened session 
		sprintf(l_acString,
				"SELECT id FROM connection_history WHERE user_id = '%d' AND vpn_id = '%d' AND end_time IS NULL",
	    	    l_iUserId,
	    	    g_iVpnId);

		l_pResSet = g_Db.ExecuteQuery(l_acString);
		int l_iNumRows = mysql_num_rows(l_pResSet);
      
      g_Db.FreeResult(l_pResSet);
		
		if (l_iNumRows > 0)
		{
			// try to update connection history
         sprintf( l_acString,
	    	     "UPDATE connection_history SET trusted_ip = '%s', trusted_port = '%s', ifconfig_pool_remote_ip = '%s', cid = '%d', kid = '%d' WHERE user_id = '%d' AND vpn_id = '%d' AND end_time IS NULL",
	    	     l_Client.GetEnv2("trusted_ip").c_str(),
	    	     l_Client.GetEnv2("trusted_port").c_str(),
	    	     l_Client.GetEnv2("ifconfig_pool_remote_ip").c_str(),
	    	     l_Client.GetCID(),
				  l_Client.GetKID(),
	    	     l_iUserId,
	    	     g_iVpnId );
              
          LOG(INFO) << "handleClientEstablished: update connection for (" << l_acUsername << ")";
		}
		else
		{
         // insert new record in connection history
			sprintf(l_acString,
				"INSERT INTO connection_history(user_id,start_time,trusted_ip,trusted_port,ifconfig_pool_remote_ip,cid,kid,vpn_id) VALUES('%d',NOW(),'%s','%s','%s','%d','%d','%d')",
				l_iUserId,
				l_Client.GetEnv2("trusted_ip").c_str(),
				l_Client.GetEnv2("trusted_port").c_str(),
				l_Client.GetEnv2("ifconfig_pool_remote_ip").c_str(),
				l_Client.GetCID(),
				l_Client.GetKID(),
				g_iVpnId);

         LOG(INFO) << "handleClientEstablished: new connection for (" << l_acUsername << ")";
		}

		// update connection_history table
		g_Db.ExecuteUpdate(l_acString);
	}
}

void OvpnResponseMng::handleClientDisconnect( ClientNotification& l_Client )
{
	char l_acString[1024];
	char l_acUsername[32];
	bool l_boUser;
	int l_iUserId;

   l_acUsername[0] = 0;
   
	l_boUser = l_Client.GetEnv("username", l_acUsername, sizeof(l_acUsername));
   
   if ((l_boUser == false) || strlen(l_acUsername) == 0)
   {
      l_boUser = l_Client.GetEnv("common_name", l_acUsername, sizeof(l_acUsername));
   }
   
   LOG(INFO) << "Notification client disconnect (" << l_acUsername << ")";

	if (l_boUser)
	{
		l_iUserId = findUserId(l_acUsername);

		sprintf(l_acString,
				"UPDATE accounts SET status = 'DISCONNECTED' WHERE username = '%s'",
				l_acUsername );

		// update accounts table
		g_Db.ExecuteUpdate(l_acString);

		int l_iBytesTx = atoi(l_Client.GetEnv2("bytes_sent").c_str());
		int l_iBytesRx = atoi(l_Client.GetEnv2("bytes_received").c_str());

		sprintf(l_acString,
				"UPDATE connection_history SET end_time = NOW(), bytes_received = %d, bytes_sent = %d, cid = %d, kid = %d WHERE user_id = '%d' AND vpn_id = '%d' AND end_time IS NULL",
				l_iBytesRx,
				l_iBytesTx,
				l_Client.GetCID(),
				l_Client.GetKID(),
				l_iUserId,
				g_iVpnId);

		// update connection_history table
		g_Db.ExecuteUpdate(l_acString);
	}
}

void OvpnResponseMng::handleClientNotification( LinesVector& p_Resp )
{
	ClientNotification l_Client;

	if (l_Client.AddLines(p_Resp) == true)
	{
		switch (l_Client.GetType())
		{
		case CLIENT_NOTIFICATION_REAUTH:
		case CLIENT_NOTIFICATION_CONNECT:
			handleClientConnect(l_Client);
			break;
		case CLIENT_NOTIFICATION_ESTABLISHED:
			handleClientEstablished(l_Client);
			break;
		case CLIENT_NOTIFICATION_DISCONNECT:
			handleClientDisconnect(l_Client);
			break;
		case CLIENT_NOTIFICATION_ADDRESS:
			break;
		default:
			break;
		}
	}
}

void OvpnResponseMng::handleBytecountNotification( LinesVector& p_Resp )
{
	char l_acBuf[1024];
	char* l_pcToken;
	int l_iCID;
	uint64_t l_ullBytesTx;
	uint64_t l_ullBytesRx;
	std::string l_Query;

	for (int i = 0; i < (int)p_Resp.size(); i++)
	{
		strcpy(l_acBuf, p_Resp[i].c_str());

		l_pcToken = strtok(l_acBuf, ";"); // notification name

		if (l_pcToken == 0)
		{
			// unexpected string format
			continue;
		}

		l_pcToken = strtok(0, ","); // CID

		if (l_pcToken == 0)
		{
			// unexpected string format
			continue;
		}

		l_iCID = atoi(l_pcToken);

		l_pcToken = strtok(0, ","); // bytes rx

		if (l_pcToken == 0)
		{
			// unexpected string format
			continue;
		}

		l_ullBytesRx = atol(l_pcToken);

		l_pcToken = strtok(0, ","); // bytes tx

		if (l_pcToken == 0)
		{
			// unexpected string format
			continue;
		}

		l_ullBytesTx = atol(l_pcToken);


		sprintf( l_acBuf,
				 "UPDATE connection_history SET bytes_received = '%llu', bytes_sent = '%llu' WHERE cid = '%d' AND vpn_id = '%d' AND end_time IS NULL",
				 l_ullBytesRx,
				 l_ullBytesTx,
				 l_iCID,
				 g_iVpnId );

		g_Db.ExecuteUpdate(l_acBuf);
	}
}

void OvpnResponseMng::handleCmdStats( LinesVector& p_Resp )
{
	int l_iRes;
	int l_iClients;
	uint64_t l_ullRx;
	uint64_t l_ullTx;
	char l_acQuery[256];

	if (p_Resp.size() > 0)
	{
		l_iRes = sscanf( p_Resp[0].c_str(),
						 "SUCCESS: nclients=%d,bytesin=%llu,bytesout=%llu",
						 &l_iClients,
						 &l_ullRx,
						 &l_ullTx );

		if (l_iRes == 3)
		{
			sprintf(l_acQuery, "UPDATE server_info SET value = '%d' WHERE attribute = 'nclients' AND vpn_id = '%d'", l_iClients, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('nclients','%d','%d')", l_iClients, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}

			sprintf(l_acQuery, "UPDATE server_info SET value = '%llu' WHERE attribute = 'bytesin' AND vpn_id = '%d'", l_ullRx, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('bytesin','%llu','%d')", l_ullRx, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}

			sprintf(l_acQuery, "UPDATE server_info SET value = '%llu' WHERE attribute = 'bytesout' AND vpn_id = '%d'", l_ullTx, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('bytesout','%llu','%d')", l_ullTx, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}
         
         sprintf( l_acQuery, "UPDATE server_info SET value = DATE_FORMAT(NOW(), '%%Y-%%m-%%d %%T') WHERE vpn_id = '%d' AND attribute = 'keepalive'", g_iVpnId );

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf( l_acQuery,
                        "INSERT INTO server_info VALUES('keepalive',DATE_FORMAT(NOW(), '%%Y-%%m-%%d %%T'),'%d')",
                        g_iVpnId );
				g_Db.ExecuteUpdate(l_acQuery);
			}
		}
		else
		{
			LOG(ERROR) << "Command LOAD-STATS unexpected response: " << p_Resp[0];
		}
	}
}

void OvpnResponseMng::handleCmdPid( LinesVector& p_Resp )
{
	int l_iRes;
	int l_iPid;
	char l_acQuery[256];

	if (p_Resp.size() > 0)
	{
		l_iRes = sscanf( p_Resp[0].c_str(), "SUCCESS: pid=%d", &l_iPid );

		if (l_iRes == 1)
		{
			sprintf(l_acQuery, "UPDATE server_info SET value = '%d' WHERE attribute = 'pid' AND vpn_id = '%d'", l_iPid, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('pid','%d','%d')", l_iPid, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}
		}
		else
		{
			LOG(ERROR) << "Command PID unexpected response: " << p_Resp[0];
		}
	}
}

void OvpnResponseMng::handleCmdVersion( LinesVector& p_Resp )
{
	int l_iRes;
	char l_acBuf[1024];
	char l_acQuery[1024];

	if (p_Resp.size() > 0)
	{
		l_iRes = sscanf( p_Resp[0].c_str(), "OpenVPN Version: %*s %s %*s", l_acBuf );

		if (l_iRes == 1)
		{
			sprintf(l_acQuery, "UPDATE server_info SET value = '%s' WHERE attribute = 'server_ver' AND vpn_id = '%d'", l_acBuf, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('server_ver','%s','%d')", l_acBuf, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}
		}

		l_iRes = sscanf( p_Resp[1].c_str(), "Management Version: %s", l_acBuf );

		if (l_iRes == 1)
		{
			sprintf(l_acQuery, "UPDATE server_info SET value = '%s' WHERE attribute = 'management_ver' AND vpn_id = '%d'", l_acBuf, g_iVpnId);

			if (g_Db.ExecuteUpdate(l_acQuery) == 0)
			{
				sprintf(l_acQuery, "INSERT INTO server_info VALUES('management_ver','%s','%d')", l_acBuf, g_iVpnId);
				g_Db.ExecuteUpdate(l_acQuery);
			}
		}
	}
}

std::string OvpnResponseMng::toMysqlDate( std::string& p_OvpnDate )
{
	std::string l_MysqlDate = "";
	std::string l_Month = p_OvpnDate.substr(4,3);

	if (l_Month == "Gen")
	{
		l_Month = "-01-";
	}
	else if (l_Month == "Feb")
	{
		l_Month = "-02-";
	}
	else if (l_Month == "Mar")
	{
		l_Month = "-03-";
	}
	else if (l_Month == "Apr")
	{
		l_Month = "-04-";
	}
	else if (l_Month == "May")
	{
		l_Month = "-05-";
	}
	else if (l_Month == "Jun")
	{
		l_Month = "-06-";
	}
	else if (l_Month == "Jul")
	{
		l_Month = "-07-";
	}
	else if (l_Month == "Ago")
	{
		l_Month = "-08-";
	}
	else if (l_Month == "Sep")
	{
		l_Month = "-09-";
	}
	else if (l_Month == "Oct")
	{
		l_Month = "-10-";
	}
	else if (l_Month == "Nov")
	{
		l_Month = "11";
	}
	else if (l_Month == "Dic")
	{
		l_Month = "-12-";
	}

	l_MysqlDate += p_OvpnDate.substr(20,4);
	l_MysqlDate += l_Month;
	l_MysqlDate += p_OvpnDate.substr(8,2);
	l_MysqlDate += p_OvpnDate.substr(10,9);

	if (l_MysqlDate[8] == ' ')
	{
		l_MysqlDate[8] = '0';
	}

	return l_MysqlDate;
}



void OvpnResponseMng::handleCmdStatus( LinesVector& p_Resp )
{
	int l_iLineIdx;
	char l_acQuery[1024];
	char l_acUserId[1024];
	char l_acConnectionId[1024];
	std::string l_Query = "";
	bool l_bo_user_present = false;
	MYSQL_RES* l_pResSet;
	MYSQL_ROW l_pResRow;

   g_Db.ExecuteUpdate("LOCK TABLES accounts WRITE");
   
	sprintf( l_acQuery,
			"UPDATE accounts SET status = 'DISCONNECTED' WHERE vpn_id = '%d'",
			g_iVpnId );
	g_Db.ExecuteUpdate(l_acQuery);

	for (l_iLineIdx = 3; l_iLineIdx < (int)p_Resp.size(); l_iLineIdx++)
	{
		if (strncmp(p_Resp[l_iLineIdx].c_str(), "ROUTING TABLE", strlen("ROUTING TABLE")) == 0)
		{
			break;
		}

		std::vector<std::string> l_Values;
		std::istringstream l_StrStream(p_Resp[l_iLineIdx]);
	   std::string l_Token;

	   while (std::getline(l_StrStream, l_Token, ','))
	   {
	    	l_Values.push_back(l_Token);
		}

	   if (l_Values.size() != 5)
	   {
	    	LOG(ERROR) << "handleCmdStatus: unexpected format for status command (" << p_Resp[l_iLineIdx] << ")";
	    	break;
	   }

	   int l_iUserId = findUserId( l_Values[0].c_str() );
      
      if (l_iUserId < 0)
      {
         LOG(ERROR) << "handleCmdStatus: [" << p_Resp[l_iLineIdx] << "]";
         continue;
      }

		sprintf(l_acQuery,
				"UPDATE accounts SET status = 'ESTABLISHED' WHERE username = '%s'",
				l_Values[0].c_str() );

		// update accounts table
		g_Db.ExecuteUpdate(l_acQuery);

	   size_t l_uiPos = l_Values[1].find_first_of(':');
	   std::string l_TrustedAddr = l_Values[1].substr(0, l_uiPos);
	   std::string l_TrustedPort = l_Values[1].substr(l_uiPos+1);
	   std::string l_StartTime = toMysqlDate(l_Values[4]);
		
		// checks if the user is present inside connection_history with a still opened session 
		sprintf(l_acQuery,
				"SELECT id FROM connection_history WHERE user_id = '%d' AND vpn_id = '%d' AND end_time IS NULL",
	    	    l_iUserId,
	    	    g_iVpnId);

		l_pResSet = g_Db.ExecuteQuery(l_acQuery);
		int l_i_num_rows = mysql_num_rows(l_pResSet);
		
		if (l_i_num_rows > 0)
		{
			l_bo_user_present = true;
		}
		else
		{
			l_bo_user_present = false;
		}

	   // try to update connection history
	   sprintf( l_acQuery,
	    	     "UPDATE connection_history SET bytes_received = '%s', bytes_sent = '%s', trusted_ip = '%s', trusted_port = '%s' WHERE user_id = '%d' AND vpn_id = '%d' AND end_time IS NULL",
	    	     l_Values[2].c_str(),
	    	     l_Values[3].c_str(),
	    	     l_TrustedAddr.c_str(),
	    	     l_TrustedPort.c_str(),
	    	     l_iUserId,
	    	     g_iVpnId );				 
		
		//LOG(INFO) << "Query: " << l_acQuery;

	    // if update fails and the user is not present in the connection_history table, insert a new connection history record
	    if (g_Db.ExecuteUpdate(l_acQuery) == 0 &&
		    l_bo_user_present == false )
	    {
		    sprintf( l_acQuery,
		    	     "INSERT INTO connection_history(user_id,start_time,end_time,bytes_received,bytes_sent,trusted_ip,trusted_port,vpn_id) VALUES('%d',NOW(),NULL,'%s','%s','%s','%s','%d')",
		    	     l_iUserId,
		    	     l_Values[2].c_str(),
		    	     l_Values[3].c_str(),
		    	     l_TrustedAddr.c_str(),
		    	     l_TrustedPort.c_str(),
		    	     g_iVpnId );

		    g_Db.ExecuteUpdate(l_acQuery);
          
          LOG(INFO) << "handleCmdStatus: update connected user (" << l_Values[0].c_str() << ")";
	    }

		g_Db.FreeResult(l_pResSet);
	}	
		
	// selects all accounts which have the connection in DISCONNECTED state
	sprintf(l_acQuery,
			"SELECT accounts.id, connection_history.id FROM accounts, connection_history WHERE accounts.id = connection_history.user_id AND accounts.status = 'DISCONNECTED' AND connection_history.end_time IS NULL");

	l_pResSet = g_Db.ExecuteQuery(l_acQuery);

	// Updates all the other tables to handle correctly the disconnection
	while ( (l_pResSet != NULL) && (l_pResRow = mysql_fetch_row(l_pResSet)) )
	{
		sprintf( l_acUserId,
				 "%s",
				 l_pResRow[0]);
				 
		sprintf( l_acConnectionId,
				 "%s",
				 l_pResRow[1]);				 

		sprintf(l_acQuery,
				"UPDATE accounts SET status = 'DISCONNECTED' WHERE id = '%s'",
				l_acUserId );

		// update accounts table
		g_Db.ExecuteUpdate(l_acQuery);

		sprintf(l_acQuery,
				"UPDATE connection_history SET end_time = NOW() WHERE user_id = '%s' AND id = '%s'",
				l_acUserId,
				l_acConnectionId);

		// update connection_history table
		g_Db.ExecuteUpdate(l_acQuery);
      
      LOG(INFO) << "handleCmdStatus: update disconnected user (id " << l_acUserId << ")";
	}

	g_Db.FreeResult(l_pResSet);
   
   g_Db.ExecuteUpdate("UNLOCK TABLES");
}

int OvpnResponseMng::findRespType( LinesVector& p_Resp )
{
	int l_iType = RESP_TYPE_UNKNOWN;

	if (p_Resp.size() > 0)
	{
		if (strncmp(p_Resp[0].c_str(), ">CLIENT:", strlen(">CLIENT:")) == 0)
		{
			// client notification
			l_iType = RESP_TYPE_NOTIF_CLIENT;
		}
		else if (strncmp(p_Resp[0].c_str(), ">BYTECOUNT_CLI:", strlen(">BYTECOUNT_CLI:")) == 0)
		{
			// client notification
			l_iType = RESP_TYPE_NOTIF_BYTECOUNT;
		}
		else if ((p_Resp[0].at(0) != '>') && (m_iCmdInProgress != NO_COMMAND))
		{
			// command response
			l_iType = m_iCmdInProgress;
		}
	}

	return l_iType;
}

void OvpnResponseMng::HandleNextResponse( void )
{
	LinesVector l_Resp;

	if (m_pOvpnIf->GetNextResponse(l_Resp) == true)
	{
		switch (findRespType(l_Resp))
		{
		case RESP_TYPE_NOTIF_CLIENT:
			handleClientNotification(l_Resp);
			break;
		case RESP_TYPE_NOTIF_BYTECOUNT:
			handleBytecountNotification(l_Resp);
			break;
		case RESP_TYPE_CMD_STATUS:
			handleCmdStatus(l_Resp);
			m_iCmdInProgress = NO_COMMAND;
			break;
		case RESP_TYPE_CMD_STATS:
			handleCmdStats(l_Resp);
			m_iCmdInProgress = NO_COMMAND;
			break;
		case RESP_TYPE_CMD_PID:
			handleCmdPid(l_Resp);
			m_iCmdInProgress = NO_COMMAND;
			break;
		case RESP_TYPE_CMD_VERSION:
			handleCmdVersion(l_Resp);
			m_iCmdInProgress = NO_COMMAND;
			break;
		case RESP_TYPE_CMD_STATE:
		case RESP_TYPE_UNKNOWN:
		default:
			break;
		}
	}
}

bool OvpnResponseMng::SendCommand( int p_iCmdType )
{
	bool l_boRes = false;

	if (m_iCmdInProgress == NO_COMMAND)
	{
		switch (p_iCmdType)
		{
			case RESP_TYPE_CMD_STATUS:
				m_pOvpnIf->SendCmd(std::string("status"));
				l_boRes = true;
				break;
			case RESP_TYPE_CMD_STATS:
				m_pOvpnIf->SendCmd(std::string("load-stats"));
				l_boRes = true;
				break;
			case RESP_TYPE_CMD_PID:
				m_pOvpnIf->SendCmd(std::string("pid"));
				l_boRes = true;
				break;
			case RESP_TYPE_CMD_VERSION:
				m_pOvpnIf->SendCmd(std::string("version"));
				l_boRes = true;
				break;
			default:
				break;
		}

		if (l_boRes == true)
		{
			m_iCmdInProgress = p_iCmdType;
		}
	}

	return l_boRes;
}

