<?php

class OWMigrationCodeGenerator {

    static function createDirectory( $dir ) {
        if( !is_dir( $dir ) ) {
            mkdir( $dir, 0777, TRUE );
        }
    }

    static function removeDirectory( $dir ) {
        var_dump($dir);
        if( !file_exists( $dir ) )
            return true;
        if( !is_dir( $dir ) || is_link( $dir ) )
            return unlink( $dir );
        foreach( scandir($dir) as $item ) {
            if( $item == '.' || $item == '..' )
                continue;
            if( !self::removeDirectory( $dir . "/" . $item ) ) {
                chmod( $dir . "/" . $item, 0777 );
                if( !self::removeDirectory( $dir . "/" . $item ) )
                    return false;
            };
        }
        return rmdir( $dir );
    }

    static function escapeString( $str ) {
        return addcslashes( $str, "'" );
    }
    
    static function generateSafeFileName( $name ) {
        $trans = eZCharTransform::instance( );
        return $trans->transformByGroup( $name, 'filename' ); 
    }
    
    static function generateClassName( $name ) {
        $trans = eZCharTransform::instance( );
        return $trans->transformByGroup( $name, 'camelize' ); 
    }
}
?>