# Activity Finder — Solr backend

Provides the **`solr`** `ActivityFinderBackend` plugin (Search API / Solr) for
[Open Y Activity Finder](../../README.md), plus the Search API server, index and
index processors it needs. Enable this submodule only when you want Activity
Finder to serve **real indexed content**. For local UI / theme / front-end work
the main module's default **Mock** backend is enough — you do **not** need Solr.

## What it provides

- `Drupal\openy_activity_finder_solr\Plugin\ActivityFinderBackend\SolrBackend`
  — the `solr` backend plugin (wraps `OpenyActivityFinderSolrBackend`).
- The 8 Search API processors that index Activity Finder fields
  (`af_ages_min_max`, `af_duration`, `af_parts_of_day`, `af_start_month`,
  `af_weekdays_parts_of_day`, …).
- Config (`config/optional`): `search_api.server.solr`,
  `search_api.index.default`.

## Requirements

- `search_api` + `search_api_solr`.
- A reachable Solr server with a core for this site's index.
- Activity Finder **content with sessions** — Solr has nothing to return until
  session content exists and is indexed (see "Demo content" below).

## DDEV setup (full recipe)

End-to-end on a DDEV YUSAOpenY site. Replace `<project>` with your DDEV project
name (`ddev describe`).

```sh
# 1. Solr add-on + restart (brings up the solr service on solr:8983).
ddev add-on get ddev/ddev-drupal-solr
ddev restart

# 2. Search API + Solr modules (idempotent; the distro usually ships these).
ddev drush pm:enable search_api search_api_solr search_api_solr_admin -y

# 3. This submodule — installs the `solr` Search API server + `default` index.
ddev drush en openy_activity_finder_solr -y

# 4. Generate the Solr core config from a Solr server.
#    NOTE: the distro's Solr server is named `solr_search`. Use an ABSOLUTE
#    path inside the web container — a relative path streams the zip to stdout
#    and writes no file.
ddev exec "drush search-api-solr:get-server-config solr_search /tmp/sc.zip"

# 5. Pull the zip out and unzip it (solrconfig.xml, schema.xml, ...).
docker cp ddev-<project>-web:/tmp/sc.zip /tmp/sc.zip
rm -rf /tmp/solrconf && mkdir -p /tmp/solrconf && unzip -o /tmp/sc.zip -d /tmp/solrconf

# 6. Create the `dev` core with that config.
#    On remote docker contexts the host .ddev/solr mount may not reach the
#    container, so push the conf in with `docker cp` rather than the mount.
docker cp /tmp/solrconf ddev-<project>-solr:/tmp/devconf
docker exec ddev-<project>-solr bin/solr delete -c dev || true
docker exec ddev-<project>-solr bin/solr create -c dev -d /tmp/devconf
# Verify: cores STATUS shows core `dev`, initFailures {}, schema drupal-*-solr-8.x.
docker exec ddev-<project>-solr curl -s 'http://localhost:8983/solr/admin/cores?action=STATUS&wt=json'

# 7. Point the `solr` Search API server (used by the `default` index) at core
#    `dev` (the shipped config ships a generic connector).
ddev drush php:eval '$c=\Drupal::configFactory()->getEditable("search_api.server.solr");$b=$c->get("backend_config");$b["connector_config"]["core"]="dev";$b["connector_config"]["host"]="solr";$b["connector_config"]["port"]=8983;$b["connector_config"]["solr_install_dir"]="/opt/solr";$c->set("backend_config",$b)->save();'
ddev drush cr
ddev drush php:eval '$s=\Drupal\search_api\Entity\Server::load("solr"); var_dump($s->getBackend()->isAvailable());'  # must be TRUE

# 8. Demo content with sessions.
#    GOTCHA: on a fresh small_y, `pm:enable openy_demo_nsessions` fails (exit 1)
#    because openy_demo_ncamp pulls config openy_demo_node_camp_news, which needs
#    openy_node_news. Enable openy_node_news FIRST, on its own, then nsessions:
ddev drush pm:enable openy_node_news -y
ddev drush pm:enable openy_demo_nsessions -y

# 9. Migrate the demo sessions (35 nodes). If a prior run left a stale lock
#    ("migration ... is busy"), reset it first.
ddev drush migrate:reset-status openy_demo_node_branch
ddev drush migrate:import openy_demo_node_session_01 --execute-dependencies -y

# 10. Index + verify (count > 0).
ddev drush search-api:index default -y
ddev exec "curl -s 'http://localhost/af/get-data?backend%5B%5D=solr'"
```

Then select the Solr backend: global default at
`/admin/openy/settings/activity-finder` (Backend → Solr) or per-block in the AF4
block form. Existing sites are switched automatically by the main module's
`hook_update_N`.

## Notes

- The backend response shape is the shared contract documented in
  [`../../fixtures/schema/runProgramSearch.schema.json`](../../fixtures/schema/runProgramSearch.schema.json);
  the Solr backend is its reference implementation.
- Removing this submodule leaves the main module on Mock (or any other enabled
  backend) — Activity Finder keeps working without Solr.
