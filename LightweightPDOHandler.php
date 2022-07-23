<?php
################################################################################
#
#   Lightweight PDO Database Handler
#
#   Version:        0.3
#   Date:           2022-07-20
#
#   Author:         Katharina Philipp Klinz
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
class Database {
    
    # 1 Variables for Class
    # 1.1 PDO and connection handles
    private     $DSNs                   = array();
    private     $Config                 = array('Default' => array());
    private     $Handles                = array('Default' => array());
    private     $Defaults               = array
                (
                    'PDSN'                  => 'mysql',
                    'Ports'                 => array(
                        'mysql'                 => 3306,
                        'pgsql'                 => 5432
                    ),
                    'Charset'               => 'UTF8'
                );
    
    # 1.2 Statements
    private     $lastInsert             = array('Default' => FALSE);
    private     $Count                  = array('Default' => FALSE);
    
    # 1.3 Errors
    private     $Error                  = array();
    private     $Errors                 = array
                (
                    'Config-001'            => 'No host given in config.',
                    'Config-002'            => 'No user given in config.',
                    'Config-003'            => 'No password given in config.',
                    'Config-004'            => 'No database name given in config.',
                    'Config-005'            => 'Given PDSN is not supported.',
                    'Config-006'            => 'No port given in config.',
                    'Config-007'            => 'Given config is not an array.',
                    'Connect-001'           => 'Connection failed.',
                    'Handle-001'            => 'Handle already exists.',
                    'executeQuery-001'      => 'Prepare failed.',
                    'executeQuery-002'      => 'Execute failed.',
                    'executeQuery-003'      => 'SQL File not found.'
                );
    
    # 1.4 Class handling
    private     $Debug                  = TRUE;
    
    # 2 Magic Methods
    # 2.1 __CONSTRUCT
    public function __CONSTRUCT($Config = FALSE, $Debug = FALSE)
    {
        # 2.1.1 Set Debug Mode
        $this->setDebug($Debug);
        
        # 2.1.2 Get list of available PDO Drivers
        $this->DSNs                     = PDO::getAvailableDrivers();
        
        # 2.1.2 Set Config for Default Connection, if a Config is given
        if($Config) {
            $this->setConfig($Config);

            # 2.1.3 Establish Default Connection
            $this->connect();
        }
    }
    
    # 2.2 __SET
    public function __SET($Var, $Value)
    {
        
    }
    
    # 2.3 __GET
    public function __GET($Var)
    {
        switch($Var)
        {
            # 2.3.1 Return last insert id for default
            case 'lastInsert':
                return $this->lastInsert();
        
            # 2.3.2 Return num rows count for default
            case 'Count':
                return $this->Count['Default'];
        
            # 2.3.3 Return Error
            case 'Error':
                if(count($this->Error) > 0)
                {
                    return $this->Error;
                }
                else
                {
                    return FALSE;
                }
                
            # 2.3.4 Return a handle
            default:
                if(isset($this->Handles[$Var]))
                {
                    return $this->Handles[$Var];
                }
                else
                {
                    return FALSE;
                }
        }
    }
    
    # 3 Public methods
    # 3.1 query()
    public function query($Query, $Data = false, $Handle = 'Default')
    {
        # 3.1.1 just return executeQuery()
        return $this->executeQuery($Query, $Data, $Handle);
    }
    
    # 3.2 insert()
    public function insert($Table, $Data, $Handle = 'Default')
    {
        if($Table == '' || !is_array($Data) && count($Data) < 1)
        {
            $this->error                = 'input error';
            return FALSE;
        }
        
        $InsertQuery                    = $this->buildInsertQuery($Table, $Data);
        $InsertSTMT                     = $this->Handles[$Handle]->prepare($InsertQuery);
        
        $i                              = 1;
        foreach($Data AS $Column => $Value)
        {
            $InsertSTMT->bindParam(':' . $Column, $$Column);
            $$Column                    = $Value;
        }
        
        if($InsertSTMT->execute())
        {
            $this->lastInsert[$Handle]  = $this->Handles[$Handle]->lastInsertID();
            return TRUE;
        }
        else
        {
            $this->Error[]              = array
            (
                'Query'                     => $InsertQuery,
                'Data'                      => $Data,
                'ErrorInfo'                 => $InsertSTMT->errorInfo(),
            );
            return FALSE;
        }
    }
    
    # 3.3 select()
    public function select()
    {
        
    }
    
    # 3.4 update()
    public function update()
    {
        
    }
    
    # 3.5 delete()
    public function delete()
    {
        
    }

    # 3.6 count()
    public function count()
    {
        
    }
    
    # 3.7 lastInsert()
    public function lastInsert($Handle = 'Default')
    {
        return $this->lastInsert[$Handle];
    }
    
    # 3.8 getSQL()
    public function getSQL($File)
    {
        if(file_exists($File))
        {
            return file_get_contents($File);
        }
        else
        {
            $this->Error[]              = array
            (
                'Message'                   => $this->Errors['Connect-003'],
                'Value'                     => $File
            );
        }
    }
    
