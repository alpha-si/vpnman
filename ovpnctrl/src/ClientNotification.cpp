/**
 * @file   ClientNotification.cpp
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Implementation of class ClientNotification.
 *
 * Utility class used to handle a Client notification
 * from OpenVPN management interface.
 */

#include "ClientNotification.h"
#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <iostream>

ClientNotification::ClientNotification()
{
	m_boCompleted = false;
	m_iCID = -1;
	m_iKID = -1;
	m_iType = CLIENT_NOTIFICATION_INVALID;
	//m_EnvMap
}


ClientNotification::~ClientNotification()
{
	// TODO Auto-generated destructor stub
}


void ClientNotification::parseConnect( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iCID = atoi(l_pcToken);
	}

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iKID = atoi(l_pcToken);
	}
}


void ClientNotification::parseReauth( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iCID = atoi(l_pcToken);
	}

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iKID = atoi(l_pcToken);
	}
}


void ClientNotification::parseEstablished( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iCID = atoi(l_pcToken);
	}
}


void ClientNotification::parseDisconnect( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iCID = atoi(l_pcToken);
	}
}


void ClientNotification::parseAddress( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_iCID = atoi(l_pcToken);
	}

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_Address = std::string(l_pcToken);
	}

	l_pcToken = strtok(NULL, ",");

	if (l_pcToken)
	{
		m_PRI = std::string(l_pcToken);
	}
}


void ClientNotification::parseEnv( void )
{
	char* l_pcToken;

	l_pcToken = strtok(NULL, "=");

	if (l_pcToken == NULL)
	{
		return;
	}

	if (strcmp(l_pcToken, "END") == 0)
	{
		m_boCompleted = true;
		return;
	}

	std::string l_Key = std::string(l_pcToken);

	l_pcToken = strtok(0, "=");

	if (l_pcToken == NULL)
	{
		return;
	}

	std::string l_Value = std::string(l_pcToken);


   
	m_EnvMap.insert(TStrStrPair(l_Key, l_Value));
}


bool ClientNotification::AddLine(char* p_acLine)
{
	char* l_pcToken;

	l_pcToken = strtok(p_acLine, ",");

	if (strcmp(l_pcToken, ">CLIENT:CONNECT") == 0)
	{
		m_iType = CLIENT_NOTIFICATION_CONNECT;
		m_EnvMap.clear();
		m_boCompleted = false;
		parseConnect();
	}
	else if (strcmp(l_pcToken, ">CLIENT:REAUTH") == 0)
	{
		m_iType = CLIENT_NOTIFICATION_REAUTH;
		m_EnvMap.clear();
		m_boCompleted = false;
		parseReauth();
	}
	else if (strcmp(l_pcToken, ">CLIENT:ESTABLISHED") == 0)
	{
		m_iType = CLIENT_NOTIFICATION_ESTABLISHED;
		m_EnvMap.clear();
		m_boCompleted = false;
		parseEstablished();
	}
	else if (strcmp(l_pcToken, ">CLIENT:DISCONNECT") == 0)
	{
		m_iType = CLIENT_NOTIFICATION_DISCONNECT;
		m_EnvMap.clear();
		m_boCompleted = false;
		parseDisconnect();
	}
	else if (strcmp(l_pcToken, ">CLIENT:ENV") == 0)
	{
		if ( (m_boCompleted == false) && (m_iType != CLIENT_NOTIFICATION_INVALID) )
		{
			parseEnv();
		}
	}
	else
	{
		//@@@ m_boCompleted = false;
	}

	return m_boCompleted;
}

bool ClientNotification::AddLines( LinesVector& p_Lines )
{
	bool l_boRes = false;
	char l_acBuffer[1024];
	int l_iLen;

	for (int i = 0; i < (int)p_Lines.size(); i++)
	{
		l_iLen = p_Lines[i].length() < sizeof(l_acBuffer) ? p_Lines[i].length() : (sizeof(l_acBuffer) - 1);
		strncpy( l_acBuffer, p_Lines[i].c_str(), l_iLen);
		l_acBuffer[p_Lines[i].length()] = 0;

		l_boRes = AddLine( l_acBuffer );
	}

	return l_boRes;
}

bool ClientNotification::GetEnv( const char* p_acEnvKey, char* p_acValue, int p_iMaxLen )
{
	TStrStrMap::iterator l_Iter = m_EnvMap.find(p_acEnvKey);

	if (l_Iter != m_EnvMap.end())
	{
		strncpy(p_acValue, l_Iter->second.c_str(), p_iMaxLen);
		return true;
	}

	return false;
}

std::string ClientNotification::GetEnv2( const char* p_acEnvKey )
{
	TStrStrMap::iterator l_Iter = m_EnvMap.find(p_acEnvKey);

	if (l_Iter != m_EnvMap.end())
	{
		//std::cout << p_acEnvKey << " = " << l_Iter->second << std::endl;
		return l_Iter->second;
	}

	return std::string("");
}

void ClientNotification::Response( char* p_acResp )
{
	sprintf(p_acResp, "client-auth-nt %d %d\r\n", m_iCID, m_iKID);
	std::cout << p_acResp << std::endl;
	m_EnvMap.clear();
	m_boCompleted = false;
}

