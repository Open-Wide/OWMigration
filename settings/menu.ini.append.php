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
Links[content_class]=owmigration/classes
Links[role]=owmigration/roles
Links[workflow]=owmigration/workflows
Links[state_group]=owmigration/state_groups
LinkNames[content_class]=Content class
LinkNames[role]=Role
LinkNames[workflow]=Workflow
LinkNames[state_group]=State group

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

