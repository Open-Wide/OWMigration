<?php

class OWMigrationCamelizeTransformation {

    static function executeCommand( $text, $command, $charsetName ) {
        return preg_replace( array_keys( array(
            '#/(.?)#e' => "'::'.strtoupper('\\1')",
            '/(^|_|-)+(.)/e' => "strtoupper('\\2')"
        ) ), array_values( array(
            '#/(.?)#e' => "'::'.strtoupper('\\1')",
            '/(^|_|-)+(.)/e' => "strtoupper('\\2')"
        ) ), $text );
    }

}
?>

