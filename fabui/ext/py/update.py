#!/bin/env python
# -*- coding: utf-8; -*-
#
# (c) 2016 FABtotum, http://www.fabtotum.com
#
# This file is part of FABUI.
#
# FABUI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FABUI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with FABUI.  If not, see <http://www.gnu.org/licenses/>.

# Import standard python module
import os
import re
import argparse
import time
import gettext
import commands

# Import external modules
from watchdog.observers import Observer
from watchdog.events import PatternMatchingEventHandler
import pycurl

# Import internal modules
from fabtotum.utils.translation import _, setLanguage
from fabtotum.fabui.gpusher import GCodePusher
from fabtotum.update.factory  import UpdateFactory
from fabtotum.update import BundleTask, FirmwareTask, BootTask

################################################################################

class UpdateApplication(GCodePusher):
    """
    Update application.
    """
    
    def __init__(self, arch='armhf', mcu='atmega1280', lang = 'en_US.UTF-8'):
        super(UpdateApplication, self).__init__(lang=lang)
        
        self.resetTrace()
        self.factory = UpdateFactory(arch=arch, mcu=mcu, config=self.config, gcs=self.gcs, notify_update=self.update_monitor)
        self.update_stats = {}
        
        self.add_monitor_group('update', self.update_stats)      
        
    def playBeep(self):
        self.send('M300')

    def finalize_task(self):
        if self.is_aborted():
            self.set_task_status(GCodePusher.TASK_ABORTING)
        else:
            self.set_task_status(GCodePusher.TASK_COMPLETING)
        
        # do some final stuff
        
        if self.is_aborted():
            self.set_task_status(GCodePusher.TASK_ABORTED)
        else:
            self.set_task_status(GCodePusher.TASK_COMPLETED)
                
        self.stop()
    
    # Only for development
    def trace(self, msg):
        print msg
         
    def state_change_callback(self, state):
        if state == 'aborted' or state == 'finished':
            self.trace( _("Print STOPPED") )
            self.finalize_task()

    def update_monitor(self, factory=None):
        with self.monitor_lock:
            self.update_stats.update( self.factory.serialize() )
            self.update_monitor_file()

    def run(self, task_id, bundles, firmware_switch, boot_switch):
        """
        """

        self.prepare_task(task_id, task_type='update', task_controller='updates')
        self.set_task_status(GCodePusher.TASK_RUNNING)
        
        self.trace( _("Update initialized.") )

        if bundles:
            remote_bundles = self.factory.getBundles()
            if remote_bundles:
                
                for bundle_name in bundles:
                    
                    bundle = BundleTask(bundle_name, remote_bundles[bundle_name])
                    self.factory.addTask(bundle)
            
        if firmware_switch:
            remote_firmware = self.factory.getFirmware()
            if remote_firmware:
                firmware = FirmwareTask("fablin", remote_firmware)
                self.factory.addTask(firmware)
        
        if boot_switch:
            remote_boot = self.factory.getBoot()
            if remote_boot:
                boot = BootTask("boot", remote_boot)
                self.factory.addTask(boot)
        
        self.send('M150 R0 U255 B0 S50')

        self.factory.setStatus('downloading')
        for task in self.factory.getTasks():
            self.factory.setCurrentTask( task.getName() )
            self.factory.update()
            task.download()
        
        self.factory.setStatus('installing')    
        for task in self.factory.getTasks():
            self.factory.setCurrentTask( task.getName() )
            self.factory.update()
            self.send('M150 R0 U255 B0 S100')
            task.install()
            self.send('M150 R0 U255 B0 S100')

        self.playBeep()
        # Set ambient colors
        
        try:
            color = self.config.get('settings', 'color')
        except KeyError:
            color = {
                'r' : 255,
                'g' : 255,
                'b' : 255,
            }
        
        self.send("M701 S{0}".format(color['r']), group='bootstrap')
        self.send("M702 S{0}".format(color['g']), group='bootstrap')
        self.send("M703 S{0}".format(color['b']), group='bootstrap')
        
        print "finishing task"
        self.finish_task()


def main():
    # SETTING EXPECTED ARGUMENTS
    parser = argparse.ArgumentParser(formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    parser.add_argument("-T", "--task-id",     						help="Task ID.",                      default=0)
    parser.add_argument("-b", "--bundles", 							help="Bundle name to be updated" )
    parser.add_argument("--boot", action="store_true", 				help="Update boot files" )
    parser.add_argument("-f", "--firmware", action="store_true", 	help="Update firmware" )
    parser.add_argument("--lang",                                   help="Output language", 		      default='en_US.UTF-8' )
    
    # GET ARGUMENTS
    args = parser.parse_args()

    # INIT VARs
    task_id     = args.task_id
    #~ bundle      = args.bundle
    if args.bundles:
        bundles     = args.bundles.split(',')
    else:
        bundles     = []
    firmware    = args.firmware
    boot        = args.boot
    lang        = args.lang
    
    app = UpdateApplication(lang=lang)

    app.run(task_id, bundles, firmware, boot)
    app.loop()

if __name__ == "__main__":
    main()

