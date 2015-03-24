/*
 * OvpnResponseMng.h
 *
 *  Created on: 23/mar/2014
 *      Author: alpha
 */

#ifndef OVPNRESPONSEMNG_H_
#define OVPNRESPONSEMNG_H_

//#include "globals.h"
#include "OvpnMng.h"
#include "ClientNotification.h"

//#include <string>

#define NO_COMMAND					0xffff
#define RESP_TYPE_UNKNOWN			0
#define RESP_TYPE_CMD_STATE			1
#define RESP_TYPE_CMD_STATUS		2
#define RESP_TYPE_CMD_VERSION		3
#define RESP_TYPE_CMD_STATS			4
#define RESP_TYPE_CMD_PID			5
#define RESP_TYPE_NOTIF_CLIENT		6
#define RESP_TYPE_NOTIF_BYTECOUNT	7


class OvpnResponseMng
{
protected:
	OvpnMng* m_pOvpnIf;
	int m_iCmdInProgress;

	int findUserId( const char* p_acUsername );
	std::string toMysqlDate( std::string& p_OvpnDate );

	// Notification handlers
	void handleClientNotification( LinesVector& p_Resp );
	void handleBytecountNotification( LinesVector& p_Resp );
	void handleClientConnect( ClientNotification& l_Client );
	void handleClientEstablished( ClientNotification& l_Client );
	void handleClientDisconnect( ClientNotification& l_Client );

	// Command response handlers
	void handleCmdStatus( LinesVector& p_Resp );
	void handleCmdStats( LinesVector& p_Resp );
	void handleCmdPid( LinesVector& p_Resp );
	void handleCmdVersion( LinesVector& p_Resp );

	int findRespType( LinesVector& p_Resp );

public:
	OvpnResponseMng();
	virtual ~OvpnResponseMng();
	inline void SetOvpnIf( OvpnMng* p_pOvpnIf ) { m_pOvpnIf = p_pOvpnIf; }
	bool SendCommand( int p_iCmdType );
	void HandleNextResponse( void );
};

#endif /* OVPNRESPONSEMNG_H_ */
