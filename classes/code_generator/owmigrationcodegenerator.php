<?php

class OWMigrationCodeGenerator {

    static function createDirectory( $dir ) {
        if( !is_dir( $dir ) ) {
            mkdir( $dir, 0777, TRUE );
        }
    }

    static function removeDirectory( $dir ) {
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

    static function generateContentClassName( $name ) {
        $trans = eZCharTransform::instance( );
        return $trans->transformByGroup( $name, 'humanize' );
    }

    static function formatValue( $value, $baseIndent = "\t\t" ) {
        $code = '';
        if( is_array( $value ) ) {
            $code .= "array(" . PHP_EOL;
            $lastItemCountdown = count( $value );
            foreach( $value as $key => $item ) {
                if( self::isHash( $value ) ) {
                    $code .= sprintf( "%s\t'%s' => %s%s" . PHP_EOL, $baseIndent, self::escapeString( $key ), self::formatValue( $item, $baseIndent . "\t" ), $lastItemCountdown != 1 ? ',' : '' );
                } else {
                    $code .= sprintf( "%s\t%s%s" . PHP_EOL, $baseIndent, self::formatValue( $item, $baseIndent . "\t" ), $lastItemCountdown != 1 ? ',' : '' );
                }
                $lastItemCountdown--;
            }
            $code .= $baseIndent . ")";
        } elseif( is_bool( $value ) ) {
            $code .= sprintf( "%s", $value == TRUE ? 'TRUE' : 'FALSE' );
        } elseif( is_numeric( $value ) ) {
            $code .= sprintf( "%s", self::escapeString( $value ) );
        } elseif( is_string( $value ) ) {
            $code .= sprintf( "'%s'", self::escapeString( $value ) );
        }
        return $code;
    }

    static function formatNameList( $nameList, $baseIndent = "\t\t" ) {
        $nameListValue = OWMigrationTools::cleanupNameList( $nameList );
        if( !empty( $nameListValue ) ) {
            return self::formatValue( $nameListValue );
        }
        return FALSE;
    }

    static function isHash( $arr ) {
        return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
    }

}
?>