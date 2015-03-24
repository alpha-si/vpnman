//============================================================================
// Name        : ovpnctrl.cpp
// Author      : F.Sartini
// Version     : 1.0
// Copyright   : Alpha-SI srl
// Description : OpenVPN control server
//============================================================================

#include <arpa/inet.h>
#include <sys/types.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <signal.h>
#include "globals.h"
#include "OvpnResponseMng.h"

_INITIALIZE_EASYLOGGINGPP

using namespace std;

CfgMng 	g_Cfg;
DbDrv	   g_Db;
OvpnMng  g_Openvpn;
bool	   g_boTerminate = false;
int		g_iVpnId = 0;

// Define the function to be called when ctrl-c (SIGINT) signal is sent to process
void signal_callback_handler(int signum)
{
	// close socket connection
	g_Openvpn.Disconnect();

	// close database connection
	g_Db.Close();

	LOG(INFO) << "ovpnctrl server terminated!";

	// terminate program
	exit(signum);
}

int main(int argc, char* argv[])
{
	unsigned int l_uiCycle = 0;
	char l_acString[256];
   std::string l_CfgFile = "./ovpnctrl.conf";

   /* get configuration file (if provided) */
   if (argc > 1)
	{
      l_CfgFile = std::string(argv[1]);
	}

	std::string l_DbHost, l_DbUser, l_DbPass, l_DbName, l_VpnManHost, l_VpnManPort, l_LogFilename, l_VpnId, l_PidFile;
	int l_iPort;
	bool l_boRes = true;

	if (g_Cfg.LoadConfig(l_CfgFile) == false)
	{
		cerr << "unable to open configuration file " << l_CfgFile.c_str() << endl;
		return 1;
	}

	cout << "loaded configuration file "  << l_CfgFile.c_str() << endl;

	// get all needed parameters from configuration
	l_boRes |= g_Cfg.GetParam("db_host", l_DbHost);
	l_boRes |= g_Cfg.GetParam("db_user", l_DbUser);
	l_boRes |= g_Cfg.GetParam("db_pass", l_DbPass);
	l_boRes |= g_Cfg.GetParam("db_name", l_DbName);
	l_boRes |= g_Cfg.GetParam("vpn_man_host", l_VpnManHost);
	l_boRes |= g_Cfg.GetParam("vpn_man_port", l_VpnManPort);

	if (l_boRes == false)
	{
		cerr << "some configuration parameters are not available" << endl;
		return 1;
	}
   
   /* set vpn id */
	if (argc > 2)
	{
		g_iVpnId = atoi(argv[2]);
	}
   else if (g_Cfg.GetParam("vpn_id", l_VpnId))
   {
      g_iVpnId = atoi(l_VpnId.c_str());
   }
   
   /* log file */   
   if (!g_Cfg.GetParam("log_file", l_LogFilename))
   {
      sprintf(l_acString, "ovpnctrl_vpn%d.log", g_iVpnId);
      l_LogFilename = std::string(l_acString);
   }
   
   /* configure logger */
	el::Configurations defaultConf;
	defaultConf.setToDefault();
	defaultConf.set(el::Level::Global,
	                el::ConfigurationType::Filename,
	                l_LogFilename);
	el::Loggers::reconfigureLogger("default", defaultConf);

	LOG(INFO) << "starting ovpnctrl " << OVPNCTRL_VERSION << " for VPN id " << g_iVpnId;

	OvpnResponseMng l_Vpn;
	l_Vpn.SetOvpnIf(&g_Openvpn);

	/* register signal and signal handler */
	signal(SIGINT, signal_callback_handler);
   
   /* write pid file */
   if (g_Cfg.GetParam("pid_file", l_PidFile))
   {
      pid_t pid = getpid();
      
      FILE *fp = fopen(l_PidFile.c_str(), "w");
      
      if (!fp) 
      {
         LOG(INFO) << "unable to write pidfile " << l_PidFile;
         exit(EXIT_FAILURE);
      }
    
      fprintf(fp, "%d\n", pid);
    
      fclose(fp);
   }

   /* main loop */
	while (true)
	{
		/* connect to the database */
		if (g_Db.IsConnected() == false)
		{
			if (g_Db.Connect( l_DbHost.c_str(),
							  l_DbUser.c_str(),
							  l_DbPass.c_str(),
							  l_DbName.c_str() ) == false)
			{
				LOG(ERROR) << "connect to mysql failed";
				sleep(3);
				continue;
			}

			LOG(INFO) << "connected to db " << l_DbName.c_str() << " on host " << l_DbHost.c_str();
		}

		/* Get openvon management port */
		if (g_iVpnId > 0)
		{
			/* get from DB */
			sprintf(l_acString, "SELECT mng_port FROM vpn WHERE id = %d", g_iVpnId);

			if (g_Db.ExecuteScalar(l_acString, l_iPort) == false)
			{
				LOG(ERROR) << "Unable to get management port number from DB for VPN id " << g_iVpnId;
				return 1;
			}
		}
		else
		{
			/* get from configuration file */
			l_iPort = atoi(l_VpnManPort.c_str());
		}

		/* connect to openvpn management interface */
		if (g_Openvpn.IsConnected() == false)
		{
			if (g_Openvpn.Connect(l_VpnManHost.c_str(), (ushort)l_iPort) == false)
			{
				LOG(ERROR) << "connect to OpenVPN managenent interface failed";
				sleep(3);
				continue;
			}

			LOG(INFO) << "connected to openvpn on host " << l_VpnManHost << ":" << l_iPort;
		}

		// update start time
		sprintf( l_acString,
				 "UPDATE server_info SET value = DATE_FORMAT(NOW(), '%%Y-%%m-%%d %%T') WHERE vpn_id = '%d' AND attribute = 'start_time'",
				 g_iVpnId );

		if (g_Db.ExecuteUpdate(l_acString) == 0)
		{
			/* attribute doesn't exist, do insert */
			sprintf( l_acString,
					 "INSERT INTO server_info VALUES('start_time',DATE_FORMAT(NOW(), '%%Y-%%m-%%d %%T'),'%d')",
					 g_iVpnId );
			g_Db.ExecuteUpdate(l_acString);
		}

		// get server version
		l_Vpn.SendCommand(RESP_TYPE_CMD_VERSION);
		sleep(1);
		l_Vpn.HandleNextResponse();

		// get server pid
		l_Vpn.SendCommand(RESP_TYPE_CMD_PID);
      
      std::string l_StatusInterval;
      int l_iInterval = 0;
      
      // get command status interval from configuration file
      if (g_Cfg.GetParam("status_interval", l_StatusInterval))
      {
         l_iInterval = atoi(l_StatusInterval.c_str());
      }

		// handle msg from openvpn management interface and send periodic command
		while (g_Db.IsConnected() && g_Openvpn.IsConnected())
		{
			l_uiCycle++;

			l_Vpn.HandleNextResponse();

			if ((l_iInterval > 0) && ((l_uiCycle % l_iInterval) == 0))
			{
				l_Vpn.SendCommand(RESP_TYPE_CMD_STATUS);
			}

			if ((l_uiCycle % 5) == 0)
			{
				l_Vpn.SendCommand(RESP_TYPE_CMD_STATS);
            //g_Db.ExecuteUpdate("UPDATE vpn SET updated = NOW()");
			}

			// nothing to do...
			sleep(1);
		}

		if (g_Db.IsConnected() == false)
		{
			LOG(WARNING) << "lost db connection...";
		}

		if (g_Openvpn.IsConnected() == false)
		{
			LOG(WARNING) << "lost openvpn connection...";
		}

		sleep(3);
	}

	return 0;
}


