################################################################################
# Automatically-generated file. Do not edit!
################################################################################

-include ../makefile.init

RM := rm -rf
FLAGS := -O0 -g3 -Wall -fmessage-length=0 -std=c++0x

# All of the sources participating in the build are defined here
-include sources.mk
-include src/subdir.mk
-include subdir.mk
-include objects.mk

ifneq ($(MAKECMDGOALS),clean)
ifneq ($(strip $(C++_DEPS)),)
-include $(C++_DEPS)
endif
ifneq ($(strip $(C_DEPS)),)
-include $(C_DEPS)
endif
ifneq ($(strip $(CC_DEPS)),)
-include $(CC_DEPS)
endif
ifneq ($(strip $(CPP_DEPS)),)
-include $(CPP_DEPS)
endif
ifneq ($(strip $(CXX_DEPS)),)
-include $(CXX_DEPS)
endif
ifneq ($(strip $(C_UPPER_DEPS)),)
-include $(C_UPPER_DEPS)
endif
endif

-include ../makefile.defs

# Add inputs and outputs from these tool invocations to the build variables 
#CC := /opt/centos/devtoolset-1.0/root/usr/bin/gcc  
#CPP := /opt/centos/devtoolset-1.0/root/usr/bin/cpp
#CXX := /opt/centos/devtoolset-1.0/root/usr/bin/g++


# All Target
all: ovpnctrl

ovpnctrl: $(OBJS) $(USER_OBJS)
	@echo 'Building target: $@'
	@echo 'Invoking: GCC C++ Linker'
	$(CXX) $(FLAGS) -o "ovpnctrl" $(OBJS) $(USER_OBJS) $(LIBS)
	@echo 'Finished building target: $@'
	@echo ' '

install: all
   chmod +x ./ovpnctrl
	cp ./ovpnctrl ../../vpn/bin/ovpnctrl

clean:
	-$(RM) $(OBJS)$(C++_DEPS)$(C_DEPS)$(CC_DEPS)$(CPP_DEPS)$(EXECUTABLES)$(CXX_DEPS)$(C_UPPER_DEPS) ovpnctrl
	-@echo ' '

.PHONY: all clean dependents
.SECONDARY:

-include ../makefile.targets
