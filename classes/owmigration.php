<?php

class OWMigration
{

    protected $_extension;
    protected $_currentVersion = 0;
    protected $_migrationClasses = array();
    protected $_reflectionClass;

    public function startMigrationOnExtension( $extension )
    {
        $this->_extension = $extension;
        $this->_currentVersion = 0;
        $this->_migrationClasses = array();
        $this->_loadMigrationClassesFromDirectory();
    }

    public function migrate( $toVersion = NULL, $forceDirection = NULL )
    {
        try
        {
            if( $forceDirection )
            {
                if( $toVersion )
                {
                    $this->_doMigrateStep( $forceDirection, $toVersion );
                } else
                {
                    throw new Exception( $this->_extension . ' missing version with force option', 0 );
                }
            } else
            {
                if( $toVersion === null )
                {
                    $toVersion = $this->getLatestVersion();
                }
                $this->_doMigrate( $toVersion );
            }
        } catch( Exception $e )
        {
            if( $e->getCode() == 0 )
            {
                OWScriptLogger::logError( $e->getMessage(), 'migrate' );
            } else
            {
                OWScriptLogger::logNotice( $e->getMessage(), 'migrate' );
            }
        }
        $this->_extension = NULL;
        $this->_currentVersion = 0;
    }

    public function getCurrentVersion()
    {
        $currentVersion = OWMigrationVersion::fetchLastestVersion( $this->_extension );
        if( $currentVersion )
        {
            return (int) $currentVersion->attribute( 'version' );
        }
        return 0;
    }

    public function getLatestVersion()
    {
        $versions = array_keys( $this->_migrationClasses );
        rsort( $versions );

        return isset( $versions[0] ) ? (int) $versions[0] : 0;
    }

    public function checkDatabase()
    {
        $db = eZDB::instance();
        if( method_exists( $db, 'setErrorHandling' ) )
        {
            $db->setErrorHandling( eZDB::ERROR_HANDLING_EXCEPTIONS );
        }
        $dbSchema = eZDbSchema::instance();
        try
        {
            // read original schema from dba file
            $originalSchema = eZDbSchema::read( 'share/db_schema.dba' );

            // merge schemas from all active extensions that declare some db schema
            $extensionsdir = eZExtension::baseDirectory();
            foreach( eZExtension::activeExtensions() as $activeextension )
            {
                if( file_exists( $extensionsdir . '/' . $activeextension . '/share/db_schema.dba' ) )
                {
                    if( $extensionschema = eZDbSchema::read( $extensionsdir . '/' . $activeextension . '/share/db_schema.dba' ) )
                    {
                        $originalSchema = eZDbSchema::merge( $originalSchema, $extensionschema );
                    }
                }
            }

            // transform schema to 'localized' version for current db
            // (we might as well convert $dbSchema to generic format and diff in generic format,
            // but eZDbSchemaChecker::diff does not know how to re-localize the generated sql
            $dbSchema->transformSchema( $originalSchema, true );
            @$differences = eZDbSchemaChecker::diff( $dbSchema->schema( array( 'format' => 'local', 'force_autoincrement_rebuild' => true ) ), $originalSchema );
            $migrationIni = eZINI::instance( 'owmigration.ini' );
            if( $migrationIni->hasVariable( 'CheckDatabase', 'NoCheckOnTables' ) )
            {
                $noCheckTables = $migrationIni->variable( 'CheckDatabase', 'NoCheckOnTables' );
                foreach( $differences as $action => $tableList )
                {
                    $tableList = array_keys( $tableList );
                    foreach( $tableList as $table )
                    {
                        if( in_array( $table, $noCheckTables ) )
                        {
                            unset( $differences[$action][$table] );
                        }
                    }
                }
            }
            $sqlDiff = trim( $dbSchema->generateUpgradeFile( $differences ) );
            $sqlDiff = trim( $sqlDiff, ';' );
            if( empty( $sqlDiff ) )
            {
                OWScriptLogger::logNotice( "The database schema is up to date.", 'migrate' );
            } else
            {
                $sqlDiffArray = explode( ';', $sqlDiff );
                OWScriptLogger::logWarning( "The database schema not is up to date:" . PHP_EOL . $sqlDiff . PHP_EOL . "> Do you want to execute these queries? (y/n)", 'migrate' );
                $fp = fopen( "php://stdin", "r" );
                $badAnswer = true;
                while($badAnswer)
                {
                    $result = trim( fgets( $fp ) );
                    if( strtolower( $result ) == 'y' )
                    {
                        foreach( $sqlDiffArray as $query )
                        {
                            $db->query( $query );
                        }
                        OWScriptLogger::logNotice( "The database schema has been updated.", 'migrate' );
                        $badAnswer = false;
                    } elseif( strtolower( $result ) == 'n' )
                    {
                        OWScriptLogger::logNotice( "The database schema update skipped.", 'migrate' );
                        $badAnswer = false;
                    } else
                    {
                        OWScriptLogger::logWarning( "Please answer y for yes or n for no", 'migrate' );
                    }
                }
            }
        } catch( Exception $e )
        {
            OWScriptLogger::logNotice( "Database schema check failed : " . $e->getMessage(), 'migrate' );
        }
    }

