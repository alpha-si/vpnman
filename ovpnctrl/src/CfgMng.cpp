/**
 * @file   CfgMng.cpp
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Implementation of class CfgMng.
 *
 * CfgMng: This class handles the configuration file
 */


#include "CfgMng.h"
#include <stdio.h>
#include <stdlib.h>
#include "string.h"


/*! class contructor
\return none
*/
CfgMng::CfgMng()
{
    m_uiLastId = 0;
    m_CfgFilename = "";
}


/*! class destructor
\return none
*/
CfgMng::~CfgMng()
{
	// TODO Auto-generated destructor stub
}


/*! configuration file parsing
@param[in] p_CfgFilename pathname of configuration file to be loaded
\return true if successful, false otherwise
*/
bool CfgMng::LoadConfig(std::string& p_CfgFilename)
{
   bool       	l_boResult = false;
   std::string  l_Line;
   std::string  l_ParamName;
   std::string  l_ParamValue;
   FILE* 		l_pFile;
   char*		l_pcLine = NULL;
   size_t 		l_iLen = 0;
   ssize_t 		l_iRead;

   m_CfgFilename = p_CfgFilename;

   /* Opens parameters file in read mode. */
   l_pFile = fopen(p_CfgFilename.c_str(), "r");

   if (l_pFile != NULL)
   {
      m_ParamValue.clear();

      /* Parses each line. */
      while ((l_iRead = getline(&l_pcLine, &l_iLen, l_pFile)) != -1)
      {
    	  if (strlen(l_pcLine) == 0)
    	  {
    		  free(l_pcLine);
    		  continue;
    	  }

    	  if (l_pcLine[0] == '#')
    	  {
    		  free(l_pcLine);
    		  continue;
    	  }

    	  char* l_pcToken = strtok(l_pcLine, "=");

    	  if (l_pcToken)
    	  {
    		  l_ParamName = std::string(l_pcToken);
    		  l_ParamName.erase(l_ParamName.find_last_not_of(" \n\r\t")+1);
    	  }

    	  l_pcToken = strtok(NULL, "=");

    	  if (l_pcToken)
    	  {
    		  l_ParamValue = std::string(l_pcToken);
    		  l_ParamValue.erase(l_ParamValue.find_last_not_of(" \n\r\t")+1);
    	  }


    	  if ((l_ParamName.length() > 0) && (l_ParamValue.length() > 0))
    	  {
    		  /* Set the parameter. */
    		  SetParameter(l_ParamName, l_ParamValue);
    	  }

    	  //free(l_pcLine);
      }

      l_boResult = true;
      fclose(l_pFile);
   }
   else
   {
	   /*@@@
       g_LogMng.LogMsg( QString("[ERROR] Impossibile aprire il file di configurazione %1 (errore: %2")
                        .arg(p_CfgFilename)
                        .arg(l_CfgFile.errorString()) );
       */
   }

   return l_boResult;
}

/*! Insert or update a configuration parameter
@param[in] p_Name	parameter name
@param[in] p_Value	parameter value.
\return nessuno.
*/
void CfgMng::SetParameter(const std::string& p_Name,
                          const std::string& p_Value)
{
   String2IntIter l_Iter;
   String2IntPair l_Pair;

   /* Finds parameter name in the map. */
   l_Iter = m_ParamName.find(p_Name);

   /* Checks if this parameter name exists(i.e. is valid). */
   if (l_Iter == m_ParamName.end())
   {
      m_uiLastId++;

      l_Pair.first = p_Name;
      l_Pair.second = m_uiLastId;

      m_ParamName.insert(l_Pair);
      m_ParamValue[m_uiLastId] = p_Value;
   }
   else
   {
       m_ParamValue[l_Iter->second] = p_Value;
   }
}

/*! Get parameter value
@param[in]	p_Name	parameter name.
@param[out] p_Value	parameter value.
\return true if the requested parameter name exists, false otherwise.
*/
bool CfgMng::GetParam( std::string p_Name, std::string& p_Value )
{
   String2IntIter l_Iter;
   bool l_boResult = false;

   /* Finds parameter name in the map. */
   l_Iter = m_ParamName.find(p_Name);

   /* Checks if this parameter name exists(i.e. is valid). */
   if (l_Iter == m_ParamName.end())
   {
       p_Value = "";
   }
   else
   {
       p_Value = m_ParamValue[l_Iter->second];
       l_boResult = true;
   }

   return l_boResult;
}