    # 3.9 newHandle()
    public function newHandle($Config, $Handle)
    {
        # 3.9.1 check if config is an array before do further checks
        if(is_array($Config))
        {
            # 3.9.1.1 check if handle already exists
            if(!isset($this->Handles[$Handle]))
            {
                # 3.9.1.1.1 set config
                $this->setConfig($Config, $Handle);
                
                # 3.9.1.1.2 seems okay, establish connection
                $this->connect($Handle);
                
                # 3.9.1.1.3 return true
                return true;
            }
            else
            {
                $this->Error[]          = $this->Errors['Handle-001'];
                return false;
            }
        }
        else
        {
            $this->Error[]              = $this->Errors['Config-007'];
            return false;
        }
    }
    
    # 4 Private Methods
    # 4.1 setDebug()
    private function setDebug($Debug = FALSE)
    {
        # 4.1.1 Set Debug true if Debug is given
        if($Debug === TRUE)
        {
            $this->Debug                = TRUE;
        }
    }
    
    # 4.2 setConfig()
    private function setConfig($Config, $Handle = 'Default')
    {
        # 4.2.1 Set defaults (if needed)
        $Config                         = $this->setConfigDefaults($Config);

        # 4.2.2 Check given Config for errors
        if(!$this->checkConfig($Config))
        {
            return FALSE;
        }
        
        # 4.2.3 Set config to given handle and return true
        else
        {
            $this->Config[$Handle]          = array
            (
                'Host'                          => $Config['Host'],
                'User'                          => $Config['User'],
                'Password'                      => $Config['Password'],
                'Name'                          => $Config['Name'],
                'Port'                          => $Config['Port'],
                'Charset'                       => $Config['Charset'],
                'PDSN'                          => $Config['PDSN']
            );

            return TRUE;
        }
    }
    
    # 4.3 checkConfig()
    private function checkConfig($Config, $Handle = 'Default')
    {
        # 4.3.1 Define Error Flag
        $Error                          = array();
        
        # 4.3.2 Check given Host
        if(!isset($Config['Host']) || empty($Config['Host']))
        {
            $Error[]                    = $this->Errors['Config-001'];
        }
        
        # 4.3.3 Check given User
        if(!isset($Config['User']) || empty($Config['User']))
        {
            $Error[]                    = $this->Errors['Config-002'];
        }
        
        # 4.3.4 Check given Password
        if(!isset($Config['Password']) || empty($Config['Password']))
        {
            $Error[]                    = $this->Errors['Config-003'];
        }
        
        # 4.3.5 Check given Database Name
        if(!isset($Config['Name']) || empty($Config['Name']))
        {
            $Error[]                    = $this->Errors['Config-004'];
        }
        
        # 4.3.6 Check if given Driver is supported
        if(!isset($Config['PDSN']) && in_array($Config['PDSN'],$this->DSNs))
        {
            $Error[]                    = $this->Errors['Config-005'];
        }
        
        # 4.3.7 Check if port is given
        if(!isset($Config['Port']) || empty($Config['Port']))
        {
            $Error[]                    = $this->Errors['Config-006'];
        }
        
        # 4.3.8 Check if there was an error, return false
        if(count($Error) > 0)
        {
            return FALSE;
        }
        
        # 4.3.9 Return true if there was no error
        else
        {
            return TRUE;
        }
    }
    
    # 4.4 setConfigDefaults()
    private function setConfigDefaults($Config)
    {
        
        # 4.4.1 Set PDSN if not given
        if(!isset($Config['PDSN']))
        {
            $Config['PDSN']             = $this->Defaults['PDSN'];
        }
        
        # 4.4.2 Set Port if not given
        if(!isset($Config['Port']) && isset($this->Defaults['Ports'][$Config['PDSN']]))
        {
            $Config['Port']             = $this->Defaults['Ports'][$Config['PDSN']];
        }
        
        # 4.4.3 Set Charset if not given
        if(!isset($Config['Config']))
        {
            $Config['Charset']          = $this->Defaults['Charset'];
        }
        
        # 4.4.4 Return Config with defaults
        return $Config;
    }
    
