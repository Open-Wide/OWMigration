<?php

abstract class OWMigrationBase {

    protected $output;
    protected $db;

    public function __construct( ) {
        $this->output = eZCLI::instance( );
        $this->db = eZDB::instance( );
    }
    
    abstract function startMigrationOn( $param );
    abstract function end( );

}
