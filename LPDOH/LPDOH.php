<?php
################################################################################
#
#   Lightweight PDO Handler
#
#   Version:        0.4
#   Date:           2022-12-04
#
#   Author:         Katharina P. Klinz
#   Company:        private
#   Contact:        mail@cerise.rocks
#   Web:            https://www.cerise.rocks/
#   License:        MIT
#   Description:    Lightweight database handler for multiple connections
#   
#   Copyright (c) 2022 Katharina Philipp Klinz
#   Permission is hereby granted, free of charge, to any person obtaining a copy
#   of this software and associated documentation files (the “Software”), to 
#   deal in the Software without restriction, including without limitation the 
#   rights to use, copy, modify, merge, publish, distribute, sublicense, and/or 
#   sell copies of the Software, and to permit persons to whom the Software is 
#   furnished to do so, subject to the following conditions:
#
#   The above copyright notice and this permission notice shall be included in 
#   all copies or substantial portions of the Software.
#
#   THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
#   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
#   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
#   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
#   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
#   FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
#   IN THE SOFTWARE. 
#
################################################################################

namespace LPDOH;

use \PDO;

/**
 * Lightweight PDO Handler
 * 
 * @see         https://github.com/cerise-phine/LightweightPDOHandler
 * @author      Katharina P. Klinz <mail@cerise.rocks>
 * @copyright   Katharina P. Klinz
 * @license     MIT
 * @note        This programm is distributed in hope that it will be usefull but
 * @note        WITHOUT ANY KIND OF WARRANTY
 */
class LPDOH
{
    # 1 Constants
    const       DEFAULTS                        = array
                (
                    'PDSN'                          => 'mysql',
                    'Ports'                         => array(
                        'mysql'                         => 3306,
                        'pgsql'                         => 5432
                    ),
                    'Charset'                       => 'UTF8'
                );
    
    const       ERRORS                          = array
                (
                    'Config-001'                    => 'No host given in config',
                    'Config-002'                    => 'No user given in config',
                    'Config-003'                    => 'No password given in config',
                    'Config-004'                    => 'No database name given in config',
                    'Config-005'                    => 'Given PDSN is not supported',
                    'Config-006'                    => 'No port given in config',
                    'Config-007'                    => 'Given config is not an array',
                    'Handle-001'                    => 'Handle already exists'
                );
    
    # 2 Properties
    # 2.1 PDO and connection handles
    /**
     * An array with available DSNs
     *
     * @var array
     */
    private     $DSNs                           = array();
    
    /**
     * An array with actual handles
     *
     * @var array
     */
    private     $Handles                        = array();
    
    # 2.3 Errors
    /**
     * This array contains occured errors
     *
     * @var array
     */
    private     $Errors                         = array();
    
    # 2.4 Class handling
    /**
     * Debug switch variable
     *
     * @var bool
     */
    private     $Debug                          = false;
    
    # 3 Magic Methods
    # 3.1 __construct
    /**
     * __construct
     * 
     * @param   array           $Config         Expects an array with database config
     * @param   bool            $Debug          Debug variable
     * 
     * @return void
     */
    public function __construct($Debug = FALSE)#: void
    {
        # 3.1.1 Set Debug Mode
        $this->setDebug($Debug);
        
        # 3.1.2 Get list of available PDO Drivers
        $this->DSNs                             = PDO::getAvailableDrivers();
    }
    
    # 3.2 __set
    /**
     * __set
     * Set a new handle if not already exist
     * 
     * @param   string          $Var            Name of the handle
     * @param   mxied           $Value          Config for handle
     * 
     * @return void
     */
    public function __set($Var, $Value): void
    {
        # 3.2.1 Check if handle exists and if config ok
        if(!isset($this->Handles[$Var]) && $this->setConfig($Var, $Value))
        {
            # 3.2.2 Instance new handle
            $this->Handles[$Var]['Handle']      = new Handle($this->Handles[$Var]['Config']);
        }
        
        # 3.2. Set error to errors log if handle exists
        else
        {
            $this->Errors[]                     = array
            (
                'Code'                              => 'Handle-001',
                'Values'                            => $Var
            );
        }
    }
    
    # 3.3 __get
    /**
     * __get
     * 
     * @param   string          $Var            Variable to get
     * 
     * @return mixed
     */
    public function __get($Var)#: mixed
    {
        switch($Var)
        {
            # 3.3.1 Return Errors
            case 'Errors':
                if(count($this->Errors) > 0)
                {
                    return $this->Errors;
                }
                else
                {
                    return null;
                }
                
            # 3.3.2 Return a handle
            default:
                if(isset($this->Handles[$Var]) && is_object($this->Handles[$Var]['Handle']))
                {
                    return $this->Handles[$Var]['Handle'];
                }
                else
                {
                    return null;
                }
        }
    }
    
