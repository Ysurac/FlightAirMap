<?php
/**
 * User Agent Generator - Test
 * @version 1.0
 * @link https://github.com/Dreyer/random-uagent
 * @author Dreyer
 */

error_reporting( E_ALL ); 
ini_set( 'display_errors', 1 );

require 'uagent.php';

#$occurrences = array();

for ( $i = 0; $i < 100; $i++ )
{
    $ua = UAgent::random();
    #$ua = UAgent::generate();
    #$ua = UAgent::generate( 'chrome', 'mac', array( 'en-US' ) );
    #$ua = UAgent::generate( 'firefox', 'mac', array( 'en-US' ) );
    #$ua = UAgent::generate( 'iexplorer', 'win', array( 'en-GB' ) );
    #$occurrences[$ua] = ( isset( $occurrences[$ua] ) ? $occurrences[$ua] + 1 : 1 );

    echo $ua . PHP_EOL;
};

#var_dump( $occurrences );
?>