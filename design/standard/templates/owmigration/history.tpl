{ezcss_require( 'owmigration.css' )}
{ezscript_require( 'owmigration.js' )}
<div class="context-block">
    <div class="box-header">
        <div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
            <h1 class="context-title">{'Migration history'|i18n('owmigration/history' )}</h1>
            <div class="header-mainline"></div>
        </div></div></div></div></div>
    </div>
    <div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
        <div class="box-content">
           <div class="context-toolbar"></div>
           <div class="block">
               {if $migration_list|count()}
	               <div class="yui-dt">
	                    <table class="list">
	                        <thead>
	                            <tr class="yui-dt-first yui-dt-last">
	                                <th><div class="yui-dt-liner">{'Date'|i18n('owmigration/history' )}</div></th>
	                                <th><div class="yui-dt-liner">{'Class'|i18n('owmigration/history' )}</div></th>
	                                <th><div class="yui-dt-liner">{'Method'|i18n('owmigration/history' )}</div></th>
	                                <th><div class="yui-dt-liner"></div></th>
	                            </tr>
	                        </thead>
	                        <tbody class="yui-dt-data">
	                            {foreach $migration_list as $index => $migration sequence array( 'yui-dt-even', 'yui-dt-odd' ) as $style}
	                                <tr class="{if $index|eq(0)}yui-dt-first{/if} {$style}">
	                                    <td><div class="yui-dt-liner">{$migration.date}</div></td>
	                                    <td><div class="yui-dt-liner">{$migration.class}</div></td>
	                                    <td><div class="yui-dt-liner">{$migration.method}</div></td>
	                                    <td><div class="yui-dt-liner">
	                                        {if $migration.log_array|count()}
	                                            <a class="display_log_control" href="#" ref="row_{$index}" show_title="{'View logs'|i18n('owmigration/history' )}" hide_title="{'Hide logs'|i18n('owmigration/history' )}">{'View logs'|i18n('owmigration/history' )}</a>
	                                        {else}
	                                            {'No log data'|i18n('owmigration/history' )}
	                                        {/if}
	                                    </div></td>
	                                </tr>
	                                <tr class="log_row {if $index|eq(0)}yui-dt-first{/if} {$style}" id="row_{$index}">
	                                    <td colspan="4"><div class="yui-dt-liner">
	                                        {foreach $migration.log_array as $log}
	                                            <p class="{$log.level}">{$log.message}</p>
	                                        {/foreach}
	                                    </div></td>
	                                <tr>
	                            {/foreach}
	                        </tbody>
	                    </table>
	                </div>
                {else}
                    {'No log data'|i18n('owmigration/history' )}
                {/if}
            </div>
        </div>
    </div></div></div></div></div></div>
</div>