    protected function _loadMigrationClassesFromDirectory()
    {

        $directory = 'extension/' . $this->_extension . '/migrations';
        $directory = eZDir::path( array(
                    eZSys::rootDir(),
                    $directory
                ) );
        if( file_exists( $directory ) && is_dir( $directory ) )
        {
            $classesToLoad = array();
            $classes = get_declared_classes();
            $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ), RecursiveIteratorIterator::LEAVES_ONLY );
            foreach( $it as $file )
            {
                $info = pathinfo( $file->getFileName() );
                if( $file->getPath() == $directory && isset( $info['extension'] ) && $info['extension'] == 'php' )
                {
                    require_once ($file->getPathName());

                    $array = array_diff( get_declared_classes(), $classes );
                    $className = end( $array );

                    if( $className )
                    {
                        $e = explode( '_', $file->getFileName() );
                        $version = $e[0];
                        if( is_numeric( $version ) )
                        {
                            $classesToLoad[$version] = array(
                                'className' => $className,
                                'path' => $file->getPathName()
                            );
                        }
                    }
                }
            }
            ksort( $classesToLoad, SORT_NUMERIC );
            $this->_migrationClasses = $classesToLoad;
        }
    }

    protected function _doMigrate( $toVersion )
    {

        $fromVersion = $this->getCurrentVersion();
        if( $fromVersion == $toVersion )
        {
            throw new Exception( $this->_extension . ' already at version # ' . $toVersion, 1 );
        }

        $direction = $fromVersion > $toVersion ? 'down' : 'up';

        if( $direction === 'up' )
        {
            for( $i = $fromVersion + 1; $i <= $toVersion; $i++ )
            {
                $this->_doMigrateStep( $direction, $i );
            }
        } else
        {
            for( $i = $fromVersion; $i > $toVersion; $i-- )
            {
                $this->_doMigrateStep( $direction, $i );
            }
        }

        return $toVersion;
    }

    protected function _doMigrateStep( $direction, $num )
    {
        OWScriptLogger::logNotice( 'Migrate ' . $direction . ' ' . $this->_extension . ' to version ' . sprintf( '%03d', $num ), 'migrate' );
        $migration = $this->_getMigrationClass( $num );
        if( method_exists( $migration, $direction ) )
        {
            $migration->$direction();
        } else
        {
            OWScriptLogger::logNotice( 'Method ' . $direction . ' does not exist in version ' . sprintf( '%03d', $num ) . '. Nothing to do.', 'migrate' );
        }
        $version = new OWMigrationVersion( array(
            'extension' => $this->_extension,
            'version' => sprintf( '%03d', $num ),
            'status' => OWMigrationVersion::INSTALLED_STATUS
                ) );
        if( $direction == 'up' )
        {
            $version->setAttribute( 'status', OWMigrationVersion::INSTALLED_STATUS );
        } else
        {
            $version->setAttribute( 'status', OWMigrationVersion::UNINSTALLED_STATUS );
        }
        $version->store();
    }

    public function _getMigrationClass( $num )
    {
        $num = sprintf( '%03d', $num );
        if( isset( $this->_migrationClasses[$num] ) )
        {
            $className = $this->_migrationClasses[$num]['className'];
            $path = $this->_migrationClasses[$num]['path'];
            include_once ($path);
            return new $className( );
        }
        throw new Exception( 'Could not find migration class for migration step: ' . $num, 0 );
    }

    static function extensionList()
    {
        $extensionList = array();
        $ini = eZINI::instance();
        $migration = new self( );
        if( $ini->hasVariable( 'MigrationSettings', 'MigrationExtensions' ) )
        {
            $migrationExtensions = $ini->variable( 'MigrationSettings', 'MigrationExtensions' );
            if( $migrationExtensions )
            {
                foreach( $migrationExtensions as $extension )
                {
                    $migration->startMigrationOnExtension( $extension );
                    $extensionList[] = array(
                        'name' => $extension,
                        'current_version' => $migration->getCurrentVersion(),
                        'latest_version' => $migration->getLatestVersion(),
                        'all_versions' => OWMigrationVersion::fetchAllVersion( $extension )
                    );
                }
            }
        }
        return $extensionList;
    }

}
