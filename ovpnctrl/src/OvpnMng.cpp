/**
 * @file   OvpnMng.h
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Declaration of class OvpnMng.
 *
 * This class handles the OpenVPN management interface
 */

#include <iostream>
#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include "globals.h"
#include "OvpnMng.h"

OvpnMng::OvpnMng()
{
	m_iSocket = -1;
	m_boConnected = false;
	l_iCmdIdx = 0;
	m_boRunning = false;
	m_boTerminateRequest = false;

	if ( (pthread_mutex_init(&m_SocketMutex, NULL) != 0) ||
		 (pthread_mutex_init(&m_QueueMutex, NULL) != 0) )
	{
		LOG(ERROR) << "OvpnMng::OvpnMng(): pthread_mutex_init failed!";
	}
}


OvpnMng::~OvpnMng()
{
	Disconnect();
	pthread_mutex_destroy(&m_SocketMutex);
	pthread_mutex_destroy(&m_QueueMutex);
}

void OvpnMng::Disconnect( void )
{
	// stop receiving thread
	recvThreadStop();

	// close management interface
	close(m_iSocket);

	// openvpn interface disconnected
	m_boConnected = false;
}

bool OvpnMng::Connect( const char* p_acAddress, ushort p_usPort )
{
	int l_iRes;
	struct sockaddr_in l_RemoteAddr;

	if (m_boConnected == false)
	{
		/* connect to the OpenVPN management interface */
		m_iSocket = socket(AF_INET, SOCK_STREAM, 0);

		if (m_iSocket < 0)
		{
			std::cout << "[ERROR] opening socket" << std::endl;
			return false;
		}

		memset(&l_RemoteAddr, 0, sizeof(struct sockaddr_in));

		/* Clear structure */
		l_RemoteAddr.sin_family = AF_INET;
		//l_RemoteAddr.sin_addr = htonl(0x7f000001);
		l_RemoteAddr.sin_port = htons(p_usPort);
		inet_aton(p_acAddress, &l_RemoteAddr.sin_addr);

		l_iRes = connect( m_iSocket,
						  (struct sockaddr *) &l_RemoteAddr,
						  sizeof(struct sockaddr_in) );

		if (l_iRes != 0)
		{
			std::cout << "[ERROR] connect to OpenVPN management interface failed" << std::endl;
			return false;
		}

		if (recvThreadStart() == false)
		{
			std::cout << "[ERROR] start recv thread failed" << std::endl;
			return false;
		}

		m_boConnected = true;
	}

	return m_boConnected;
}

bool OvpnMng::isOneLineNotification( const char* p_acLine )
{
	//@@@TODO: add all one-line notification
	if ( (strncmp(p_acLine, ">BYTECOUNT_CLI:", strlen(">BYTECOUNT_CLI:")) == 0) ||
		 (strncmp(p_acLine, ">INFO:", strlen(">INFO:")) == 0) )
	{
		return true;
	}

	return false;
}

bool OvpnMng::isEndOfNotification( const char* p_acLine )
{
	if (strstr(p_acLine, ",END") != 0)
	{
		return true;
	}

	return false;
}

