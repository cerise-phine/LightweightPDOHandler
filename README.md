# LightweightPDOHandler
Lightweight database handler for multiple connections

I often use this little class to handle my database connections in simple way. Its not 100 % feature complete but its working

# Usage example
>$DBConfig                                       = array(
>    'Host'                                          => 'localhost',
>    'Port'                                          => 3306,
>    'Name'                                          => 'SCHEME NAME',
>    'User'                                          => 'USERNAME',
>    'Password'                                      => 'PASSWORD',
>    'PDSN'                                          => 'mysql',
>    'Charset'                                       => 'UTF8'
>);
>
># A little function that makes it easier to ensure that i always get
># the same instance (haha a "minified multiton" ;)
>function DB($DB, $DBConfig)
>{
>    if(is_null($DB))
>    {
>        return new LightweightPDOHandler($DBConfig);
>    }
>    else
>    {
>        return $DB;
>    }
>}
>
># Instance LightweightPDOHandler
>$DB = null;
>$DB = DB($DB, $DBConfig);
>
># Make a query
>$Query = 'SELECT * FROM myTable';
>$Result = $DB->query($Query)->fetch();
>
># Make a query from a SQL File
># (I often like to store queries in a seperate SQL file)
>$Result = $DB->query($DB->getSQL('query.sql'))->fetch();
>
># Make an escape query
># Thats mostly the same as you use PDO direct
>$Query = 'SELECT * FROM myTable WHERE id = :id';
>$Result = $DB->query($Query, array('id' => $IDFromSomewhere))->fetch();
>
># Make an insert
>$DB->insert('myTable', array('Column1' => $DataFromSomwhere1, 'Column2' => $DataFromSomewhere2));

Basically thats it. I planned to implement an update and select feature, but actually i did not needed it, so its open. It just does the jobs that i need and help me handling database connections.

If you like it and you want it feature complete, give it a star.
