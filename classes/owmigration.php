<?php

class OWMigration extends eZPersistentObject {

    protected $output;

    public function up( ) {
        throw new Exception( "Not implemented method" );

    }

    public function down( ) {
        throw new Exception( "Not implemented method" );

    }

    /* eZPersistentObject methods */
    public static function definition( ) {
        return array(
            'fields' => array(
                'class' => array(
                    'name' => 'class',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                ),
                'method' => array(
                    'name' => 'method',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                ),
                'date' => array(
                    'name' => 'date',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                ),
                'log' => array(
                    'name' => 'log',
                    'datatype' => 'text',
                    'default' => null,
                    'required' => false
                ),
            ),
            'keys' => array(
                'class',
                'method',
                'date'
            ),
            'class_name' => 'OWMigration',
            'name' => 'owmigration',
            'function_attributes' => array( 'log_array' => 'unserializeLog' ),
            'set_functions' => array( )
        );
    }

    public function unserializeLog( ) {
        return unserialize( $this->log );
    }

    static function fetchList( $conds = array(), $limit = NULL ) {
        return self::fetchObjectList( self::definition( ), null, $conds, array( 'date' => 'asc', ), $limit, true, false, null, null, null );
    }

}
