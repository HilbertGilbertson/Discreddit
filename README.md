![Build Status][build-shield]
[![Contributors][contributors-shield]][contributors-url]
[![MIT License][license-shield]][license-url]

## Table of Contents

* [About Discreddit](#about-discreddit)
  * [Built With](#built-with)
* [Getting Started](#getting-started)
  * [Server Requirements](#server-requirements)
  * [Installation](#installation)
* [Configuration](#configuration)
  * [Creating a Discord Application](#creating-a-discord-application)
    * [Enabling Bot features](#enabling-bot-features)
  * [Creating a Reddit Application](#creating-a-reddit-application)
  * [Configuring Communities](#configuring-communities)
  * [Additional Config](#additional-config)
    * [DB Config](#db-config)
      *  [MySQL](#mysql)
      *  [SQLite](#sqlite)
  * [Requirements](#requirements)
    * [Reddit Requirements](#reddit-requirements)
    * [Discord Requirements](#discord-requirements)
* [API](#api)
* [Actions](#actions)
* [Template Customisation](#template-customisation)
* [License](#license)
* [Contact](#contact)
* [Acknowledgements](#acknowledgements)

## About Discreddit

![Discreddit Start Screen][dcr-screenshot]

Discreddit allows you to authenticate users of your Discord and reddit communities, assuring you that */u/nameofsomeuser* on reddit is the same person as *SomeUser#3816* on Discord. The code has been adapted from another project and released as *Discreddit* on the request of a friend, so that they (and hopefully others) can make use of it in their communities.

**Where does it come from?**

Discreddit originates from the website of the Watch Dogs Discord, also moderators on /r/watch_dogs. We wanted to have a means of identifying shared users of both our communities, and rewarding those who were active in them both. So we set up a simple script to allow for open authentication (OAuth) through both reddit and Discord, which would on completion grant the user a `@redditor` role, and even provide them a bonus boost of our credit currency, on Discord.

**Why not just look at people's Discord profiles? They can link their accounts officially through Discord.**

It's true that users can link their Discord account with reddit officially, so it's possible for you to right click on a user in Discord and potentially verify their reddit username. But most users don't have their reddit account linked via Discord, and even then, not everyone who has makes it public. Crucially, *bots* cannot see a Discord user's linked accounts (which are only visible to them via OAuth). It also doesn't work the other way around (you can't look at a user on *reddit* and verify their Discord identity).

With Discreddit, we can gather the Discord user's reddit username, karma, status on your subreddit (are they muted, banned, subscribed, what's their flair, etc?) and impose conditions.

We could even use Discreddit to, say, set up a Discord community that is open *only* to contributors of your subreddit who have more than *X* karma. Or allow everyone in, but grant your most dedicated reddit users a special role on the Discord.

### Built With
* [Bootstrap](https://getbootstrap.com)
* [JQuery](https://jquery.com)


## Getting Started

This is an example of how you may give instructions on setting up your project locally.
To get a local copy up and running follow these simple example steps.

### Server Requirements

* A webserver supporting PHP 7+ and curl

### Installation

1. Upload the *dist* directory to your webserver and rename it (e.g. to `dcr`).
2. Edit config.php (see configuration below).
3. Make changes to index.php as needed (e.g. adding a link to your terms and conditions), including modifying the 'congratulations' message the user will see upon successful completion of account linking.

## Configuration

There are numerous configuration options so please make sure you read over this section carefully.

### Creating a Discord Application

1. Head to the Discord Developer Portal [here](https://discordapp.com/developers/applications/) and create a new application. For a name, you could choose something like '*MyCommunity* Auth'.
2. You will be redirected to your new application's settings page. On the left hand side, click *OAuth2*.
3. Under *Redirects*, click *Add Redirect*. Take the URL of the Discreddit directory (e.g. `https://mysite.com/dcr/`) and add `?return=discord` on the end.

	The redirect URL should look like: `https://mysite.com/dcr/?return=discord`.
4. Click **Save Changes**

Now that your application has been created and configured for OAuth, we are going to place its credentials into config.php. On our Discord application settings page, click *General Information* on the left hand side. Click to copy the `CLIENT ID` and `CLIENT SECRET`. These must be pasted into their respective fields in config.php.

The config has four key settings under `discord =>` **`oauth`**:

* `client_id` - grab this from the application settings page
* `client_secret` - as with client_id, copy this from the application settings page
* `appname` - set this to match the name of your Discord application e.g. '*MyCommunity Auth*'
* `use_connections` - this can be left as `false`. Only set this to `true` if you've looked through the code and want to make use of the `DisCon()` method.

Also under the *General Information* page, you can upload an icon for your application, which will show on the OAuth Consent screen.

#### Enabling Bot features

The steps under this heading are **not required** as bot features are optional and disabled by default. However, the bot functionality is strongly recommended.

Enabling Bot features would allow your Discreddit installation to (among other things):

* gather information about a user's *membership* of your Discord guild. Enables: use of the `has_role` config setting to allow only users who are members of a specific role on your Discord server to link their reddit account; `onguild_min` setting to require a user to have been a member of your Discord server for *X* number of days before they can link their reddit account.
* use the `force_join` setting to automatically join the user to your Discord guild if they aren't already a member (without this, you can still require that a Discord user is a member of your guild with the `onguild` requirement, but the user would have to manually join your Discord as normal).
* upon successfully linking their Discord and reddit accounts, add a role to the Discord user or set their nickname to their reddit username (with `DiscordMemberAddRole()` and `DiscordMemberChangeNick()`).

#### To enable bot features:

1. Back on your Discord application's settings in the Developer Portal, click *Bot*.
2. Click on *Add Bot* and then *Yes, do it!*
3. You can set the *Public Bot* option to off. The *Requires OAuth2 Code Grant* option will be off by default - **leave** it *off*. You may also optionally upload an avatar for the bot user from this page.
4. Under *TOKEN*, click to copy it.
5. In config.php under `discord => bot`, paste the copied token into the `token` field and set `enabled` to `true`.
6. You must now join your newly created bot to your Discord guild:
	* Place your Discord application's Client ID (the same one you've placed into `client_id` in config.php) onto the *end* of the following URL: `https://discordapp.com/oauth2/authorize?scope=bot&permissions=402653185&client_id=`
	* Visit the URL and select your guild from the dropdown (you may only add a Discord bot to a guild that you have permission to manage).
	* To avoid complications, the preconfigured permissions are best left ticked.
		* 	The *Create Instant Invite* permission is required for Discreddit to be able to force join users onto your guild.
		*  The *Manage Roles* permission is recommended to allow Discreddit to be able to grant a role to a user.
		*  The *Manage Nicknames* permission is finally recommended to allow Discreddit to be able to change a user's nickname
	* Click Authorize and the bot user will join your guild.

Note for those with existing Discord bot applications: the bot token used could come from another application, but if you intend to use the `force_join` functionality (to automatically join a Discord user to your guild if they're not already a member), the bot token **must** belong to the same application used for OAuth.

### Creating a Reddit Application

1. Head to the reddit developer applications page [here](https://old.reddit.com/prefs/apps/)
2. Choose `are you a developer? create an app` / `create another app`
3. Under the name field, you can once again choose something like '*MyCommunity* Auth'. This could be identical to the name of your Discord OAuth Application.
4. Leave the default application type as *web app*
5. In the *redirect uri* field, enter the URL to your Discreddit directory (with trailing slash), followed by `?return=reddit` e.g. `https://mysite.com/dcr/?return=reddit`). The other fields may be left blank.
6. Press *create app*. You will now be presented with the client information.
![](https://i.imgur.com/3FmdotE.png)
7. Copy and paste the client ID and client secret into **`reddit`** `=> oauth =>` **`client_id`** and **`client_secret`** in config.php.
8. You must also set `reddit => oauth => appname` to the name of your reddit app (e.g. `Beep Auth` above), and `author` to your reddit username (e.g. `HilbertGilbertson`).

### Configuring Communities

In config.php, you must set the following values:

* `discord =>`
	* `guild_id`: the [Discord ID](https://support.discordapp.com/hc/en-us/articles/206346498-Where-can-I-find-my-User-Server-Message-ID-) of your guild
	* `guild_title`: the title of your Discord guild e.g. `the MyCommunity Discord`
	* `invite_link`: an invite link to your Discord guild

* `reddit =>`
	* `subreddit`: the name of your subreddit. This will be the case sensitive string that comes after `reddit.com/r/` e.g. `watch_dogs`
	* `subreddit_title`: the name of your subreddit e.g. `/r/Watch_Dogs`

### Additional Config

* `discord => force_join`: set this to true to force any Discord users who sign in through your Discreddit installation to join your Discord. If enabled they will be joined automatically.
  * If enabled, the Discord OAuth consent screen will additionally ask the Discord user for permission for your application to *Join servers for you*.

* `discord => oauth => use_connections`: 
  * If the user has officially linked their Discord account with a number of third-party services (e.g. skype, xbox, twitter, twitch, etc), enabling this setting will allow us to use the *DisCon()* function to fetch a particular third-party connection, if present.
  * If enabled, the Discord OAuth consent screen will additionally ask the Discord user for permission for your application to *Access your third-party connections*.

* `reddit => force_subscribe`: set this to true to force any reddit users who sign in through your Discreddit installation to subscribe to your subreddit. If enabled they will be subscribed automatically.
  * If enabled, the reddit OAuth consent screen will additionally ask the reddit user for permission for your application to *Manage my subreddit subscriptions. Manage "friends" - users whose content I follow*.
  * You can still require the user to be subcribed without forceably subscribing them. If you enable the `subscriber` requirement (see requirements below) and the user is not subscribed to your subreddit, then they will be able to subscribe manually and Discreddit will then allow them to pass.
  * This setting is not a substitution for the `subscriber` requirement, and if enabled it is strongly recommended that you also ensure the `subscriber` requirement is set to `true`.


* **`this_url`**: **must** be set to the valid URL of your installation, complete with trailing slash e.g. `https:///mysite.com/dcr/`
* `cookie_warning`: whether to display a cookie warning to users (set to true or false)
* `use_tos`: whether to require the user to agree to T&Cs (you must edit the link to the T&Cs in index.php). Set to true and users will see a link and have to agree.

#### DB Config

Discreddit supports MySQL and SQLite. It is recommended that enable database storage, in order to keep a record of a user's Discord ID and reddit handle (such that bots or other applications can later make use of this information), and to prevent users from attempting to link an account more than once.

##### MySQL

Discreddit can connect to a MySQL database (using only one table, for which the template SQL file can be found in the `extra` directory (`extra/db.sql`).

In config.php, set `use_db` to `mysql`, and in the `mysql` array, configure `host`, `dbname`, `user` and `password` to match the appropriate credentials.

The table name should be `discreddit`, but this can be changed by editing Discreddit.DB.php, should you wish to use the Discreddit table in an existing database, for example.

##### SQLite
As an alternative to MySQL, Discreddit can use an SQLite database file, and a `db.sqlite3` file is provided in the `extra` directory. 

Simply upload the db.sqlite3 file to a directory outside of your webroot (or within the webroot if using htaccess to deny access to it, for example).

Ensure that the file is writable by the web user (e.g. 644 permissions), and then modify config.php, setting `use_db` to `sqlite` and `sqlite_path` to the full file path to `db.sqlite3`.

### Requirements

The requirements configuration can be found in config.php, and determines what requirements a user will have to meet, both in respect of reddit and discord, before being able to link their accounts.

Want to have no restrictions at all? No problem. Want to ensure users have 500 karma and are Nitro Boosters? Sure thing!

#### Reddit Requirements

* `min_karma`: require the user to have *x* number of total karma (comment karma and link karma combined). Set to `0` or `false` to disable.
* `min_age`: require the user to have been a member of reddit for *x* number of days (useful to help prevent new accounts from immediately being linked). Set to `0` or `false` to disable.
* `min_sr_karma`: require the user to have *x* number of total karma on your subreddit. Set to `0` or `false` to disable.
* `subscriber`: require the user to subscribe to your reddit. When the `reddit => force_subscribe` option is set to `true`, this condition should almost always be passed (unless the user is banned from your subreddit, for example).

#### Discord Requirements

* `min_age`: require the user to have created their Discord account at least *x* number of days ago. Set to `0` or `false` to disable.
* `onguild`: requires the user to be a member of your Discord guild. Set to true or false.
* `onguild_min`: require the user to have been a member of your Discord guild for *x* number of days. Set to `0` or `false` to disable.
* `has_role`: requires the user to have a specific role on your Discord guild to be eligible to link their accounts. Set to false to disable. To enable, must be set to an array of `id => role ID, name => role name`. e.g. `'has_role' => array('id' => 168872590704115715, 'name' => 'VIP Members')`.

## Sample API

In the event that you have applications (e.g. a Discord bot) that you want to be able to look up users (by their reddit handles or Discord IDs), Discreddit has a sample API script for this purpose.

You can find the API script in `extra/api`. It is not required on standard installation (you only need the files in the *dist* directory), and in any case should not be implemented without some customisation.

## Actions

What would you like Discreddit to *do* when someone has identified both their reddit and Discord accounts and satisfied your requirements (if any)?

By default, Discreddit will simply create a record in the database upon success, if the `use_db` config option is enabled. If you want to do anything more, you'll need to implement some of the available methods. We can do this by placing our completion code in `Discreddit.php`.

1. Open `Discreddit.php` in your chosen editor.
2. Look for the `onCompletion()` method. This method will be called upon successful linking.

```php
private function onCompletion()
    {
        /*
         * Place your actions here.
         */
    }
```

### Examples

For example, if we wanted to add a Discord role to the user on completion:

```php
private function onCompletion() {
  $this->DiscordMemberAddRole($this->discord->user->id, '298209989909020672');
}
```

If we wanted to post a message into a channel with a webhook when the user links their account, we could place the following inside the onCompletion() method:

```php
$embed = ['title' => "Reddit Account Linked",
  'description' => "<@{$this->discord->user->id}> just linked their reddit account.",
  'color' => 16728833,
  'footer' => [
    'text' => "Powered by Discreddit"
  ],
  'fields' => 
  [
    [
      'name' => "Reddit",
      'value' => "[/u/{$this->reddit->user->name}](https://reddit.com/u/{$this->reddit->user->name})",
      'inline' => true
     ],
     [
       'name' => "Discord",
       'value' => $this->discord->user->username . "#" . $this->discord->user->discriminator,
       'inline' => true
      ]
  ]
];

$this->DiscordPostWebhook(
  "https://discordapp.com/api/webhooks/275471922946896031/blahblahblah", //webhook URL
  null,
  [ $embed ],
  "Discreddit",
  "https://i.imgur.com/enyNdws.png"
);
```

Or if we wanted to post this message as our bot user instead of via a webhook:

```php
$this->DiscordPostMsg('272004382659313664', null, $embed);
```

If we wanted to set the Discord user's nickname to their reddit username (up to 32 characters):

```php
$this->DiscordMemberChangeNick($this->discord->user->id, substr($this->reddit->user->name, 0, 32));
```

## Template Customisation

The template in Discreddit is rather simple but functional. All JS & CSS code is compressed contained within the page, with zero dependence on locally hosted files. The uncompressed CSS & JS can be found in the `extra/src` directory (which does not need to be uploaded), should you need to edit it.

In index.php, there are some holding paragraphs that you should replace:

```html
<p>
Here's the place to explain all the wonderful benefits you're offering users linking their
reddit and Discord accounts on your community.
</p>
```

```html
<p>
Your Discord and reddit accounts are now linked with our community.
Congratulations message goes here.
</p>
```

```html
<label for="tos_agree" class="small">
I accept the <a href="https://link/to/my/tos" target="_blank">TOS & Privacy Policy</a>
</label>
```

You only need to place a link above to your terms if you have enabled the `use_tos` option in config.php.

The template also features a copyright and attribution footer; you may remove this (and make all the changes you want) so long as you clearly credit me somewhere in your implementation of Discreddit.

## License

Distributed under the MIT License. See `LICENSE.txt` for more information.

## Contact

HilbertGilbertson - [@HilbertsGilbert](https://twitter.com/HilbertsGilbert)

Project URL: [https://github.com/HilbertGilbertson/Discreddit](https://github.com/HilbertGilbertson/Discreddit)


## Acknowledgements
* [SweetAlert2](https://sweetalert2.github.io/)
* [cookieconsent](https://cookieconsent.osano.com)
* [Moment.js](https://momentjs.com)
* [Font Awesome](https://fontawesome.com)

[build-shield]: https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square
[contributors-shield]: https://img.shields.io/badge/contributors-1-orange.svg?style=flat-square
[contributors-url]: https://github.com/HilbertGilbertson/Discreddit/contributors
[license-shield]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[license-url]: https://choosealicense.com/licenses/mit
[dcr-screenshot]: https://i.imgur.com/aQR5eI5.png