<?php

class OWMigrationStateGroupCodeGenerator extends OWMigrationCodeGenerator {

    static function getMigrationClassFile( $objectStateGroup, $dir ) {
        if( is_numeric( $objectStateGroup ) ) {
            $objectStateGroup = eZContentObjectStateGroup::fetchById( $objectStateGroup );
        }
        if( !$objectStateGroup instanceof eZContentObjectStateGroup ) {
            return FALSE;
        }
        self::createDirectory( $dir );
        $filename = self::generateSafeFileName( $objectStateGroup->attribute( 'name' ) . '_object_state.php' );
        $filepath = $dir . $filename;
        @unlink( $filepath );
        eZFile::create( $filepath, false, OWMigrationObjectStateCodeGenerator::getMigrationClass( $objectStateGroup ) );
        return $filepath;
    }

    static function getMigrationClass( $objectStateGroup ) {
        if( is_numeric( $objectStateGroup ) ) {
            $objectStateGroup = eZContentObjectStateGroup::fetchById( $objectStateGroup );
        }
        if( !$objectStateGroup instanceof eZContentObjectStateGroup ) {
            return FALSE;
        }
        $code = "<?php" . PHP_EOL . PHP_EOL;
        $code .= sprintf( "class myExtension_xxx_%sStateGroup {" . PHP_EOL, self::generateClassName( $objectStateGroup->attribute( 'identifier' ) ) );
        $code .= self::getUpMethod( $objectStateGroup );
        $code .= self::getDownMethod( $objectStateGroup );
        $code .= "}" . PHP_EOL . PHP_EOL;
        $code .= "?>";
        return $code;
    }

    static function getUpMethod( $objectStateGroup ) {
        $code = "\tpublic function up( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationStateGroup( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $objectStateGroup->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->createIfNotExists( );" . PHP_EOL;
        $languageArray = array( );
        foreach( $objectStateGroup->availableLanguages( ) as $local ) {
            $translation = $objectStateGroup->translationByLocale( $local );
            $language = $translation->attribute( 'language' );
            if( $language instanceof eZContentLanguage ) {
                if( $translation->attribute( 'name' ) != '' ) {
                    $languageArray[$language->attribute( 'locale' )]['name'] = $translation->attribute( 'name' );
                }
                if( $translation->attribute( 'description' ) != '' ) {
                    $languageArray[$language->attribute( 'locale' )]['description'] = $translation->attribute( 'description' );
                }
            }
        }
        if( $objectStateGroup->attribute( 'default_language' ) instanceof eZContentLanguage ) {
            $languageArray['default_language'] = $objectStateGroup->attribute( 'default_language' )->attribute( 'locale' );
        }
        if( !empty( $languageArray ) ) {
            $code .= sprintf( "\t\t\$migration->update( %s );" . PHP_EOL . PHP_EOL, self::formatValue( $languageArray ) );
        }
        foreach( $objectStateGroup->states() as $objectState ) {
            $code .= sprintf( "\t\t\$migration->addState( '%s'", self::escapeString( $objectState->attribute( 'identifier' ) ) );
            if( count( $objectState->translations( ) ) > 0 ) {
                $languageArray = array( );
                foreach( $objectState->availableLanguages( ) as $local ) {
                    $translation = $objectState->translationByLocale( $local );
                    $language = $translation->attribute( 'language' );
                    if( $language instanceof eZContentLanguage ) {
                        if( $translation->attribute( 'name' ) != '' ) {
                            $languageArray[$language->attribute( 'locale' )]['name'] = $translation->attribute( 'name' );
                        }
                        if( $translation->attribute( 'description' ) != '' ) {
                            $languageArray[$language->attribute( 'locale' )]['description'] = $translation->attribute( 'name' );
                        }
                    }
                }
                if( !empty( $languageArray ) ) {
                    $code .= sprintf( ", %s", self::formatValue( $languageArray ) );
                }
            }
            $code .= " );" . PHP_EOL;
        }

        $code .= "\t\t\$migration->end( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL . PHP_EOL;
        return $code;
    }

    static function getDownMethod( $objectStateGroup ) {
        $code = "\tpublic function down( ) {" . PHP_EOL;
        $code .= "\t\t\$migration = new OWMigrationStateGroup( );" . PHP_EOL;
        $code .= sprintf( "\t\t\$migration->startMigrationOn( '%s' );" . PHP_EOL, self::escapeString( $objectStateGroup->attribute( 'identifier' ) ) );
        $code .= "\t\t\$migration->removeStateGroup( );" . PHP_EOL;
        $code .= "\t}" . PHP_EOL;
        return $code;
    }

}
?>