    # 4.5 connect()
    private function connect($Handle = 'Default')
    {
        # 4.5.1 Get PDO DSN connect string
        switch($this->Config[$Handle]['PDSN'])
        {
            # 4.5.1.1 mySQL
            case 'mysql':
                $ConnectString          = $this->connectStringMySQL($this->Config[$Handle]);
                break;
            
            # 4.5.1.2 PostgreSQL
            case 'pgsql':
                $ConnectString          = $this->connectStringPGSQL($this->Config[$Handle]);
                break;
        }
        
        # 4.5.2 try connect to database
        try
        {
            # 4.5.2.1 Establish connection
            $this->Handles[$Handle]     = new PDO
            (
                $ConnectString,
                $this->Config[$Handle]['User'],
                $this->Config[$Handle]['Password']
            );
            
            # 4.5.2.2 Set Charset for connecction
            $this->Handles[$Handle]->exec('SET NAMES "' . $this->Config[$Handle]['Charset'] . '"');
            
            # 4.5.2.3 Set enhanced error messages if debug mode is true
            if($this->Debug)
            {
                $this->Handles[$Handle]->setAttribute
                (
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
            }
        }
        
        # 4.5.3 throw exeption into errors array and return false
        catch(PDOException $e)
        {
            $this->Error[]              = array
            (
                'Message'                   => $this->Errors['Connect-001'],
                'Value'                     => $e
            );
            
            return FALSE;
        }
        
        # 4.5.4 return true if everything was ok
        return TRUE;
    }
    
    # 4.6 connectStringMySQL()
    private function connectStringMySQL($Config)
    {
        # 4.6.1 Build connect string: Driver
        $ConnectString                  = 'mysql:';
        
        # 4.6.2 Build connect string: Host
        $ConnectString                  .= 'host=' . $Config['Host'] . ';';
        
        # 4.6.3 Build connect string: Port
        $ConnectString                  .= 'port=' . $Config['Port'] . ';';
        
        # 4.6.4 Build connect string: Database Name
        $ConnectString                  .= 'dbname=' . $Config['Name'];

        # 4.6.5 return connect string
        return $ConnectString;
    }
    
    # 4.7 connectPGSQL()
    private function connectStringPGSQL($Config)
    {
        
        # 4.7.1 Build connect string: Driver
        $ConnectString                  = 'pgsql:';
        
        # 4.7.2 Build connect string: Host
        $ConnectString                  .= 'host=' . $Config['Host'] . ';';
        
        # 4.7.3 Build connect string: Port
        $ConnectString                  .= 'port=' . $Config['Port'] . ';';
        
        # 4.7.4 Build connect string: Database Name
        $ConnectString                  .= 'dbname=' . $Config['name'];
        
        # 4.7.5 return connect string
        return $ConnectString;
    }
    
    # 4.8 connectStringCUBRID()
    # 4.9 connectStringMSSQL()
    # 4.10 connectStringFirebird()
    # 4.11 connectStringIBM()
    # 4.12 connectStringInformix()
    # 4.13 connectStringOracle()
    # 4.14 connectStringODBC()
    # 4.15 connectStringSQLite()
   
    # 4.16 close()
    private function close($Handle = 'Default')
    {
        # 4.16.1 Check if handle exists, close and return true
        if(isset($this->Handles[$Handle]))
        {
            $this->Handles[$Handle]     = NULL;
            return TRUE;
        }
        
        # 4.16.2 Return false if handle not exist
        else
        {
            return FALSE;
        }
    }
    
    # 4.17 executeQuery()
    private function executeQuery($Query, $Data = false, $Handle = 'Default')
    {
        # 4.17.1 try to prepare statement
        try
        {
            # 4.17.1.1 Prepare statement
            $Statement                  = $this->Handles[$Handle]->prepare($Query);
            
            # 4.17.1.2 try to execute statement
            try
            {
                # 4.17.1.2.1 Execute statement
                if(is_array($Data))
                {
                    $Statement->execute($Data);
                }
                else
                {
                    $Statement->execute();
                }
                
                # 4.17.1.2.2 Save rowCount
                #$this->Count[$Handle]   = $Statement->rowCount();
                
                # 4.17.1.2.3 Return statement
                return $Statement;
            }
            
            # 4.17.1.3 Catch error if statement failed
            catch(PDOException $e)
            {
                # 4.17.1.3.1 Write error to error log
                $this->Error[]          = array
                (
                    'Handle'                => $Handle,
                    'Message'               => $this->Errors['executeQuery-002'],
                    'Value'                 => $e
                );
                
                return FALSE;
            }
        }
        
        # 4.17.2 Catch error if prepare failed
        catch(PDOException $e)
        {
            # 4.17.2.1 Write error to error log
            $this->Error[]              = array
            (
                'Handle'                    => $Handle,
                'Message'                   => $this->Errors['executeQuery-001'],
                'Value'                     => $e
            );
            return FALSE;
        }
    }
    
    # 4.18 buildInsertQuery()
    private function buildInsertQuery($Table, $Data)
    {
        $InsertQuery                    = 'INSERT INTO `' . $Table . '` (';
        
        $i                              = 1;
        foreach($Data AS $Column => $Value)
        {
            $InsertQuery                .= '`' . $Column . '`';
            $InsertQuery                .= ($i++ < count($Data) ? ',' : '');
        }
        
        $InsertQuery                    .= ') VALUES (';
        
        $i                              = 1;
        foreach($Data AS $Column => $Value)
        {
            $InsertQuery                .= ':' . $Column;
            $InsertQuery                .= ($i++ < count($Data) ? ',' : '');
        }
        
        $InsertQuery                    .= ')';
        
        return $InsertQuery;
    }
    
    # 4.19 buildUpdateQuery()
    private function buildUpdateQuery($Table, $Data)
    {
        
    }
}
