<?php /*#?ini charset="utf-8"?

[Extensions]
Commands[]
Commands[camelize]=extension/owmigration/classes/transformation/owmigrationcamelizetransformation.php:OWMigrationCamelizeTransformation
Commands[filename]=extension/owmigration/classes/transformation/owmigrationfilenametransformation.php:OWMigrationFilenameTransformation
Commands[humanize]=extension/owmigration/classes/transformation/owmigrationhumanizetransformation.php:OWMigrationHumanizeTransformation


[Transformation]
Groups[]=camelize
Groups[]=filename
Groups[]=humanize

[camelize]
Files[]
Extensions[]
Commands[]
Commands[]=normalize
Commands[]=transform
Commands[]=decompose
Commands[]=transliterate
Commands[]=diacritical
Commands[]=lowercase
Commands[]=identifier_cleanup
Commands[]=camelize

[filename]
Files[]
Extensions[]
Commands[]
Commands[]=normalize
Commands[]=transform
Commands[]=decompose
Commands[]=transliterate
Commands[]=diacritical
Commands[]=lowercase
Commands[]=filename

[humanize]
Files[]
Extensions[]
Commands[]
Commands[]=normalize
Commands[]=transform
Commands[]=decompose
Commands[]=transliterate
Commands[]=diacritical
Commands[]=lowercase
Commands[]=identifier_cleanup
Commands[]=humanize