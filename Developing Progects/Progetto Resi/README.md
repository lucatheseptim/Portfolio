# Returns Management Module — PHP

A web module for managing product returns at points of sale. It is designed as
a section of an existing internal platform: it reuses the host application's
authentication, menu and template system, and adds the pages, queries and
front-end components needed to analyse returns.

## What it does

- **Returns analysis by point of sale** — filtered by product library, by date
  range and by store
- **Aggregated views** — global figures, per-area-manager breakdown, per-location
  breakdown and per-store detail
- **Dynamic forms** — the page composes itself from templates, with placeholders
  filled at render time
- **Multi-select filters** — custom jQuery multi-select component for choosing
  several values at once
- **Calendar input** — date pickers for the "from" and "to" range

## Technology stack

| | |
|---|---|
| Language | PHP |
| Database | MySQL (queries executed through the host platform's connector) |
| Front end | HTML, CSS, JavaScript, jQuery |
| Plugins | custom multi-select, calendar/date-picker plugin |
| Integration | host platform: session authentication, menu builder, template engine |

## Project structure

```
punto_vendita_resi.php          entry page: builds the view from the template,
                                injects menu and language, renders the filters
form_template_resi.html         form template with placeholders ({PHP_SELF},
                                {OrderByVal}, {LibraryVal}, ...)
punto_vendita_resi.js           client-side behaviour: filters, multi-select,
                                date pickers, AJAX requests
style_resi.css                  page styling

get_all_data_resi.php           returns the full data set
get_global_data_resi.php        returns the aggregated totals
get_modulo_resi_capo_area.php   form module: area manager selection
get_modulo_resi_localita.php    form module: location selection
get_modulo_resi_negozio.php     form module: store selection

multiselect_plugin/             third-party multi-select component
plugin_calendario/              third-party calendar component
```

## Design notes

- **Modular endpoints.** Each `get_*_data` script answers a single question, so
  the page can refresh one panel without reloading the whole view. The
  `get_modulo_*` scripts return fragments used to build the filter form
  progressively.
- **Template with placeholders.** The HTML file is not rendered directly: the
  PHP page loads it through the platform's template class and substitutes the
  placeholders, keeping markup and logic separate.
- **Reuse of the host platform.** Authentication, permissions, menu, language
  and database access all come from the surrounding application; the module
  adds only what is specific to returns.

## Notes

- The module depends on the host platform's classes (session authentication,
  menu, template engine, database configurator), which are not included here.
  It is published as a record of the module itself, not as a standalone
  application.
- `configurator.php` (database connection settings) is provided by the host
  platform and is not part of this folder.

## Author

Luca Airoldi
