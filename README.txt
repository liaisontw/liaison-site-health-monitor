
=== liaison site health monitor ===
Contributors: Liaison
Tags: performance, site-health, database, slow-query, profiling
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Developer-focused WordPress diagnostic plugin using WordPress core SAVEQUERIES for site health metrics and slow query profiling.



== Description ==

**liaison site health monitor** is an admin-only diagnostic tool built for developers and operators who want **clear visibility into WordPress performance characteristics** without introducing undocumented runtime behavior.

This plugin intentionally **requires `SAVEQUERIES` to be enabled in `wp-config.php`**, and does not attempt to bypass or reimplement WordPress core database instrumentation.

 Why this matters

Rather than intercepting or mutating the database layer, this plugin:

* Respects WordPress’s established debugging contract
* Uses **core-supported query timing data**
* Makes performance trade-offs **explicit and auditable**
* Avoids fragile hooks into internal execution paths



== Requirements ==

To enable slow query monitoring, the following **must** be set in `wp-config.php`:

define( 'SAVEQUERIES', true );

This constant must be defined **before WordPress bootstrap**, as required by core.

If `SAVEQUERIES` is not enabled, slow query monitoring will be automatically disabled and the plugin will operate in **site health metrics–only mode**.



== Features ==

 1. Site Health Metrics

* PHP memory usage
* Aggregate database query time
* REST API response latency
* Active plugin count
* WordPress version

 2. Slow Query Monitoring (SAVEQUERIES-based)

* Uses `$wpdb->queries` as the authoritative source
* Automatic slow query detection via time thresholds
* Query normalization and aggregation
* Persistent storage for historical analysis
* Zero interference with query execution



 Slow Query Monitoring

1. WordPress records query timing into `$wpdb->queries` (core behavior).
2. On admin page load:

   * Plugin inspects recorded queries
   * Filters queries exceeding the configured threshold
3. Slow queries are:

   * Normalized (values stripped)
   * Stored in a plugin-owned table
4. Aggregated results are displayed in the dashboard.



 === Why SAVEQUERIES Is Required ===

WordPress deliberately gates query timing behind `SAVEQUERIES` to ensure developers **explicitly opt into performance overhead**.

This plugin embraces that design decision:

* No attempt to override `$wpdb`
* No undocumented filters or runtime mutation
* No silent performance impact

Instead, it provides:

* Transparent prerequisites
* Predictable behavior
* Alignment with WordPress core debugging philosophy

This makes the plugin suitable for **development, staging, and controlled production diagnostics**.



 === Security Considerations ===

* **Admin-only access**

  * Dashboard restricted to privileged users.
* **No sensitive data exposure**

  * Query values are normalized before storage.
* **No runtime mutation**

  * Does not alter database execution flow.
* **Clear uninstall behavior**

  * Plugin-owned tables removed on uninstall.



 === Trade-offs / Limitations ===

 Pros

* Uses WordPress-supported debugging mechanisms
* Easy to reason about and audit
* No fragile DB-layer interception
* Clear performance cost model
* Ideal for learning and diagnosis

 Cons

* Requires `SAVEQUERIES` to be enabled
* Higher overhead than production-safe profilers
* Not intended for always-on monitoring
* Less suitable for high-traffic production sites



 === Installation ===

1. Add the following to `wp-config.php`:

   define( 'SAVEQUERIES', true );

2. Upload the plugin to `wp-content/plugins/liaison-site-health-monitor`.

3. Activate via WordPress admin.

4. Navigate to:
   **Tools → Site Health**

5. Review site metrics and slow query data.



 === Testing ===

* Tested with `SAVEQUERIES` enabled and disabled.
* Verifies graceful degradation when unavailable.
* Manual validation of query timing accuracy.
* No dependency on non-core WordPress behavior.

== Screenshots ==

1. **Site Health Dashboard**

Overview of WordPress runtime, database, cache, and performance metrics, including slow queries detected through the core `SAVEQUERIES` mechanism.

2. **Slow DB Queries (Top 10)**

Detailed view of the slowest database queries captured by `SAVEQUERIES`, including execution time, SQL pattern, request URI, timestamp, index status, and call stack for root-cause analysis.

 === Changelog ===

 = 1.0.0 =

* Provides site health metrics and database slow query profiling
* SAVEQUERIES-based slow query analysis
* Introduced query normalization & aggregation
* Added explicit requirement documentation
* Refined admin dashboard
* Clarified architectural trade-offs

