/**
 * @file   ClientNotification.h
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Declaration of class ClientNotification.
 *
 * Utility class used to handle a Client notification
 * from OpenVPN management interface.
 */

#ifndef CLIENTNOTIFICATION_H_
#define CLIENTNOTIFICATION_H_

#include <map>
#include <string>
#include "OvpnMng.h"

#define CLIENT_NOTIFICATION_INVALID 	0
#define CLIENT_NOTIFICATION_CONNECT 	1
#define CLIENT_NOTIFICATION_REAUTH 		2
#define CLIENT_NOTIFICATION_ESTABLISHED	3
#define CLIENT_NOTIFICATION_DISCONNECT 	4
#define CLIENT_NOTIFICATION_ADDRESS 	5

typedef std::map<std::string, std::string> TStrStrMap;
typedef std::pair<std::string, std::string> TStrStrPair;

class ClientNotification
{
protected:
	bool m_boCompleted;
	int m_iCID;
	int m_iKID;
	int m_iType;
	std::string m_Address;
	std::string m_PRI;
	TStrStrMap m_EnvMap;

	void parseConnect( void );
	void parseReauth( void );
	void parseEstablished( void );
	void parseDisconnect( void );
	void parseAddress( void );
	void parseEnv( void );

public:
	ClientNotification();
	virtual ~ClientNotification();
	bool AddLine(char* p_acLine);
	bool AddLines( LinesVector& p_Lines );
	inline bool Completed( void ) { return m_boCompleted; }
	inline int GetType( void ) { return m_iType; }
	inline int GetCID( void ) { return m_iCID; }
	inline int GetKID( void ) { return m_iKID; }
	void Response( char* p_acResp );
	bool GetEnv( const char* p_acEnvKey, char* p_acValue, int p_iMaxLen );
	std::string GetEnv2( const char* p_acEnvKey );
};

#endif /* CLIENTNOTIFICATION_H_ */
