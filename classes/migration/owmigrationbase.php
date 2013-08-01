<?php

abstract class OWMigrationBase {

    protected $output;
    protected $db;

    public function __construct( ) {
        $this->db = eZDB::instance( );
    }
    
    abstract function startMigrationOn( $param );
    abstract function end( );

}
