# Upstream patch queue

Prefer a custom module, hook, or trigger over changing Dolibarr core. Only keep a core patch here when extension points cannot implement the required behavior.

For every patch, record:
- a unique filename and the upstream files affected;
- the exact Dolibarr stable tag/commit it targets;
- the reason an extension point is insufficient;
- the regression test and expected behavior;
- whether the patch applies cleanly to the next stable release.

Never edit `dolibarr/upstream/` in place. Rebase/review patches against each proposed upstream submodule update and stop the update PR if a patch conflicts or its regression test fails.
