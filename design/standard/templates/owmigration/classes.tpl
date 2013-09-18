{def $classlist = fetch( 'class', 'list', hash(
            'sort_by', array( 'name', true() ) 
) )}
<form method="post" action={'owmigration/classes'|ezurl()}>
    <div class="context-block">
        <div class="box-header">
            <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
               <h1 class="context-title">{'Migrate content class'|i18n('owmigration/classes' )}</h1>
               <div class="header-mainline"></div>
            </div></div></div></div></div>
        </div>
	    <div class="box-ml"><div class="box-mr">
            <div class="box-content">
                <div class="context-toolbar"></div>
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
        </div></div>
	    <div class="controlbar">
	        <div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
                <div class="block">
                    <input class="button" type="submit" name="ActionGenerateCode" value="{'Display code'|i18n( 'owmigration/all' )}" />
                    <input class="button" type="submit" name="ActionExportCode" value="{'Export code'|i18n( 'owmigration/all' )}" />
                    <input class="defaultbutton" type="submit" name="ActionExportAllClassCode" value="{'Export all classes code'|i18n( 'owmigration/all' )}" />
                </div>
            </div></div></div></div></div></div>
	    </div>
    </div>
</form>
{if $class_identifier}
    {$class_identifier|display_content_migration_class()}
{/if}