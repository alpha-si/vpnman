/**
 * @file   DbDrv.h
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Declaration of class DbDrv.
 *
 * Database interface class.
 */

#ifndef DBDRV_H_
#define DBDRV_H_

#ifndef NULL
#define NULL 0
#endif

extern "C" {
#include <mysql/mysql.h>
}

class DbDrv
{
protected:
	MYSQL*	m_pConn;
	bool 	m_boConnected;
	char	m_acLastError[256];

public:
	DbDrv();
	virtual ~DbDrv();

	bool 		Connect( const char* p_acHost, const char* p_acUser, const char* p_acPass, const char* p_acDbName );
    void		Close( void );
    MYSQL_RES*	ExecuteQuery( const char* p_acQuery );
    int     	ExecuteUpdate( const char* p_acQuery, unsigned int* p_uiInsertId = NULL);
    //QString	GetString( MYSQL_ROW p_Row, int p_iFieldNum );
    //int       GetInt( QSqlRecord& p_Row, int p_iFieldNum );
    bool    	ExecuteScalar( const char* p_acQuery, int& p_iResValue );
    bool    	ExecuteScalar( const char* p_acQuery, char* p_acResValue, int p_iMaxResLen );
    bool    	IsConnected( void );
    void		FreeResult( MYSQL_RES* p_pSqlRes );


};

extern DbDrv g_Db;

#endif /* DBDRV_H_ */
