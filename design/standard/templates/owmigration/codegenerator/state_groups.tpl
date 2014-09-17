<form method="post" action={'owmigration/state_groups'|ezurl()}>
    <div class="context-block">
        <div class="box-header">
            <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
	            <h1 class="context-title">{'Migrate state groups'|i18n('owmigration/state_groups' )}</h1>
	            <div class="header-mainline"></div>
	        </div></div></div></div></div>
        </div>
        <div class="box-ml"><div class="box-mr">
            <div class="box-content">
                <div class="context-toolbar"></div>
                <div class="context-attributes">
                    <div class="block">
                        <label for="ObjectStateGroupID">{'Object state groups list'|i18n( 'owmigration/state_groups' )}
                        <select name="ObjectStateGroupID" id="ObjectStateGroupID">
                            {foreach $object_state_group_list as $object_state_group}
                                <option value="{$object_state_group.id|wash()}"{cond( $object_state_group_id|eq( $object_state_group.id ), ' selected="selected"' , '' )}>{$object_state_group.current_translation.name} ({$object_state_group.identifier|wash()})</option>
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
{if $object_state_group_id}
    {$object_state_group_id|display_state_group_migration_class()}
{/if}
