# Update in Progress
I refactored the whole class.

# LightweightPDOHandler
Lightweight database handler for multiple connections

I often use this little class to handle my database connections in a simple way. Its not 100 % feature complete but its working.

# Usage example
```
$DBConfig                                       = array(
    'Host'                                          => 'localhost',
    'Port'                                          => 3306,
    'Name'                                          => 'SCHEME NAME',
    'User'                                          => 'USERNAME',
    'Password'                                      => 'PASSWORD',
    'PDSN'                                          => 'mysql',
    'Charset'                                       => 'UTF8'
);

# Instance LightweightPDOHandler
$DB = new LPDOH;
$DB->Handle1 = $DBConfig;

# Make a query
$Query = 'SELECT * FROM myTable';
$Result = $DB->Handle1->Query($Query)->fetch();

# Make an insert
$DB->Handle1->insert('myTable', array('Column1' => $DataFromSomwhere1, 'Column2' => $DataFromSomewhere2));
```
