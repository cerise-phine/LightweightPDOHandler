<?php
################################################################################
#
#   Lightweight PDO Database Handler
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
class Handle
{
    # 1 Traits
    use CRUD\Create;
    use CRUD\Read;
    use CRUD\Update;
    use CRUD\Delete;
    
    # 2 Constants
    const       ERRORS                          = array
                (
                    
                );
    
    # 3 Properties
    /**
     * The PDO handle
     *
     * @var null|object
     */
    private     $PDOHandle                      = null;
    
    /**
     * This array contains last insert id after an insert query
     *
     * @var array
     */
    private     $lastInsert                     = null;
    
    /**
     * This array contains occured errors
     *
     * @var array
     */
    private     $Errors                         = array();
    
    /**
     * Debug switch variable
     *
     * @var bool
     */
    private     $Debug                          = false;
    
    # 4 Magic Methods
    # 4.1 __construct
    /**
     * __construct
     * 
     * @param   array           $Config         Expects an array with database config
     * @param   bool            $Debug          Debug variable
     * 
     * @return void
     */
    public function __construct($Config, $Debug = false)#: void
    {
        $this->setDebug($Debug);
        $this->connect($Config);
    }
    
    # 4.2 __set
    /**
     * __set
     * 
     * @param   string          $Var            Name of the handle
     * @param   mxied           $Value          Config for handle
     * 
     * @return void
     */
    public function __set($Var, $Value): void
    {
        
    }
    
    # 4.3 __get
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
            case 'Handle':
                $this->PDOHandle;
                
            case 'Query':
                $this->Query($Var);
            
            case 'close':
                $this->close();
            
            default:
                return null;
        }
    }
    
    # 4.4 __debugInfo
    /**
     * __debugInfo
     * 
     * @return array
     */
    public function __debugInfo(): array
    {
        return array
        (
            'PDOHandle'                             => $this->PDOHandle,
            'lastInsert'                            => $this->lastInsert,
            'Errors'                                => $this->Errors,
            'Debug'                                 => $this->Debug
        );
    }
    
    # 5 Public Methods
    # 3.1 Query
    /**
     * Query
     * 
     * @param   string          $Query          String with the query
     * @param   array           $Data           Optional data
     * 
     * @return void
     */
    public function Query($Query, $Data = false)
    {
        return $this->executeQuery($Query, $Data);
    }
    
    # 6 Private Methods
    # 6.1 setDebug
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
    
    # 6.2 connect
    /**
     * connect
     * tries to establish a database connection with PDO
     * 
     * @param   array           $Config         An array with a checked config
     * 
     * @return void
     */
    private function connect($Config): void
    {
        # 6.2.1 Get PDO DSN connect string
        switch($Config['PDSN'])
        {
            # 6.2.1.1 mySQL
            case 'mysql':
                $ConnectString                  = $this->connectStringMySQL($Config);
                break;
            
            # 6.2.1.2 PostgreSQL
            case 'pgsql':
                $ConnectString                  = $this->connectStringPGSQL($Config);
                break;
        }
        
        # 6.2.2 try connect to database
        try
        {
            # 6.2.2.1 Establish connection
            $this->PDOHandle                    = new \PDO
            (
                $ConnectString,
                $Config['User'],
                $Config['Password']
            );
            
            # 6.2.2.2 Set Charset for connecction
            $this->PDOHandle->exec('SET NAMES "' . $Config['Charset'] . '"');
            
            # 6.2.2.3 Set enhanced error messages if debug mode is true
            if($this->Debug)
            {
                $this->PDOHandle->setAttribute
                (
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
            }
        }
        
        # 6.2.3 throw exeption into errors array and return false
        catch(\PDOException $e)
        {
            $this->Errors[]                     = array
            (
                'Message'                           => $this->Errors['Connect-001'],
                'Value'                             => $e
            );
        }
    }
    
    # 6.3 connectStringMySQL
    /**
     * connectStringMySQL
     * tries to establish a database connection with PDO
     * 
     * @param   array           $Config         An array with a checked config
     * 
     * @return string
     */
    private function connectStringMySQL($Config): string
    {
        # 6.3.1 Build connect string: Driver
        $ConnectString                          = 'mysql:';
        
        # 6.3.2 Build connect string: Host
        $ConnectString                          .= 'host=' . $Config['Host'] . ';';
        
        # 6.3.3 Build connect string: Port
        $ConnectString                          .= 'port=' . $Config['Port'] . ';';
        
        # 6.3.4 Build connect string: Database Name
        $ConnectString                          .= 'dbname=' . $Config['Name'];

        # 6.3.5 return connect string
        return $ConnectString;
    }
    
    # 6.4 connectStringPGSQL
    /**
     * connectStringPGSQL
     * tries to establish a database connection with PDO
     * 
     * @param   array           $Config         An array with a checked config
     * 
     * @return string
     */
    private function connectStringPGSQL($Config): string
    {
        
        # 6.4.1 Build connect string: Driver
        $ConnectString                          = 'pgsql:';
        
        # 6.4.2 Build connect string: Host
        $ConnectString                          .= 'host=' . $Config['Host'] . ';';
        
        # 6.4.3 Build connect string: Port
        $ConnectString                          .= 'port=' . $Config['Port'] . ';';
        
        # 6.4.4 Build connect string: Database Name
        $ConnectString                          .= 'dbname=' . $Config['name'];
        
        # 6.4.5 return connect string
        return $ConnectString;
    }
    
    # 6.5 connectStringCUBRID
    # 6.6 connectStringMSSQL
    # 6.7 connectStringFirebird
    # 6.8 connectStringIBM
    # 6.9 connectStringInformix
    # 6.10 connectStringOracle
    # 6.11 connectStringODBC
    # 6.12 connectStringSQLite
    
    # 6.13 close
    /**
     * close
     * close a PDO handle
     * 
     * @return void
     */
    private function close(): void
    {
        $this->PDOHandle                = null;
    }
    
    # 6.14 executeQuery()
    /**
     * executeQuery
     * 
     * @param   string          $Query          String with the query
     * @param   array           $Data           Optional data
     * 
     * @return void
     */
    private function executeQuery($Query, $Data = false)
    {
        # 6.14.1 try to prepare statement
        try
        {
            # 6.14.1.1 Prepare statement
            $Statement                  = $this->PDOHandle->prepare($Query);
            
            # 6.14.1.2 try to execute statement
            try
            {
                # 6.14.1.2.1 Execute statement
                if(is_array($Data))
                {
                    $Statement->execute($Data);
                }
                else
                {
                    $Statement->execute();
                }
                
                # 6.14.1.2.2 Save rowCount
                #$this->Count[$Handle]   = $Statement->rowCount();
                
                # 6.14.1.2.3 Return statement
                return $Statement;
            }
            
            # 6.14.1.3 Catch error if statement failed
            catch(PDOException $e)
            {
                # 6.14.1.3.1 Write error to error log
                $this->Errors[]         = array
                (
                    'Message'               => $this->Errors['executeQuery-002'],
                    'Value'                 => $e
                );
                
                return FALSE;
            }
        }
        
        # 6.14.2 Catch error if prepare failed
        catch(PDOException $e)
        {
            # 6.14.2.1 Write error to error log
            $this->Error[]              = array
            (
                'Handle'                    => $Handle,
                'Message'                   => $this->Errors['executeQuery-001'],
                'Value'                     => $e
            );
            return FALSE;
        }
    }
}