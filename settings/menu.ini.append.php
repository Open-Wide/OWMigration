<?php /*

[NavigationPart]
Part[owmigration]=Migration

[TopAdminMenu]
Tabs[]=owmigration

[Topmenu_owmigration]
NavigationPartIdentifier=owmigration
Name=Migrations
Tooltip=Génération migration code
URL[]
URL[default]=owmigration/dashboard
Enabled[]
Enabled[default]=true
Enabled[browse]=false
Enabled[edit]=false
Shown[]
Shown[navigation]=true
Shown[default]=true
Shown[browse]=true
PolicyList[]
PolicyList[]=owmigration/read

[Leftmenu_owmigration]
Name=Migrations
Links[dashboard]=owmigration/dashboard
LinkNames[dashboard]=Dashboard

[Leftmenu_owmigration_codegenerator]
Name=Code generator
Links[codegenerator_content_class]=owmigration/codegenerator_classes
Links[codegenerator_role]=owmigration/codegenerator_roles
Links[codegenerator_workflow]=owmigration/codegenerator_workflows
Links[codegenerator_state_group]=owmigration/codegenerator_state_groups
LinkNames[codegenerator_content_class]=Content class
LinkNames[codegenerator_role]=Role
LinkNames[codegenerator_workflow]=Workflow
LinkNames[codegenerator_state_group]=State group

[Leftmenu_owmigration_description]
Name=Description
Links[description_content_class]=owmigration/description_classes
Links[description_role]=owmigration/description_roles
Links[description_workflow]=owmigration/description_workflows
Links[description_state_group]=owmigration/description_state_groups
LinkNames[description_content_class]=Content class
LinkNames[description_role]=Role
LinkNames[description_workflow]=Workflow
LinkNames[description_state_group]=State group

