#!/bin/sh

# Make sure only root can run our script
if [ "$(id -u)" != "0" ]; then
   echo "This script must be run as root" 1>&2
   exit 1
fi

# Location of openvpn binary
openvpn=""
openvpn_locations="/usr/sbin/openvpn /usr/local/sbin/openvpn"
for location in $openvpn_locations
do
  if [ -f "$location" ]
  then
    openvpn=$location
  fi
done

# PID directory
piddir="/var/run/openvpn"

# Our working directory
work=/etc/openvpn


# Check that binary exists
if ! [ -f  $openvpn ] 
then
  echo "openvpn binary not found"
  exit 1
fi

cfg=$2
cf=$(basename "$cfg")
bn=${cf%%.conf}
openvpn_pidf=${piddir}/${bn}_srv.pid
ovpnctrl_pidf=${piddir}/${bn}_ctrl.pid

# Check that config exists
if ! [ -f  $cfg ] 
then
  echo "openvpn config not found"
  exit 1
fi

# See how we were called.
case "$1" in

  start)
	echo -n $"Starting vpn: "

	if [ ! -d  $piddir ]; then
	    mkdir $piddir
	fi
   
   	if [ -s $openvpn_pidf ]; then
		kill `cat $openvpn_pidf` >/dev/null 2>&1
	fi

	if [ -s $ovpnctrl_pidf ]; then
		kill `cat $ovpnctrl_pidf` >/dev/null 2>&1
	fi
   
   	if [ -f $openvpn_pidf ]; then
      		rm -f $openvpn_pidf
	fi

	if [ -f $ovpnctrl_pidf ]; then
		rm -f $ovpnctrl_pidf;
	fi
	      
	cd $work

   	$openvpn --daemon --writepid $openvpn_pidf --config $cfg > /dev/null
       
   	if [ $? = 0 ]; then
      		echo $"success";
   	else
      		echo $"failure";
   	fi

	;;
   
  stop)
	echo -n $"Shutting down vpn: "
   
	if [ -s $openvpn_pidf ]; then
		kill `cat $openvpn_pidf` >/dev/null 2>&1
	fi
   
   	if [ -f $openvpn_pidf ]; then
      		rm -f $openvpn_pidf
	fi

	if [ -s $ovpnctrl_pidf ]; then
		kill `cat $ovpnctrl_pidf` >/dev/null 2>&1
	fi

	if [ -f $ovpnctrl_pidf ]; then
		rm -f $ovpnctrl_pidf
	fi
	echo $"success";
   
	;;
   
  restart)
	$0 stop $2
	sleep 2
	$0 start $2
	;;
  
  checkfw)
   iptables -L allow_vpn
   exit
   ;;
   
  *)
	echo "Usage: openvpn {start|stop|restart} CONF_FILE"
#	exit 0
	;;
   
esac
exit 0
