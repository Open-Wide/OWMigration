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
                'extension' => array(
                    'name' => 'extension',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                ),
                'version' => array(
                    'name' => 'version',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                ),
                'status' => array(
                    'name' => 'date',
                    'datatype' => 'string',
                    'default' => null,
                    'required' => true
                )
            ),
            'keys' => array(
                'extension',
                'version'
            ),
            'class_name' => 'OWMigration',
            'name' => 'owmigration',
            'function_attributes' => array( ),
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
