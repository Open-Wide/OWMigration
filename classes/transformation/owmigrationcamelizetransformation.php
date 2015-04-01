<?php

class OWMigrationCamelizeTransformation {

    static function executeCommand( $text, $command, $charsetName ) {
        $text = preg_replace_callback(
            '/(^|_|-)+(.)/', function ($m) {
            return ucfirst( $m[2] );
        }, $text
        );
        $text = ucfirst( $text );
        return $text;
    }

}
