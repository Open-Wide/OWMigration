<?php

class OWMigrationVersion extends eZPersistentObject {

    const INSTALLED_STATUS = 'installed';
    const UNINSTALLED_STATUS = 'uninstalled';
    const NEVER_INSTALLED_STATUS = 'never-installed';
    const NONEXISTANT_STATUS = 'nonexistent';

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
            'class_name' => 'OWMigrationVersion',
            'name' => 'owmigration_version',
            'function_attributes' => array( ),
            'set_functions' => array( ),
            'grouping' => array( )
        );
    }

    static function fetch( $extension, $version ) {
        $conds = array(
            'extension' => $extension,
            'version' => $version
        );
        return self::fetchObject( self::definition( ), null, $conds );
    }

    static function fetchAllVersion( $extension, $includeNeverInstalled ) {
        $version = self::fetchList( array( 'extension' => $extension ) );
        if( $includeNeverInstalled ) {
            // TODO scan extension directory to add migration whose are not in database
        }
    }

    static function fetchLastestVersion( $extension, $status = self::INSTALLED_STATUS ) {
        $conds = array(
            'extension' => $extension,
            'status' => $status
        );
        $customFields = array( array(
                'operation' => 'MAX( version )',
                'name' => 'version'
            ) );
        switch ($status) {
            case self::NONEXISTANT_STATUS :
                return array( );
                break;
            case self::NEVER_INSTALLED_STATUS :
                // TODO scan extension directory
                return array( );
                break;
            default :
                return self::fetchObject( self::definition( ), null, $conds, true, array( 'extension' ), $customFields );
        }
    }

    static function fetchList( $conds = array(), $limit = NULL ) {
        return self::fetchObjectList( self::definition( ), null, $conds, array( 'version' => 'asc', ), $limit, true, false, null, null, null );
    }

}
