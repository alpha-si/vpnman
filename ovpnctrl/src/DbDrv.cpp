/**
 * @file   DbDrv.cpp
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Implementation of class DbDrv.
 *
 * Database interface class.
 */

#include "DbDrv.h"
#include "string.h"
#include <stdlib.h>
#include <stdio.h>
#include <iostream>
#include "easylogging++.h"


DbDrv::DbDrv()
{
	m_pConn = mysql_init(NULL);
	m_boConnected = false;
}


DbDrv::~DbDrv()
{
	// TODO Auto-generated destructor stub
}


bool DbDrv::Connect( const char* p_acHost, const char* p_acUser, const char* p_acPass, const char* p_acDbName )
{
	MYSQL* l_pRes;
	const char* l_pcError;

	if (m_boConnected)
	{
		return true;
	}

	if (m_pConn == 0)
	{
		return false;
	}

	l_pRes = mysql_real_connect( m_pConn, p_acHost, p_acUser, p_acPass, p_acDbName, 0, NULL, 0);

	if (l_pRes == 0)
	{
		l_pcError = mysql_error(m_pConn);

		if (l_pcError)
		{
			strncpy(m_acLastError, l_pcError, sizeof(m_acLastError));
		}

		return false;
	}

	m_boConnected = true;

	return true;
}


void DbDrv::Close( void )
{
	if (m_pConn != NULL)
	{
		mysql_close(m_pConn);
	}

	m_boConnected = false;
}


MYSQL_RES* DbDrv::ExecuteQuery( const char* p_acQuery )
{
	MYSQL_RES* l_pResultSet = NULL;

	if ( (m_boConnected == false)  || (m_pConn == NULL) )
	{
      LOG(ERROR) << "DbDrv::ExecuteQuery: no mysql connection!";
		return NULL;
	}

	if (mysql_query(m_pConn, p_acQuery) == 0)
	{
		l_pResultSet = mysql_store_result(m_pConn);
	}
	else
	{
		LOG(ERROR) << "DbDrv::ExecuteQuery: " << mysql_error(m_pConn);
	}

	return l_pResultSet;
}


int DbDrv::ExecuteUpdate( const char* p_acQuery, unsigned int* p_puiInsertId )
{
	int l_iResult = 0;

	if ( (m_boConnected == false)  || (m_pConn == NULL) )
	{
      LOG(ERROR) << "DbDrv::ExecuteUpdate: no mysql connection!";
		return 0;
	}

	if (mysql_query(m_pConn, p_acQuery) == 0)
	{
		if (p_puiInsertId != NULL)
		{
			*p_puiInsertId = (unsigned int)mysql_insert_id(m_pConn);
		}

		l_iResult = (int)mysql_affected_rows(m_pConn);
	}
	else
	{
		LOG(ERROR) << "DbDrv::ExecuteUpdate: " << mysql_error(m_pConn);
	}

	return l_iResult;
}

//QString	GetString( MYSQL_ROW p_Row, int p_iFieldNum );
//int       GetInt( QSqlRecord& p_Row, int p_iFieldNum );


bool DbDrv::ExecuteScalar( const char* p_acQuery, int& p_iResValue )
{
	MYSQL_RES* 	l_pResultSet;
	MYSQL_ROW 	l_pRow;
	bool 		l_boResult = false;

	if ( (m_boConnected == false) || (m_pConn == NULL) )
	{
      LOG(ERROR) << "DbDrv::ExecuteScalar(1): no mysql connection!";
		return false;
	}

	if (mysql_query(m_pConn, p_acQuery) == 0)
	{
		l_pResultSet = mysql_store_result(m_pConn);

		if ((l_pResultSet != NULL) && (l_pRow = mysql_fetch_row(l_pResultSet)))
		{
			p_iResValue = atoi(l_pRow[0]);
			l_boResult = true;
		}

		FreeResult(l_pResultSet);
	}
	else
	{
      LOG(ERROR) << "DbDrv::ExecuteScalar(1): " << mysql_error(m_pConn);
	}

	return l_boResult;
}


bool DbDrv::ExecuteScalar( const char* p_acQuery, char* p_acResValue, int p_iMaxResLen )
{
	MYSQL_RES* l_pResultSet;
	MYSQL_ROW l_pRow;
	bool l_boResult = false;

	if ( (m_boConnected == false) || (m_pConn == NULL) )
	{
      LOG(ERROR) << "DbDrv::ExecuteScalar(2): no mysql connection!";
		return false;
	}

	if (mysql_query(m_pConn, p_acQuery) == 0)
	{
		l_pResultSet = mysql_store_result(m_pConn);

		if ((l_pResultSet != NULL) && (l_pRow = mysql_fetch_row(l_pResultSet)))
		{
			if (l_pRow[0])
			{
				strncpy(p_acResValue, l_pRow[0], p_iMaxResLen);
			}

			l_boResult = true;
		}

		FreeResult(l_pResultSet);
	}
	else
	{
		LOG(ERROR) << "DbDrv::ExecuteScalar(1): " << mysql_error(m_pConn);
	}

	return l_boResult;
}


bool DbDrv::IsConnected( void )
{
	return m_boConnected;
}


void DbDrv::FreeResult( MYSQL_RES* p_pSqlRes )
{
	if (p_pSqlRes != NULL)
	{
		mysql_free_result(p_pSqlRes);
	}
}

