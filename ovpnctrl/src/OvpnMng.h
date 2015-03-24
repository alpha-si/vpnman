/**
 * @file   OvpnMng.cpp
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Implementation of class OvpnMng.
 *
 * This class handles the OpenVPN management interface
 */

#ifndef OVPNMNG_H_
#define OVPNMNG_H_

#include <pthread.h>
#include <string>
#include <vector>
#include <queue>

typedef std::vector<std::string> LinesVector;
typedef std::queue<LinesVector> RespQueue;

/** This class handles the OpenVPN management interface */
class OvpnMng
{
protected:

	bool m_boConnected;				/*!< status of connection with OpenVPN management socket */
	int m_iSocket;					/*!< connection socket */
	bool m_boRunning;				/*!< status of receiving task */
	bool m_boTerminateRequest;		/*!< flag for receiving thread termination */
	pthread_t m_RecvThread;			/*!< thread used for data reception from socket */
	pthread_mutex_t m_SocketMutex;	/*!< mutex semaphore */
	pthread_mutex_t m_QueueMutex;	/*!< mutex semaphore */

	/* utility variables for parsing */
	char l_acBuffer[4096];
	char l_acCommand[1024];
	int l_iCmdIdx;

	LinesVector m_CurrentResp;
	RespQueue	m_RespQueue;
	bool		m_boRespInProgress;

	// Receiving thread
	void ReceiveLoop( void );
	bool recvThreadStart( void );
	void recvThreadStop( void );
	static void* recvThreadMain( void* p_pClassIstance );

	void handleNewLine( char* p_acLine );
	bool isOneLineNotification( const char* p_acLine );
	bool isEndOfNotification( const char* p_acLine );

public:

	OvpnMng();
	virtual ~OvpnMng();
	bool Connect( const char* p_acAddress, ushort p_usPort = 7505 );
	void Disconnect( void );
	inline bool IsConnected( void ) { return m_boConnected; }
	void SendCmd( std::string p_Cmd );
	bool GetNextResponse( LinesVector& p_Resp );
};

#endif /* OVPNMNG_H_ */
