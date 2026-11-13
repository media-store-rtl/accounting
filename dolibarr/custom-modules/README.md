# Local Dolibarr modules

Store locally authored Dolibarr modules here, outside the upstream submodule. Each module should follow Dolibarr's module conventions and document its supported Dolibarr stable version, permissions, hooks/triggers, database changes, and regression tests.

Deployment mapping: copy/release reviewed module directories into `dolibarr/upstream/htdocs/custom/` using a separate deployment process. Do not deploy automatically as part of the upstream source-sync workflow.
