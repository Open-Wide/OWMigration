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
        $name = self::generateUnderscoredName( $name );
        return str_replace( "_", "", $name );
    }
    
    static function generateClassName( $name ) {
        $name = self::generateUnderscoredName( $name );
        return sfInflector::camelize( $name );
    }

    static function generateUnderscoredName( $name ) {
        $name = str_replace( "#", "_", $name );
        $name = str_replace( " ", "_", $name );
        $name = str_replace( "'", "", $name );
        $name = str_replace( '"', "", $name );
        $name = str_replace( "__", "_", $name );
        $name = str_replace( "&", "and", $name );
        $name = str_replace( "/", "_", $name );
        $name = str_replace( "\\", "_", $name );
        $name = str_replace( "?", "", $name );
        $name = str_replace( array(
            'à',
            'á',
            'â',
            'ã',
            'ä',
            'ç',
            'è',
            'é',
            'ê',
            'ë',
            'ì',
            'í',
            'î',
            'ï',
            'ñ',
            'ò',
            'ó',
            'ô',
            'õ',
            'ö',
            'ù',
            'ú',
            'û',
            'ü',
            'ý',
            'ÿ',
            'À',
            'Á',
            'Â',
            'Ã',
            'Ä',
            'Ç',
            'È',
            'É',
            'Ê',
            'Ë',
            'Ì',
            'Í',
            'Î',
            'Ï',
            'Ñ',
            'Ò',
            'Ó',
            'Ô',
            'Õ',
            'Ö',
            'Ù',
            'Ú',
            'Û',
            'Ü',
            'Ý'
        ), array(
            'a',
            'a',
            'a',
            'a',
            'a',
            'c',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'n',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'A',
            'A',
            'A',
            'A',
            'A',
            'C',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'N',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'Y'
        ), $name );
        $name = sfInflector::underscore( $name );
        return $name;
    }

}
?>