    # 3.4 __debugInfo
    /**
     * __debugInfo
     * 
     * @return array
     */
    public function __debugInfo(): array
    {
        return array
        (
            'DSNs'                                  => $this->DSNs,
            'Handles'                               => $this->Handles,
            'Errors'                                => $this->Errors,
            'Debug'                                 => $this->Debug
        );
    }
    
    # 5 Private Methods
    # 5.1 setDebug
    /**
     * setDebug
     * 
     * @param   bool            $Debug          Debug variable
     * 
     * @return void
     */
    private function setDebug($Debug = false): void
    {
        if($Debug === true)
        {
            $this->Debug                        = true;
        }
    }
    
    # 5.2 setConfigDefaults
    /**
     * setConfigDefaults
     * Returns the given config array with defaults, if needed
     * 
     * @param   array           $Config         Array with config variables
     * 
     * @return bool
     */
    private function setConfigDefaults($Config): array
    {
        # 5.2.1 Set PDSN if not given
        if(!isset($Config['PDSN']))
        {
            $Config['PDSN']                     = self::DEFAULTS['PDSN'];
        }
        
        # 5.2.2 Set Port if not given
        if(!isset($Config['Port']) && isset(self::DEFAULTS['Ports'][$Config['PDSN']]))
        {
            $Config['Port']                     = self::DEFAULTS['Ports'][$Config['PDSN']];
        }
        
        # 5.2.3 Set Charset if not given
        if(!isset($Config['Config']))
        {
            $Config['Charset']                  = self::DEFAULTS['Charset'];
        }
        
        # 5.2.4 Return Config with defaults
        return $Config;
    }
    
    # 5.3 checkConfig()
    /**
     * checkConfig
     * Checks if a given config contains errors
     * 
     * @param   array           $Config         Array with config variables
     * 
     * @return bool
     */
    private function checkConfig($Config): bool
    {
        # 5.3.1 Define Error Flag
        $Errors                                 = array();
        
        # 5.3.2 Check given Host
        if(!isset($Config['Host']) || empty($Config['Host']))
        {
            $Errors[]                           = self::ERRORS['Config-001'];
        }
        
        # 5.3.3 Check given User
        if(!isset($Config['User']) || empty($Config['User']))
        {
            $Errors[]                           = self::ERRORS['Config-002'];
        }
        
        # 5.3.4 Check given Password
        if(!isset($Config['Password']) || empty($Config['Password']))
        {
            $Errors[]                           = self::ERRORS['Config-003'];
        }
        
        # 5.3.5 Check given Database Name
        if(!isset($Config['Name']) || empty($Config['Name']))
        {
            $Errors[]                           = self::ERRORS['Config-004'];
        }
        
        # 5.3.6 Check if given Driver is supported
        if(!isset($Config['PDSN']) && in_array($Config['PDSN'],$this->DSNs))
        {
            $Errors[]                           = self::ERRORS['Config-005'];
        }
        
        # 5.3.7 Check if port is given
        if(!isset($Config['Port']) || empty($Config['Port']))
        {
            $Errors[]                           = self::ERRORS['Config-006'];
        }
        
        # 5.3.8 Check if there was an error, return false
        if(count($Errors) > 0)
        {
            $this->Errors                       = $Errors;
            return false;
        }
        
        # 5.3.9 Return true if there was no error
        else
        {
            return true;
        }
    }
    
    # 5.4 setConfig
    /**
     * setConfig
     * 
     * @param   string          $Handle         Name of the handle
     * @param   array           $Config         Array with config variables
     * 
     * @return bool
     */
    private function setConfig($Handle, $Config): bool
    {
        # 5.4.1 Set config defaults if needed
        $Config                                 = $this->setConfigDefaults($Config);
        
        # 5.4.2 Check given Config for errors
        if(!$this->checkConfig($Config))
        {
            return false;
        }
        
        # 5.4.3 Set config to given handle and return true
        else
        {
            $this->Handles[$Handle]['Config']   = array
            (
                'Host'                              => $Config['Host'],
                'User'                              => $Config['User'],
                'Password'                          => $Config['Password'],
                'Name'                              => $Config['Name'],
                'Port'                              => $Config['Port'],
                'Charset'                           => $Config['Charset'],
                'PDSN'                              => $Config['PDSN']
            );

            return true;
        }
    }
}