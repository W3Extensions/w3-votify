=== Upvote/Downvote Plugin -- W3 Votify ===
Contributors: bookbinder
Tags: social bookmarking, ajax, voting, points, rating, trending, upvote, downvote
Requires at least: 4.7
Tested up to: 5.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Rank pages based on what's trending by allowing users to upvote/downvote posts and comments on your site.

== Description ==

Add Reddit-like voting functionality to WordPress posts and comments.

Order comments/pages based on what's trending or use standard criteria like title, post date, etc. You can also order pages based on their page rating (the percentage of upvotes out of total votes cast).

W3 Votify works perfectly with with cached web pages (so pages don't need to be regenerated/refreshed whenever a new vote is cast).

You can modify your theme to use Votify with your comments or you can use W3 Ajax Comments (with Votify support built in).

##Related Plugins

###W3 Directory Builder
Add W3 Directory Builder to create a power link/social bookmarking web site.

###W3 Ajax Comments
Add W3 Ajax Comments to enable Disqus-like comment functionality (edit and create threaded/nested comments without reloading the page).

== Installation ==

Add buttons to WordPress template(s)


###Function
w3vx_vote_buttons_wrapper($id, $type);

###Parameters

**$id**
comment/post ID


**$type**
"post" or "comment"