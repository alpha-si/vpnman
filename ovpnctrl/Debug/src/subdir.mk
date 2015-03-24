################################################################################
# Automatically-generated file. Do not edit!
################################################################################

# Add inputs and outputs from these tool invocations to the build variables 
CPP_SRCS += \
../src/CfgMng.cpp \
../src/ClientNotification.cpp \
../src/DbDrv.cpp \
../src/OvpnMng.cpp \
../src/OvpnResponseMng.cpp \
../src/ovpnctrl.cpp 

OBJS += \
./src/CfgMng.o \
./src/ClientNotification.o \
./src/DbDrv.o \
./src/OvpnMng.o \
./src/OvpnResponseMng.o \
./src/ovpnctrl.o 

CPP_DEPS += \
./src/CfgMng.d \
./src/ClientNotification.d \
./src/DbDrv.d \
./src/OvpnMng.d \
./src/OvpnResponseMng.d \
./src/ovpnctrl.d 


# Each subdirectory must supply rules for building sources it contributes
src/%.o: ../src/%.cpp
	@echo 'Building file: $<'
	@echo 'Invoking: GCC C++ Compiler'
	$(CXX) -O0 -g3 -Wall -c -fmessage-length=0 -std=c++0x -MMD -MP -MF"$(@:%.o=%.d)" -MT"$(@:%.o=%.d)" -o "$@" "$<"
	@echo 'Finished building: $<'
	@echo ' '


