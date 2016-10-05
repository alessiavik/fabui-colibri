#!/bin/bash
###########################################################################
##                                                                       ##
##                     Set eth connection                                ##
##                                                                       ##
## This program is free software; you can redistribute it and/or modify  ##
## it under the terms of the GNU General Public License as published by  ##
## the Free Software Foundation; either version 2 of the License, or     ##
## (at your option) any later version.                                   ##
##                                                                       ##
###########################################################################

# usage: <IP_ADDRESS>

IP=${1}

if [ -z "$IP" ] ; then
    echo "Missing ip address"
    exit
fi

cat <<EOF> /etc/network/interfaces.d/eth0

allow-hotplug eth0
auto eth0
iface eth0 inet static
  address $IP
  netmask 255.255.255.0
  gateway 169.254.1.1

EOF
sudo /etc/init.d/network restart