<?php

class OWMigrationContentClassCodeGenerator extends OWMigrationCodeGenerator {

    static function getMigrationClassFile( $classIdentifier, $dir ) {
        $filename = self::generateSafeFileName( $classIdentifier . '.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, self::getMigrationClass( $classIdentifier ) );
        return $filepath;
    }

    static function getMigrationClass( $classIdentifier ) {
        $trans = eZCharTransform::instance();
        $contentClass = eZContentClass::fetchByIdentifier( $classIdentifier );
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class myExtension_xxx_%s {" . PHP_EOL . PHP_EOL, $trans->transformByGroup( $contentClass->attribute( 'identifier' ), 'camelize' ) );
        $code .= self::getUpMethod( $contentClass );
        $code .= self::getDownMethod( $contentClass );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "?>";
        return $code;
    }

    static function getUpMethod( $contentClass ) {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $contentClass->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL . PHP_EOL;

        $contentClassArray = call_user_func( 'ContentClassMigrationHandler::toArray', $contentClass );
        ksort( $contentClassArray );
        foreach ( $contentClassArray as $key => $value ) {
            $code .= sprintf( "\t\t\$migration->%s = %s;" . PHP_EOL, $key, self::formatValue( $value ) );
        }
        $code .= PHP_EOL;
        $attributesList = $contentClass->fetchAttributes();
        foreach ( $attributesList as $attribute ) {
            $code .= sprintf( "\t\t\$migration->addAttribute( '%s'", self::escapeString( $attribute->attribute( 'identifier' ) ) );
            $contentClassAttributeHandlerClass = get_class( $attribute ) . 'MigrationHandler';
            $contentClassAttributeArray = call_user_func( "$contentClassAttributeHandlerClass::toArray", $attribute );
            $datatypeHandlerClass = get_class( $attribute->dataType() ) . 'MigrationHandler';
            if ( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::toArray' ) ) {
                $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
            }
            $attributeDatatypeArray = call_user_func( "$datatypeHandlerClass::toArray", $attribute );
            $attributeArray = array_merge( $contentClassAttributeArray, $attributeDatatypeArray );
            if ( count( $attributeArray ) > 0 ) {
                $code .= ", " . self::formatValue( $attributeArray );
            }
            $code .= " );" . PHP_EOL;
        }
        $code .= PHP_EOL;
        foreach ( $contentClass->attribute( 'ingroup_list' ) as $classGroup ) {
            $code .= sprintf( "\t\t\$migration->addToContentClassGroup( '%s' );" . PHP_EOL, self::escapeString( $classGroup->attribute( 'group_name' ) ) );
        }
        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $contentClass ) {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationContentClass( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $contentClass->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->removeClass( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}

?>