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
        $trans = eZCharTransform::instance( );
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
        //'name'
        $nameList = $contentClass->attribute( 'nameList' );
        $nameListCode = self::formatNameList( $nameList );
        if( !empty( $nameListCode ) ) {
            $code .= sprintf( "\t\t\$migration->name = %s;" . PHP_EOL, self::formatNameList( $nameList ) );
        } else {
            $code .= sprintf( "\t\t\$migration->name = '%s';" . PHP_EOL, self::escapeString( self::generateContentClassName( $contentClass->attribute( 'identifier' ) ) ) );
        }
        //'description'
        if( $contentClass->hasAttribute( 'descriptionList' ) ) {
            $descriptionList = $contentClass->attribute( 'descriptionList' );
            if( isset( $descriptionList['always-available'] ) ) {
                $descriptionListAlwaysAvailable = $descriptionList['always-available'];
                unset( $descriptionList['always-available'] );
            } else {
                $descriptionListAlwaysAvailable = FALSE;
            }
            if( implode( '', array_values( $descriptionList ) ) != '' ) {
                $code .= "\t\t\$migration->description = array(" . PHP_EOL;
                foreach( $descriptionList as $key => $value ) {
                    $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( $key ), self::escapeString( $value ) );
                }
                if( $descriptionListAlwaysAvailable === FALSE ) {
                    $descriptionListAlwaysAvailable = $key;
                }
                $code .= sprintf( "\t\t\t'%s' => '%s'," . PHP_EOL, self::escapeString( 'always-available' ), self::escapeString( $descriptionListAlwaysAvailable ) );
                $code .= "\t\t);" . PHP_EOL;
            }
        }
        //'contentobject_name'
        if( $contentClass->attribute( 'contentobject_name' ) != '' ) {
            $code .= sprintf( "\t\t\$migration->contentobject_name = '%s';" . PHP_EOL, self::escapeString( $contentClass->attribute( 'contentobject_name' ) ) );
        }
        //'url_alias_name'
        if( $contentClass->attribute( 'url_alias_name' ) != '' ) {
            $code .= sprintf( "\t\t\$migration->url_alias_name = '%s';" . PHP_EOL, self::escapeString( $contentClass->attribute( 'url_alias_name' ) ) );
        }
        //'is_container'
        if( $contentClass->attribute( 'is_container' ) == TRUE ) {
            $code .= "\t\t\$migration->is_container = TRUE;" . PHP_EOL;
        }
        //'always_available'
        if( $contentClass->attribute( 'always_available' ) == TRUE ) {
            $code .= "\t\t\$migration->always_available = TRUE;" . PHP_EOL;
        }
        //'sort_field'
        if( $contentClass->attribute( 'sort_field' ) != 1 ) {
            $code .= sprintf( "\t\t\$migration->sort_field = %s;" . PHP_EOL, self::escapeString( $contentClass->attribute( 'sort_field' ) ) );
        }
        //'always_available'
        if( $contentClass->attribute( 'sort_order' ) == FALSE ) {
            $code .= "\t\t\$migration->sort_order = FALSE;" . PHP_EOL;
        }
        $code .= PHP_EOL;
        $attributesList = $contentClass->fetchAttributes( );
        foreach( $attributesList as $attribute ) {
            $code .= sprintf( "\t\t\$migration->addAttribute( '%s'", self::escapeString( $attribute->attribute( 'identifier' ) ) );
            $contentClassAttributeHandlerClass = get_class( $attribute ) . 'MigrationHandler';
            $contentClassAttributeArray = $contentClassAttributeHandlerClass::toArray( $attribute );
            $datatypeHandlerClass = get_class( $attribute->dataType( ) ) . 'MigrationHandler';
            if( !class_exists( $datatypeHandlerClass ) || !is_callable( $datatypeHandlerClass . '::toArray' ) ) {
                $datatypeHandlerClass = "DefaultDatatypeMigrationHandler";
            }
            $attributeDatatypeArray = $datatypeHandlerClass::toArray( $attribute );
            $attributeArray = array_merge( $contentClassAttributeArray, $attributeDatatypeArray );
            if( count( $attributeArray ) > 0 ) {
                $code .= ", ".self::formatValue( $attributeArray );
            }
            $code .= " );" . PHP_EOL;
        }
        $code .= PHP_EOL;
        foreach( $contentClass->attribute( 'ingroup_list' ) as $classGroup ) {
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