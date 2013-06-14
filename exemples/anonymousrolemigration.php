<?php
 
class AnonymousRoleMigration extends OWMigration {
    public function up( ) {
        $migration = new OWMigrationRole( );
        $migration->startMigrationOn( 'Anonymous' );
        $migration->createIfNotExists( );
 
        $migration->addPolicy( 'content', 'read', array(
            'Section' => array(
                'Bac à sable',
                'Candidatures',
                'CJW Newsletter',
                'Design',
                'Media',
                'Open Wide',
                'Open Wide Ingenierie',
                'Open Wide Outsourcing',
                'Open Wide Technologies',
                'Open Wide Video Avancee',
                'Setup',
                'Standard',
                'Users'
             ),
        ) );
        $migration->addPolicy( 'content', 'pdf', array(
            'Section' => array(
                'Bac à sable',
                'Candidatures',
                'CJW Newsletter',
                'Design',
                'Media',
                'Open Wide',
                'Open Wide Ingenierie',
                'Open Wide Outsourcing',
                'Open Wide Technologies',
                'Open Wide Video Avancee',
                'Setup',
                'Standard',
                'Users'
             ),
        ) );
        $migration->addPolicy( 'rss', 'feed' );
        $migration->addPolicy( 'user', 'login', array(
            'SiteAccess' => array(
                'openwide',
                'accelance',
                'os4i',
                'video_os4i',
                'owtech',
                'owtech_en',
                'openwide_admin',
                'accelance_admin',
                'os4i_admin',
                'owtech_admin',
                'full_admin'
             ),
        ) );
        $migration->addPolicy( 'content', 'read', array(
            'Class' => array(
                'file',
                'flash',
                'flash_player',
                'image'
             ),
            'Section' => array(
                'Bac à sable',
                'Candidatures',
                'CJW Newsletter',
                'Design',
                'Media',
                'Open Wide',
                'Open Wide Ingenierie',
                'Open Wide Outsourcing',
                'Open Wide Technologies',
                'Open Wide Video Avancee',
                'Setup',
                'Standard',
                'Users'
             ),
        ) );
        $migration->addPolicy( 'content', 'bookmark' );
        $migration->addPolicy( 'newsletter', 'subscribe' );
        $migration->addPolicy( 'newsletter', 'unsubscribe' );
        $migration->addPolicy( 'newsletter', 'configure' );
        $migration->addPolicy( 'googlesitemap', 'generate' );
        $migration->addPolicy( 'ezoe', 'editor' );
        $migration->addPolicy( 'content', 'create', array(
            'Class' => array(
                'candidature'
             ),
            'Section' => array(
                'Bac à sable',
                'Candidatures',
                'CJW Newsletter',
                'Design',
                'Media',
                'Open Wide',
                'Open Wide Ingenierie',
                'Open Wide Outsourcing',
                'Open Wide Technologies',
                'Open Wide Video Avancee',
                'Setup',
                'Standard',
                'Users'
             ),
        ) );
    }
 
    public function down( ) {
        $migration = new OWMigrationRole( );
        $migration->startMigrationOn( 'Anonymous' );
        $migration->removeRole( );
    }
}
 
?>