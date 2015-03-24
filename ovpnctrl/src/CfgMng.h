/**
 * @file   CfgMng.h
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Declaration of class CfgMng.
 *
 * CfgMng: This class handles the configuration file
 */


#ifndef CFGMNG_H_
#define CFGMNG_H_

#include <map>
#include <string>

typedef std::map<std::string, int>  String2IntMap;
typedef std::pair<std::string, int> String2IntPair;
typedef String2IntMap::iterator String2IntIter;
typedef std::map<int, std::string>  Int2StringMap;

/** This class handles the configuration file */
class CfgMng
{
protected:
    unsigned int    m_uiLastId;     /*!< last assigned param id */
    std::string		m_CfgFilename;  /*!< loaded configuration filename */
    String2IntMap	m_ParamName;    /*!< param_name to param_id map */
    Int2StringMap	m_ParamValue;   /*!< param_id to param_value map */

    void SetParameter( const std::string& p_Name,
                       const std::string& p_Value );

public:
	CfgMng();
	virtual ~CfgMng();

    bool LoadConfig( std::string& p_CfgFilename );
    bool GetParam( std::string p_Name, std::string& p_Value );
};

#endif /* CFGMNG_H_ */
