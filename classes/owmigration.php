<?php

abstract class OWMigration {

    protected $output;

    abstract public function up( );
    abstract public function down( );

    public function __construct( ) {
        $this->output = eZCLI::instance( );
    }

}
