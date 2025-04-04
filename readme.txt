# About the plugin

Taxonomy Based Passwords lets you restrict access to content, by just requiring a single password for a group of tagged content.

It is like the password protected post feature built into WordPress. Where Taxonomy Based Passwords improves on this, is that once logged in, you can freely move between other pieces of protected content.

This is great for semi-private content, where you don't want it to be openly public, but you don't want to place a big hurdle for your users to gain access either. 

Taxonomy Based Passwords gives you a single shared password for the groups of content, and can be applied to the hub page, and custom post types.

# Real world example

Imagine you are a trucking company that wants to give access to content specifically for its truckers.

Lets say you want to group them by specialism, like long-haul and short-haul. 

Your staff aren't interested in complicated sign ups, or remembering what username and password they used six months ago.

Use Taxononmy Based Passwords to make an access group, then add two groups; one for each of Long Haul / Short Haul.

Everyone in the short haul group gets one simple password, everyone in the long haul gets their own single password.

Now you can set up a hub page for each of these access groups, tag the pages to protect them, and put content onto that page using your favourite page builder.

Need more structure? You can make custom post types using your normal, favourite tools, like "Custom Post Type UI", JetEngine, etc. 

Then protect the individual posts by adding each one to one of your two Truckers access groups.

This could be a private blog called Trucker News. Put a widget into your hub page to display a carousel of your recent posts from that custom post type, using normal WordPress development tooling.

Clicking through to the individual posts is seamless, as they are already logged in. If not, and somebody has shared a direct link with them, they just get asked for the password once, and can then freely move around the content tagged by their access group.

Link that carousel through to the archive page of the Trucker News custom post type, and the archives list is automatically filtered to the posts tagged by their logged-in access group.

Make more custom post types as needed. Add one for technical articles. Make one for training videos, and embed YouTube videos into the posts. Make one for Trucker Of The Month. It can be crafted to your specification.

# Set up steps

There are three sections for this plugin:

1. Setting up the core plugin
1. Setting up hub pages, if you want to use them
1. Setting up custom post types, if you want to use them

## Core setup

During the core set up you configure the log in page, review the settings, and set up your first access group password.

1. Install plugin
1. Activate
1. Add a new page for the login page
1. Add the [runthings_taxonomy_login_form] login shortcode to the page
1. Optionally, go to Settings > Taxonomies, and change the access group name
1. Go to the taxonomy under pages (default access group)
1. Add a new group, and set the password (remember the password as it will be securely stored; you won't be able to view it again)
1. Add the [runthings_taxonomy_logout] logout shortcode to your template somewhere, for example, in the footer

## Hub pages set up

Hub pages are normal WordPress pages. You can set a main hub, and then protect each page underneath it by assigning an access group to it. 

This gives you a central location for your protected content. 

You can use your favourite page builder to show the protected content, such as content directly on the page, or by using tools to display collections of the custom post types.

1. Add a new page for the hub page
1. Go to Settings > Taxonomies and assign the hub page setting to this page
1. Add the [runthings_taxonomy_hub_page_list] hub shortcode to the page (optional, see below)
1. Add a page under the hub for that access group
1. Set the access group for that page
1. Visit the page, you will see its protected.

You can skip setting up the hub page list shortcode, if you want to operate your departments in stealth. 

This lets you just share the specific access-group hub page directly with your users, without letting them see what other groups exist in a top level hub page.

# Custom post types set up

The custom post type protection lets you create content under selected cpts, and assign it to an access group.

If the user tries to access an individual content then they are asked to log in.

Once logged in they can freely move between protected content in their access group without having to log back in.

Browsing the archive page of the custom post type automatically filters the post results, so they only see their protected content.

1. Set up a CPT to protect, using a plugin like "Custom Post Type UI", JetEngine, etc
1. Add a second access group
1. Add some test posts under the cpt, and tag them to your access group
1. Set up one post that is tagged to the other access group
1. Visit the archive page for the cpt, while logged out, and you will get redirected
1. Go through the login process via the hub page
1. Visit the archive page again, and you will see that the archive is auto filtered to your logged in access group's posts.


