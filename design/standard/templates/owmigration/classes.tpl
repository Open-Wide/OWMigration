{def $classlist = fetch( 'class', 'list', hash(
            'class_filter', ezini( 'ListSettings', 'IncludeClasses', 'lists.ini' ),
            'sort_by', array( 'name', true() ) 
) )}
<form method="post" action={'owmigration/classes'|ezurl()}>
    <div class="box-header">
        <h1 class="context-title">{'Migrate content class'|i18n('owmigration/classes' )}</h1>
        <div class="header-mainline"></div>
    </div>
    <div class="box-content">
        <div class="context-attributes">
			<div class="block">
			    <label for="ContentClassIdentifier">{'Classes list'|i18n( 'owmigration/classes' )}
			    <select name="ContentClassIdentifier" id="ContentClassIdentifier">
				    {foreach $classlist as $class}
				        <option value="{$class.identifier|wash()}"{cond( $class_identifier|eq( $class.identifier ), ' selected="selected"' , '' )}>{$class.name|wash()}</option>
				    {/foreach}
			    </select></label>
			</div>
        </div>
    </div>
    <div class="controlbar">
		<div class="block">
		    <input class="button" type="submit" name="ActionGenerateCode" value="{'Display code'|i18n( 'owmigration/classes' )}" />
		    <input class="button" type="submit" name="ActionExportCode" value="{'Export code'|i18n( 'owmigration/classes' )}" />
            <input class="defaultbutton" type="submit" name="ActionExportAllClassCode" value="{'Export all classes code'|i18n( 'owmigration/classes' )}" />
		</div>
    </div>
    {if $class_identifier}
    <pre>{$class_identifier|display_content_migration_class()}</pre>
    {/if}
</form>