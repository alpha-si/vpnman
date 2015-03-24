#!/bin/bash
iptables -N allow_vpn
iptables -A INPUT -j allow_vpn
