<?php

$db = new mysqli('host', 'user', 'password', 'database name');

if ($db->connect_error)
{
        die ('Error connecting to DB: ' + $db->connect_error );
}
