/**
 * @file   globals.h
 * @author F.Sartini
 * @date   March, 2014
 * @brief  Globals defines and variable declarations
 */

#ifndef GLOBALS_H_
#define GLOBALS_H_

#include <iostream>
#include <unistd.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include "easylogging++.h"
#include "DbDrv.h"
#include "CfgMng.h"
#include "OvpnMng.h"

#define OVPNCTRL_VERSION   "v0.0.2"

extern CfgMng 	g_Cfg;
extern DbDrv	g_Db;
extern OvpnMng 	g_Openvpn;
extern int		g_iVpnId;

#endif /* GLOBALS_H_ */
