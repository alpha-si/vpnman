#! /bin/bash
 
# Function calculates number of bit in a netmask
#
mask2cidr()
{
    nbits=0
    IFS=.
    for dec in $1 ; do
        case $dec in
            255) let nbits+=8;;
            254) let nbits+=7;;
            252) let nbits+=6;;
            248) let nbits+=5;;
            240) let nbits+=4;;
            224) let nbits+=3;;
            192) let nbits+=2;;
            128) let nbits+=1;;
            0);;
            *) echo "Error: $dec is not recognised"; exit 1
        esac
    done
    echo "$nbits"
}
 
cidr=$(mask2cidr $route_netmask_1)

# Configure source nat from vpn tunnel to every network interfaces
for entry in `ls /sys/class/net`; do
   regex='^eth|wlan[0-9]{1,2}$'
   if [[ $entry =~ $regex ]]
   then
     iptables -t nat -A POSTROUTING -s $route_network_1/$cidr -o $entry -j MASQUERADE
   fi
done
 
# vpnman-ap-ctrl handles VPNMAN custom command received from openvpn server
# NOTE: currently the only custom command is "vpnman-subnet-map"
#
#/etc/openvpn/ovpnctrl-ap/vpnman-ap-ctrl $route_network_1 $route_netmask_1 &