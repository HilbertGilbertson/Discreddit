<?php

$config = [
    'this_url' => "https://mysite.com/dcr/",
    //set to the URL of this directory, complete with trailing slash (/)
    'cookie_warning' => false,
    //show a cookie warning to users?
    'use_tos' => false,
    //require the user to agree to T&Cs?
    'discord' => [
        'oauth' => [
            //VERY important. See the README.
            'client_id' => "",
            'client_secret' => "",
            'appname' => "",
            'use_connections' => false
        ],
        'bot' => [
            //See the README
            'enabled' => false,
            'token' => ""
        ],
        'guild_id' => 900000000000000009,
        'guild_title' => "MyCommunity Discord",
        'invite_link' => "https://discord.gg/myinvite",
        'force_join' => false
        //true/false. Set to true to automatically join to your guild all Discord users who authenticate
    ],
    'reddit' => [
        //See the README
        'subreddit' => "Hilbert's Subreddit",
        'subreddit_title' => '/r/HilbertLand',
        'force_subscribe' => false,
        //true/false. Set to true to automatically subscribe to your subreddit all reddit users who authenticate
        'oauth' => [
            'client_id' => "",
            'client_secret' => "",
            'appname' => "",
            'author' => ""
        ]
    ],
    'requirements' => [
        //set any of these to 0 (zero) or false to disable
        'reddit' => [
            'min_karma' => 0,
            // (int) number of karma points the user must have on reddit
            'min_age' => 0,
            // (int) minimum age of the user's reddit account in DAYS,
            'min_sr_karma' => 0,
            // (int) number of karma points the user must have from the subreddit
            'subscriber' => false
            // (bool) require the user to be a subscriber of the reddit
        ],
        'discord' => [
            'min_age' => 0,
            // (int) minimum age of the user's Discord account in DAYS
            'onguild' => false,
            // (bool) require the user to be on the Discord
            'onguild_min' => 0,
            // (int) minimum time the user must have been on your Discord guild in DAYS
            'has_role' => false
            // (optional [id, name]of a role on your Discord guild the user must have to qualify for linking (false to disable)
            //'has_role' => ['id' => 268872990704145815, 'name' => "VIP Members"]
        ]
    ],
    'use_db' => false, //false, 'mysql', or 'sqlite'
    'mysql' => [
        'host' => 'localhost',
        'dbname' => 'mydbname',
        'user' => 'dbuser',
        'password' => 'passw0rd'
    ],
    'sqlite_path' => '/path/to/db.sqlite3'
];