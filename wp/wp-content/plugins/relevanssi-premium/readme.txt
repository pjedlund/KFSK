=== Relevanssi Premium - A Better Search ===
Contributors: msaari
Donate link: http://www.relevanssi.com/
Tags: search, relevance, better search
Requires at least: 2.7
Tested up to: 3.3.1
Stable tag: 1.7.7

Relevanssi Premium replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features
and configurable options. You'll get better results, better presentation of results - your users
will thank you.

= Key features =
* Search results sorted in the order of relevance, not by date.
* Fuzzy matching: match partial words, if complete words don't match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Search for phrases with quotes, for example "search phrase".
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the documents when user clicks through search results.
* Search comments, tags, categories and custom fields.

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Automatic support for [WPML multi-language plugin](http://wpml.org/)
* Advanced filtering to help hacking the search results the way you want.

= Premium features (only in Relevanssi Premium) =
* Search result throttling to improve performance on large databases.
* Improved spelling correction in "Did you mean?" suggestions.
* WordPress Multisite support.
* Indexing and searching user profiles.
* Weights for post types, including custom post types.
* Limit searches with custom fields.
* Index internal links for the target document (sort of what Google does).
* Search using multiple taxonomies at the same time.

Relevanssi is available in two versions, regular and Premium. Regular Relevanssi is and will remain
free to download and use. Relevanssi Premium comes with a cost, but will get all the new features.
Standard Relevanssi will be updated to fix bugs, but new features will mostly appear in Premium.
Also, support for standard Relevanssi depends very much on my mood and available time. Premium
pricing includes support.

= Relevanssi in Facebook =
You can find [Relevanssi in Facebook](http://www.facebook.com/relevanssi).
Become a fan to follow the development of the plugin, I'll post updates on bugs, new features and
new versions to the Facebook page.

Relevanssi owes a lot to [wpSearch](http://wordpress.org/extend/plugins/wpsearch/) by Kenny
Katzgrau.

== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new
version and if the new version has changes in the indexing, rebuild the index.

= Changes to templates =
None necessary! Relevanssi uses the standard search form and doesn't usually need any changes in
the search results template.

= How to index =
Check the options to make sure they're to your liking, then click "Save indexing options and
build the index". If everything's fine, you'll see the Relevanssi options screen again with a 
message "Indexing successful!"

If something fails, usually the result is a blank screen. The most common problem is a timeout:
server ran out of time while indexing. The solution to that is simple: just return to Relevanssi
screen (do not just try to reload the blank page) and click "Continue indexing". Indexing will
continue. Most databases will get indexed in just few clicks of "Continue indexing". You can
follow the process in the "State of the Index": if the amount of documents is growing, the 
indexing is moving along.

If the indexing gets stuck, something's wrong. I've had trouble with some plugins, for example
Flowplayer video player stopped indexing. I had to disable the plugin, index and then activate
the plugin again. Try disabling plugins, especially those that use shortcodes, to see if that
helps. Relevanssi shows the highest post ID in the index - start troubleshooting from the post
or page with the next highest ID. Server error logs may be useful, too.

= Using custom search results =
If you want to use the custom search results, make sure your search results template uses `the_excerpt()`
to display the entries, because the plugin creates the custom snippet by replacing the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some
problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can
use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()`
filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

See Frequently Asked Questions for more instructions on what you can do with
Relevanssi.

= Uninstalling =
To uninstall the plugin, first click the "Remove plugin data" button on the plugin settins page
to remove options and database tables, then remove the plugin using the normal WordPress
plugin management tools.

= Combining with other plugins =
Relevanssi doesn't work with plugins that rely on standard WP search. Those plugins want to
access the MySQL queries, for example. That won't do with Relevanssi. [Search Light](http://wordpress.org/extend/plugins/search-light/),
for example, won't work with Relevanssi.

[ThreeWP Ajax Search](http://wordpress.org/extend/plugins/threewp-ajax-search/) is
an AJAX instant search plugin that works with Relevanssi.

Some plugins cause problems when indexing documents. These are generally plugins that use shortcodes
to do something somewhat complicated. One such plugin is [MapPress Easy Google Maps](http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/).
When indexing, you'll get a white screen. To fix the problem, disable either the offending plugin 
or shortcode expansion in Relevanssi while indexing. After indexing, you can activate the plugin
again.

== Frequently Asked Questions ==

= Where is the Relevanssi search box widget? =
There is no Relevanssi search box widget.

Just use the standard search box.

= Where are the user search logs? =
See the top of the admin menu. There's 'User searches'. There. If the logs are empty, please note
showing the results needs at least MySQL 5.

= Displaying the number of search results found =

The typical solution to showing the number of search results found does not work with Relevanssi.
However, there's a solution that's much easier: the number of search results is stored in a
variable within $wp_query. Just add the following code to your search results template:

`<?php echo 'Relevanssi found ' . $wp_query->found_posts . ' hits'; ?>`

= Advanced search result filtering =

If you want to add extra filters to the search results, you can add them using a hook.
Relevanssi searches for results in the _relevanssi table, where terms and post_ids are listed.
The various filtering methods work by listing either allowed or forbidden post ids in the
query WHERE clause. Using the `relevanssi_where` hook you can add your own restrictions to
the WHERE clause.

These restrictions must be in the general format of 
` AND doc IN (' . {a list of post ids, which could be a subquery} . ')`

For more details, see where the filter is applied in the `relevanssi_search()` function. This
is stricly an advanced hacker option for those people who're used to using filters and MySQL
WHERE clauses and it is possible to break the search results completely by doing something wrong
here.

There's another filter hook, `relevanssi_hits_filter`, which lets you modify the hits directly.
The filter passes an array, where index 0 gives the list of hits in the form of an array of 
post objects and index 1 has the search query as a string. The filter expects you to return an
array containing the array of post objects in index 0 (`return array($your_processed_hit_array)`).

= Direct access to query engine =
Relevanssi can't be used in any situation, because it checks the presence of search with
the `is_search()` function. This causes some unfortunate limitations and reduces the general usability
of the plugin.

You can now access the query engine directly. There's a new function `relevanssi_do_query()`,
which can be used to do search queries just about anywhere. The function takes a WP_Query object
as a parameter, so you need to store all the search parameters in the object (for example, put the
search terms in `$your_query_object->query_vars['s']`). Then just pass the WP_Query object to
Relevanssi with `relevanssi_do_query($your_wp_query_object);`.

Relevanssi will process the query and insert the found posts as `$your_query_object->posts`. The
query object is passed as reference and modified directly, so there's no return value. The posts
array will contain all results that are found.

= Sorting search results =
If you want something else than relevancy ranking, you can use orderby and order parameters. Orderby
accepts $post variable attributes and order can be "asc" or "desc". The most relevant attributes
here are most likely "post_date" and "comment_count".

If you want to give your users the ability to sort search results by date, you can just add a link
to http://www.yourblogdomain.com/?s=search-term&orderby=post_date&order=desc to your search result
page.

Order by relevance is either orderby=relevance or no orderby parameter at all.

= Filtering results by date =
You can specify date limits on searches with `by_date` search parameter. You can use it your
search result page like this: http://www.yourblogdomain.com/?s=search-term&by_date=1d to offer
your visitor the ability to restrict their search to certain time limit (see
[RAPLIQ](http://www.rapliq.org/) for a working example).

The date range is always back from the current date and time. Possible units are hour (h), day (d),
week (w), month (m) and year (y). So, to see only posts from past week, you could use by_date=7d
or by_date=1w.

Using wrong letters for units or impossible date ranges will lead to either defaulting to date
or no results at all, depending on case.

Thanks to Charles St-Pierre for the idea.

= Caching =
Relevanssi has an included cache feature that'll store search results and
post excerpts in the database for reuse. It's something of an experimental 
feature right now, but should work and if there are lots of repeat queries,
it'll give some actual boost in performance.

= Displaying the relevance score =
Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add
something like

`echo $post->relevance_score`

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
To use Google-style "did you mean?" suggestions, first enable search query logging. The
suggestions are based on logged queries, so without good base of logged queries, the
suggestions will be odd and not very useful.

To use the suggestions, add the following line to your search result template, preferably
before the have_posts() check:

`<?php if (function_exists('relevanssi_didyoumean')) { relevanssi_didyoumean(get_search_query(), "<p>Did you mean: ", "?</p>", 5); }?>`

The first parameter passes the search term, the second is the text before the result,
the third is the text after the result and the number is the amount of search results
necessary to not show suggestions. With the default value of 5, suggestions are not
shown if the search returns more than 5 hits.

= Search shortcode =
Relevanssi also adds a shortcode to help making links to search results. That way users
can easily find more information about a given subject from your blog. The syntax is
simple:

`[search]John Doe[/search]`

This will make the text John Doe a link to search results for John Doe. In case you
want to link to some other search term than the anchor text (necessary in languages
like Finnish), you can use:

`[search term="John Doe"]Mr. John Doe[/search]`

Now the search will be for John Doe, but the anchor says Mr. John Doe.

One more parameter: setting `[search phrase="on"]` will wrap the search term in
quotation marks, making it a phrase. This can be useful in some cases.

= Restricting searches to categories and tags =
Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or
tags, since those are pretty much the same). Just add a hidden input field named `cat` in your
search form and list the desired category or tag IDs in the `value` field - positive numbers
include those categories and tags, negative numbers exclude them.

This input field can only take one category or tag id (a restriction caused by WordPress, not
Relevanssi). If you need more, use `cats` and use a comma-separated list of category IDs.

You can also set the restriction from general plugin settings (and then override it in individual
search forms with the special field). This works with custom taxonomies as well, just replace `cat`
with the name of your taxonomy.

If you want to restrict the search to categories using a dropdown box on the search form, use
a code like this:

`<form method="get" action="<?php bloginfo('url'); ?>">
	<div><label class="screen-reader-text" for="s">Search</label>
	<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" />
<?php
	wp_dropdown_categories(array('show_option_all' => 'All categories'));
?>
	<input type="submit" id="searchsubmit" value="Search" />
	</div>
</form>`

This produces a search form with a dropdown box for categories. Do note that this code won't work
when placed in a Text widget: either place it directly in the template or use a PHP widget plugin
to get a widget that can execute PHP code.

= Restricting searches with taxonomies =

You can use taxonomies to restrict search results to posts and pages tagged with a certain 
taxonomy term. If you have a custom taxonomy of "People" and want to search entries tagged
"John" in this taxonomy, just use `?s=keyword&people=John` in the URL. You should be able to use
an input field in the search form to do this, as well - just name the input field with the name
of the taxonomy you want to use.

It's also possible to do a dropdown for custom taxonomies, using the same function. Just adjust
the arguments like this:

`wp_dropdown_categories(array('show_option_all' => 'All people', 'name' => 'people', 'taxonomy' => 'people'));`

This would do a dropdown box for the "People" taxonomy. The 'name' must be the keyword used in
the URL, while 'taxonomy' has the name of the taxonomy.

= Automatic indexing =
Relevanssi indexes changes in documents as soon as they happen. However, changes in shortcoded
content won't be registered automatically. If you use lots of shortcodes and dynamic content, you
may want to add extra indexing. Here's how to do it:

`if (!wp_next_scheduled('relevanssi_build_index')) {
	wp_schedule_event( time(), 'daily', 'relevanssi_build_index' );
}`

Add the code above in your theme functions.php file so it gets executed. This will cause
WordPress to build the index once a day. This is an untested and unsupported feature that may
cause trouble and corrupt index if your database is large, so use at your own risk. This was
presented at [forum](http://wordpress.org/support/topic/plugin-relevanssi-a-better-search-relevanssi-chron-indexing?replies=2).

= Highlighting terms =
Relevanssi search term highlighting can be used outside search results. You can access the search
term highlighting function directly. This can be used for example to highlight search terms in
structured search result data that comes from custom fields and isn't normally highlighted by
Relevanssi.

Just pass the content you want highlighted through `relevanssi_highlight_terms()` function. The
content to highlight is the first parameter, the search query the second. The content with
highlights is then returned by the function. Use it like this:

`if (function_exists('relevanssi_highlight_terms')) {
    echo relevanssi_highlight_terms($content, get_search_query());
}
else { echo $content; }`

= Multisite searching =
To search multiple blogs in the same WordPress network, use the `searchblogs` argument. You can
add a hidden input field, for example. List the desired blog ids as the value. For example, 
searchblogs=1,2,3 would search blogs 1, 2, and 3. 

The features are very limited in the multiblog search, none of the advanced filtering works, and
there'll probably be fairly serious performance issues if searching common words from multiple
blogs.

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency*
while idf is *inverted document frequency*. Term frequency is simply the number of times the term
appears in a document, while document frequency is the number of documents in the database where
the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and
the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about
every document are completely useless for information retrieval purposes. Basically, their
inverted document frequency is really low, so they never have much power in matching. Also,
removing those words helps to make the index smaller and searching faster.

== Known issues and To-do's ==
* Known issue: The most common cause of blank screens when indexing is the lack of the mbstring extension. Make sure it's installed.
* Known issue: In general, multiple Loops on the search page may cause surprising results. Please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* Known issue: Custom post types and private posts is problematic - I'm using default 'read_private_*s' capability, which might not always work.
* Known issue: There are reported problems with custom posts combined with custom taxonomies, the taxonomy restriction doesn't necessarily work.
* Known issue: User searches page requires MySQL 5.

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape.
* Mohib Ebrahim for relentless bug hunting.
* John Blackbourn for amazing internal link feature and other fixes.

== Changelog ==

= 1.7.7 =
* Fixed a major bug that can make indexing fail when the user has manually chosen to hide posts from the index.
* Removed default values from text columns in the database.
* Relevanssi will now index pending and future posts. These posts are only shown in the admin search.
* Using multiple taxonomies in search will now use OR logic between term within the same taxonomy and AND logic between different taxonomies. Thanks to Jonathan Liuti.
* Added a shortcode `noindex` that can be used to prevent parts of posts being indexed. In order to use the shortcode, you must enable expanding shortcodes in indexing.

= 1.7.6 =
* New filter `relevanssi_results` added. This filter will process an array with (post->ID => document weight) pairs.
* Fixed a mistake in the FAQ: correct post date parameter is `post_date`, not `date`.
* When continuing indexing, Relevanssi now tells if there's more to index. (Thanks to mrose17.)
* Private and draft posts were deleted from the index when they were edited. This bug has been fixed. (Thanks to comprock.)
* Improved WPML support.
* The `relevanssi_index_doc()` function has a new parameter that allows you to bypass global $post and force the function to index the document given as a parameter (see 1.7.6 release notes at Relevanssi.com for more details).

= 1.7.5 =
* Drafts are now indexed and shown in the admin search.
* A first test version of English stemmer (or suffix stripper) is available. Enable it with `add_filter('relevanssi_stemmer', 'relevanssi_simple_english_stemmer');`.

= 1.7.4 =
* Fixed a bug related that caused AND queries containing short search terms to fall back to OR searches.
* The 'relevanssi_match' filter now gets the IDF as an additional parameter to make recalculating weight easier.
* Added a very nice related searches feature by John Blackbourn.

= 1.7.3 =
* Cache truncation was never actually scheduled.
* Index wasn't updated properly when post status was switched from public to private.
* Made the relevanssi_hide_post custom field invisible.
* Added an option to hide the Relevanssi post controls metabox on edit pages.
* Fixed a bug that prevents search hit highlighting in multiple blog searches.
* Added support for 'order' and 'orderby' in multiple blog searches.
* Added nonces to various forms to improve plugin security.
* Added support for 'author' query variable.
* Added support for searches without a search term.

= 1.7.2 =
* Fixed another bug that was causing error notices.

= 1.7.1 =
* Fixed a bug that caused errors when indexing, if MySQL column setting was empty.

= 1.7 =
* Relevanssi now stores more data about custom fields and custom taxonomies, allowing more fine-tuned control of results.
* There was a bug in custom field indexing that caused all custom field terms get a term frequency of 1.
* There was a bug in custom taxonomy indexing, effects of which are uncertain. Probably nothing major.
* The 'tag' (and 'tags') query variable now accepts tag names as well as tag IDs. For category names, you can use 'category_name'.
* Relevanssi can now index user-specified MySQL columns from the wp_posts table.
* It's now possible to adjust weights for all taxonomies, not just categories and tags.
* It's now possible to give a weight bonus for recent posts.

= 1.6.2.1 =
* Fixed a nasty bug that prevented indexing the database. If you installed 1.6.2 and ran into the problem, update and check the correct post types to index.

= 1.6.2 =
* Somebody had problems with the log table ID field going over MEDIUMINT limit. I changed the ID field to BIGINT.
* There were serious problems with custom post type names that include 'e_' in them. That's now fixed.

= 1.6.1 =
* Fixed small bugs in the Did you mean -feature. (Thanks to John Blackbourn.)
* Fixed the tf*idf weighing a bit in order to increase the effect of the idf component. This should improve results of OR searches in particular by giving more weight to rarer terms.
* Fixed the WPML filter when working with multisite environment. (Thanks to Richard Vencu.)
* Fixed (for real) a bug that created bad suggestion URLs with WPML. (Thanks to John Blackbourn.)
* Fixed s2member support for s2member versions 110912 and above. (Thanks to Jason Caldwell.)

= 1.6 =
* Fixed a bug that removed 'à' from search terms.
* Fixed error notices about undefined $wpdb.
* Fixed errors about deprecated ereg_replace.
* Old post type indexing settings are now imported.
* Fixed uninstall to better clean up after Relevanssi is uninstalled.
* Fixed a bug that created bad suggestion URLs with WPML. (Thanks to John Blackbourn)
* Improved s2member support.
* Removed error notices that popped up when quick editing a post.
* Relevanssi can now index drafts for admin search.
* New filter `relevanssi_show_matches` can be used to modify the text that shows where the hits are made.
* New filter `relevanssi_user_index_ok` lets you control which users are indexed and which are not.

= 1.5.13.beta =
* Support for s2member membership plugin. Search won't show posts that the current user isn't allowed to see.
* New filter `relevanssi_post_ok` can be used to add support for other membership plugins.
* Better way to choose which post types are indexed.
* Post meta fields that contain arrays are now indexed properly, expanding all the arrays.

= 1.5.12.beta =
* If a custom field limitation is set and no matches are found, no results are returned.
* New filter `relevanssi_fuzzy_query`. This can be used to change the way fuzzy matches are made.
* There's a meta box on post and page edit pages that you can use to exclude posts and pages from search.
* User profiles couldn't be found, unless "respect exclude_from_search" was disabled. I've fixed that.
* OR fallback search had a bug. Fixed that.
* Custom field searches support phrases. Thanks to davidn.de.
* Fixed a bug that caused problems when paging search results.
* `get_the_excerpt` filters weren't triggered on excerpt creation. `the_excerpt` is not used, as it will add unnecessary HTML code to the excerpts.

= 1.5.11.beta =
* New filter `relevanssi_do_not_index`. Filter is passed a post id and if it returns `true`, the post will not be indexed.
* New query variable: use `tag` or `tags` to filter results by tag. Both take comma-separated lists of tag ids (not tag slugs or names) and filter results by them (it's an OR, not AND operation).
* New filter `relevanssi_ellipsis`. Use this if you want to change the '...' appended to excerpts.
* Relevanssi-created excerpts are now passed through `the_excerpt` and `get_the_excerpt` filters.
* Attachments (with post status inherit) couldn't be found in search. Now they can.
* Amount of SQL queries made in indexing has been reduced a lot. Less memory should be required. I'd appreciate any reports of changes in the database re-indexing performance.

= 1.5.10.beta =
* Removed some unnecessary filter calls.
* the_content filters didn't have any effect on excerpts, now they work as they should.
* Taxonomy term search didn't work properly.
* I've moved the "strip invisibles" function after shortcode expansion in indexing and excerpt creation, so objects, embeds and styles created by shortcodes are stripped. Let me know if this causes any problems.
* Multibyte string functions are not required anymore, Relevanssi will work without, but will cause problems if you try to handle multibyte strings without multibyte functions. (Thanks to John Blackbourn.)
* Couple of functions Relevanssi uses weren't namespaced properly. They are now. (Thanks to John Blackbourn.)
* When $post is being indexed, `$post->indexing_content` is set to `true`. This can be useful for plugin developers and all sorts of custom hacks. (Thanks to John Blackbourn.)
* User search log now displays the total number of searches. (Thanks to Simon Blackbourn.)
* Database now has an index on document ID, which should make indexing faster.
* If you upgrade from 1.5.8 or earlier, emptying the database manually is not necessary.
* The plugin can now be upgraded automatically. The required API key can be found on Relevanssi.com in the sidebar after you log in.

= 1.5.9 =
* Fixed a MySQL error that was triggered by a media upload.
* Minimum word length to index wasn't enforced properly.
* Fixed a bug that caused an error when quick editing a post.
* Improved the handling of punctuation.
* Added an indexing option to manage thousands separators and large numbers better.
* The database is changed. The change requires reindexing and emptying the database before activating the plugin. Either truncate the database from phpMyAdmin or similar tool or use the "Delete plugin options" (but remember to back up your options and stopwords first!).
* Adjusted the default throttle to 300 posts from 500 posts.

= 1.5.8 =
* Added a new hook `relevanssi_excerpt_content`; see [Knowledge Base](http://www.relevanssi.com/category/knowledge-base/) for details.
* Improved the indexing procedure to prevent MySQL errors from appearing and to streamline the process.

= 1.5.7 =
* 1.5.6 was broken, this is a quick fix release.

= 1.5.6 =
* Added default values to the database columns, this could cause some problems.
* Indexing could cause problems, because Relevanssi changed the contents of global $post. That's fixed now.
* There's an option to choose the default order of search results, by relevance or by date.
* Indexing settings have a new option to only index certain post types.

= 1.5.5 =
* Added two new filters: `relevanssi_index_titles` and `relevanssi_index_content`. Add a function that returns `false` to the filters to disable indexing titles and post content respectively.
* Google Adsense caused double hits to the user search logs. That's now fixed thanks to Justin Klein.

= 1.5.4 =
* It's now possible to remove matches from the results with the external filter `relevanssi_match`. If the weight is set to 0, the match will be removed.
* Multisite installations had problems - installing plugin on a single site in network didn't work. John Blackbourn found and fixed the bug, thanks!

= 1.5.3 =
* User search log is available to user with `edit_post` capabilities (editor role). There's also an option to remove Relevanssi branding from the user search logs. Thanks to John Blackbourn.
* A proper database collation is now set. Thanks to John Blackbourn.
* UI looks better. Thanks to John Blackbourn.
* Small fixes: spelling corrector uses now correct multibyte string operators, unnecessary taxonomy queries are prevented. Thanks to John Blackbourn.
* You can now export and import settings. Thanks to ThreeWP Ajax Search for showing me a good (easy) way to do this.

= 1.5.2 =
* A German translation is included, thanks to David Decker.
* A get_term() call was missing a second parameter and throwing errors occasionally. Fixed that.
* Fixed a bug that caused Cyrillic searches in the log to get corrupted.
* Punctuation removal filter was actually missing from the code. Oops. Fixed that now.

= 1.5.1 =
* The result caching system didn't work properly. It works now.
* Limiting results with custom field key and value didn't work properly: it matched the value to the whole field. Now it matches the value to any part of the custom field. That should make more sense. 

= 1.5 =
* Taxonomy pages (tags, categories, custom taxonomies) can now be indexed and searched.
* Short search terms don't crash the search anymore.
* There are fixes to the user search as well, including a new option to index additional fields.
* Relevanssi now uses search result caching system that greatly reduces the number of database calls made.
* Punctuation removal function is now triggered with a filter call and can thus be replaced.

= 1.4.5 =
* New filter: `relevanssi_match` allows you to weight search results.
* Similar to `cats` vs `cat`, you can now use `post_types` to restrict the search to multiple post types.
* Multisite search supports post type restriction.

= 1.4.4 =
* Changed the way search results are paginated. This makes adjusting the number of search results shown much easier.

= 1.4.3 =
* Fixed the Did you mean -feature.
* WordPress didn't support searching for multiple categories with the `cat` query variable. There's now new `cats` which can take multiple categories.

= 1.4.2 =
* Multisite search had bugs. It's working now.
* Stopwords are not highlighted anymore. Now this feature actually works.

= 1.4.1 =
* Textdomain was incorrect.
* The new database structure broke the throttle and the spelling correction. These are now fixed.

= 1.4 =
* New database structure, which probably reduces the database size and makes clever stuff possible.
* The throttle option had no effect, throttle was always enabled. Now the option works. You can now also either replace the throttle function with your own (through 'relevanssi_query_filter') or modify it if necessary ('relevanssi_throttle').
* Highlights didn't work properly with non-ASCII alphabets. Now there's an option to make them work.
* Title highlight option now affects external search term highlights as well.
* Stopwords are not highlighted anymore.
* Fixed a small mistake that caused error notices.
* Custom post types, particularly those created by More Types plugin, were causing problems.

= 1.3.2 =
* Expired cache data is now automatically removed from the database every day. There's also an option to clear the caches.
* A nasty database bug has been fixed (thanks to John Blackbourn for spotting this).
* Fixed bugs on option page.

= 1.3.1 =
* Fixed the multiple taxonomy search logic to AND instead of OR.
* Some small security fixes.

= 1.3 =
* Bug fix: when choosing mark highlighting, the option screen showed wrong option.
* Category restrictions now include subcategories as well to mirror WordPress default behaviour.
* Internal links can be now indexed for the source, target or both source and target.
* It's now possible to limit searches by custom fields.
* It's now possible to use more than one taxonomy at the same time.

= 1.2 =
* Relevanssi can now highlight search terms from incoming queries.
* Spelling correction in Did you mean searches didn't work.
* Some shortcode plugins (Catablog, for example) were having trouble; fixed that.

= 1.1.2 =
* The plugin didn't update databases correctly, causing problems.

= 1.1.1 =
* Very small fix that improves plugin compatibility with Relevanssi when using shortcodes.

= 1.1 =
* Multisite WordPress support. See FAQ for instructions on how to search multiple blogs.
* Improved the fallback to fuzzy search if no hits are found with regular search.
* AND searches sometimes failed to work properly, causing unnecessary fallback to OR search. Fixed.
* When using WPML, it's now possible to choose if the searches are limited to current language.
* Adding stopwords from the list of 25 common words didn't work. It works now.
* The instructions to add a category dropdown to search form weren't quite correct. They are now.
* It's now possible to assign weights for post types.
* User profiles can be indexed and searched.

= 1.0 =
* First published version, matches Relevanssi 2.7.3.