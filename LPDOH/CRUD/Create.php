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

namespace LPDOH\CRUD;

trait Create
{
    # 1 Public Methods
    # 1.1 insert
    /**
     * insert
     * 
     * @param   string          $Table          Name of the table to insert
     * @param   array           $Data           array with Column => Data key-value-pairs
     * 
     * @return bool
     */
    public function insert($Table, $Data): bool
    {
        if(!is_string($Table) || empty($Table) || !is_array($Data) || count($Data) < 1)
        {
            $this->error                        = 'input error';
            return false;
        }
        
        $InsertQuery                            = $this->buildInsertQuery($Table, $Data);
        $InsertSTMT                             = $this->PDOHandle->prepare($InsertQuery);
        
        foreach($Data AS $Column => $Value)
        {
            $InsertSTMT->bindParam(':' . $Column, $$Column);
            $$Column                            = $Value;
        }
        
        if($InsertSTMT->execute())
        {
            $this->lastInsert                   = $this->PDOHandle->lastInsertID();
            return true;
        }
        else
        {
            $this->Errors[]                     = array
            (
                'Query'                             => $InsertQuery,
                'Data'                              => $Data,
                'ErrorInfo'                         => $InsertSTMT->errorInfo(),
            );
            return false;
        }
    }
    
    # 2 Private Methods
    # 2.1 buildInsertQuery
    /**
     * buildInsertQuery
     * 
     * @param   string          $Table          Name of the table to insert
     * @param   array           $Data           array with Column => Data key-value-pairs
     * 
     * @return void
     */
    private function buildInsertQuery($Table, $Data)
    {
        $i                                      = 1;
        $Count                                  = count($Data);
        $InsertQueryColumns                     = '';
        $InsertQueryData                        = '';
        
        foreach($Data AS $Column => $Value)
        {
            $InsertQueryColumns                 .= '`' . $Column . '`';
            $InsertQueryColumns                 .= ($i < $Count ? ',' : '');
            
            $InsertQueryData                    .= ':' . $Column;
            $InsertQueryData                    .= ($i < $Count ? ',' : '');
            
            $i++;
        }
        
        $InsertQuery                            = 'INSERT INTO `' . $Table . '` (';
        $InsertQuery                            .= $InsertQueryColumns;
        $InsertQuery                            .= ') VALUES (';
        $InsertQuery                            .= $InsertQueryData;
        $InsertQuery                            .= ');';
        
        return $InsertQuery;
    }
}