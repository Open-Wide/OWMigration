<?php

class OWMigrationHumanizeTransformation {

    static function executeCommand( $text, $command, $charsetName ) {
        if( substr( $text, -3 ) === '_id' ) {
            $text = substr( $text, 0, -3 );
        }

        return ucfirst( str_replace( '_', ' ', $text ) );
    }

}
?>