void OvpnMng::handleNewLine( char* p_acLine )
{
	bool l_boEndOfResponse = false;
	bool l_boOneLineResponse = false;

	if ( (strncmp(p_acLine, "SUCCESS:", strlen("SUCCESS:")) == 0) ||
		 (strncmp(p_acLine, "ERROR:", strlen("ERROR:")) == 0) ||
		 ((p_acLine[0] == '>') && (isOneLineNotification(p_acLine))) )

	{
		// one-line command/notification response received
		l_boEndOfResponse = true;
		l_boOneLineResponse = true;
	}
	else if ( (strncmp(p_acLine, "END", strlen("END")) == 0) ||
			  ((p_acLine[0] == '>') && (isEndOfNotification(p_acLine))) )
	{
		// end of multiline command/notification response received
		l_boEndOfResponse = true;
	}

	if (l_boEndOfResponse == true)
	{
		if (m_boRespInProgress == true)
		{
			// if there is an in progress multiline response, enqueue it
			if (l_boOneLineResponse == false)
			{
				m_CurrentResp.push_back(std::string(p_acLine));
			}
			pthread_mutex_lock(&m_QueueMutex);
			m_RespQueue.push(m_CurrentResp);
			pthread_mutex_unlock(&m_QueueMutex);
			m_CurrentResp.clear();

			m_boRespInProgress = false;
		}

		if (l_boOneLineResponse == true)
		{
			// enqueue the one-line command response
			m_CurrentResp.push_back(std::string(p_acLine));
			pthread_mutex_lock(&m_QueueMutex);
			m_RespQueue.push(m_CurrentResp);
			pthread_mutex_unlock(&m_QueueMutex);
			m_CurrentResp.clear();
		}
	}
	else
	{
		// enqueue the line in current response
		m_CurrentResp.push_back(std::string(p_acLine));
		m_boRespInProgress = true;
	}
}

void OvpnMng::SendCmd( std::string p_Cmd )
{
	// get mutex
	pthread_mutex_lock(&m_SocketMutex);

	p_Cmd += "\r\n";
	write(m_iSocket, p_Cmd.c_str(), p_Cmd.length());

	// release mutex
	pthread_mutex_unlock(&m_SocketMutex);
}


void OvpnMng::ReceiveLoop( void )
{
	// management interface receive loop
	while (m_boTerminateRequest == false)
	{
		// get mutex
		//pthread_mutex_lock(&m_SocketMutex);

		// read from socket
		int l_iLen = read( m_iSocket, l_acBuffer, sizeof(l_acBuffer) );

		// get mutex
		//pthread_mutex_unlock(&m_SocketMutex);

		// copy chars to command string until newline is found
		for (int i = 0; i < l_iLen; i++)
		{
			if (l_acBuffer[i] == '\n')
			{
				// end of line, null terminate the string and pass it to the line parser
				l_acCommand[l_iCmdIdx] = 0;
				l_iCmdIdx = 0;
				handleNewLine(l_acCommand);
			}
			else if (l_acBuffer[i] == '\r')
			{
				// discard \r char
				continue;
			}
			else if ((unsigned int)l_iCmdIdx < sizeof(l_acCommand))
			{
				// copy char to command string
				l_acCommand[l_iCmdIdx] = l_acBuffer[i];
				l_iCmdIdx++;
			}
			else
			{
				// out of string buffer
				std::cout << "[ERROR] received line too long!" << std::endl;
				l_iCmdIdx = 0;
				break;
			}
		}
	}
}

/*! Call the thread's main function
\return A pointer to the class istance.
*/
void* OvpnMng::recvThreadMain( void* p_pClassIstance )
{
	((OvpnMng*)p_pClassIstance)->ReceiveLoop();
	return p_pClassIstance;
}

/*! Start receive thread
\return TRUE if successfully, FALSE otherwise.
*/
bool OvpnMng::recvThreadStart( void )
{
	int l_iRes;

	if (m_boRunning == false)
	{
		l_iRes = pthread_create( &m_RecvThread,
								 NULL,
								 OvpnMng::recvThreadMain,
								 this );

		if (l_iRes == 0)
		{
			m_boRunning = true;
		}
	}

	return m_boRunning;
}

/*! Stop receive thread
\return none.
*/
void OvpnMng::recvThreadStop( void )
{
	if (m_boRunning == true)
	{
		m_boTerminateRequest = true;
		pthread_join(m_RecvThread, NULL);
		m_boTerminateRequest = false;
		m_boRunning = false;
	}
}

bool OvpnMng::GetNextResponse( LinesVector& p_Resp )
{
	bool l_boRes = false;

	pthread_mutex_lock(&m_QueueMutex);

	if (m_RespQueue.empty() == false)
	{
		p_Resp = m_RespQueue.front();
		m_RespQueue.pop();
		l_boRes = true;
	}

	pthread_mutex_unlock(&m_QueueMutex);

	return l_boRes;
}

