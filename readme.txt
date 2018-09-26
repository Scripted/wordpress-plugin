=== Scripted.com Writer Marketplace ===
Contributors: stevenmaguire
Donate link:
Tags: writing, blog posts, twitter, tweet, hire blogger, hire writer, custom content, scripted.com, expert writer, scripted, freelance writer
Requires at least: 3.3
Requires PHP: >=5.5
Tested up to: 4.9.8
Stable tag: 3.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get your blog posts written by our pre-screened network of top-notch writers. Scripted makes it easy to order consistently great blog posts that you own 100% of the rights to publish on your WordPress website and blog.

== Description ==

Founded in 2011, Scripted.com is a leading marketplace for original, high-quality writing and blogging for companies ranging from small businesses to large enterprises. Scripted receives a new writer application every 20 minutes but vets writers across multiple criteria before they are admitted into the marketplace, and its writer acceptance rate is 5% ensuring the highest quality content. Scripted is a venture-backed (Redpoint Ventures, Crosslink Capital), San Francisco-based company.

== Installation ==

1. Upload the `scripted-api` folder to the `/wp-content/plugins/` directory .
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Create an account at Scripted.com by going here: https://www.scripted.com/business/register
4. View and copy your organization key and access token here: https://www.scripted.com/business/account/api
5. Go to your Scripted settings (see screenshot) and enter it in the Scripted settings page.

== Frequently asked questions ==

See how Scripted works here: [http://scripted.com/how-it-works/](https://www.scripted.com/content-writing-service)

== Screenshots ==

1. Scripted API settings
2. View Current Jobs
3. View Finished Jobs and Create Posts

== Changelog ==


== Upgrade notice ==

- Upgrading to version 3.x from 2.x of this plugin will require that you re-enter your Scripted.com org key and access token.
- This plugin makes use of [version 3.67.7 of the aws-sdk-php library](https://github.com/aws/aws-sdk-php/tree/3.67.7). The library is included as a phar file that is approximately 11 mb in size and occupies the following root namespaces: `Aws`, `GuzzleHttp`, `JmesPath`, 'Psr'.
- This plugin makes use of [version 1.35.4 of the twig library](https://github.com/twigphp/Twig/tree/v1.35.4). The library is included as a phar file that is approximately 1 mb in size and occupies the following root namespaces: `Twig_`, 'Symfony'.
