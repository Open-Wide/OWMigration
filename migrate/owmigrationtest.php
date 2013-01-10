<?php

class OWMigrationTest extends OWMigration {

    public function up( ) {
        $this->output->notice( "Migration Up OK" );
    }

    public function down( ) {
        $this->output->notice( "Migration Down OK" );
    }

}
