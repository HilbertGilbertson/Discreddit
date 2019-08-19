<?php
/**
 * Discreddit
 * @file Discreddit.php
 * @version 1.0
 * @author HilbertGilbertson
 * @url https://github.com/HilbertGilbertson/Discreddit
 */

class Discreddit
{
    public $config;
    public $reddit;
    public $discord;
    public $db;
    protected $ver;

    public function __construct($configArr)
    {
        session_name('RedditLink');
        session_start();
        $this->reddit = (isset($_SESSION['reddit']) ? $this->toObj($_SESSION['reddit']) : false);
        $this->discord = (isset($_SESSION['discord']) ? $this->toObj($_SESSION['discord']) : false);
        $this->config = $this->toObj($configArr);
        $this->ver = "1.0";

        if ($this->config->use_db) {
            require('Discreddit.DB.php');
            if ($this->config->use_db == 'mysql' || $this->config->use_db == 'sqlite') {
                $fp = ($this->config->use_db == 'sqlite' ? $this->config->sqlite_path : $this->config->mysql);
                $this->db = new DiscredditDB($this->config->use_db, $fp);
            } else {
                $this->config->use_db = false;
            }
        }
    }

    public function refresh($site = "reddit")
    {
        if ($site == "discord" || $site == "reddit") {
            unset($_SESSION[$site]);
            $this->{$site} = false;
            if (!isset($_SESSION["oauth_$site"]) || (isset($_SESSION["oauth_$site"]) && $_SESSION["oauth_$site"]->expires < time())) {
                unset($_SESSION["oauth_$site"]);
                $this->run();
            }
            if ($site == "reddit") $this->FetchRedditData();
            else $this->FetchDiscordData();
        }
    }

    public function ajaxReply($stage, $extra = array())
    {
        $handles = array();
        if ($this->reddit) {
            $handles['reddit'] = "/u/" . $this->reddit->user->name;
        }
        if ($this->discord) {
            $handles['discord'] = $this->discord->user->username . "#" . $this->discord->user->discriminator;
        }
        $baseData = array('stage' => $stage, 'handles' => $handles);
        header("Content-Type: application/json");
        die(json_encode(array_merge_recursive($baseData, $extra)));
    }

    public function run($acknowledged = 1)
    {
        if (!$this->reddit) {
            if (!isset($_SESSION['oauth_reddit'])) {
                $state = substr(md5(session_id()), -12);
                $product = $this->product('reddit');
                $params = array(
                    'response_type' => $product->response_type,
                    'client_id' => $this->config->reddit->oauth->client_id,
                    'scope' => implode(" ", $product->scopes),
                    'state' => $state,
                    'redirect_uri' => $this->config->this_url . "?return=" . $product->name
                );
                $this->ajaxReply('reddit_oauth', [
                    'nextURL' => $product->auth_url . "?" . http_build_query($params)
                ]);
            } else {
                $this->FetchRedditData();
            }
        }

        if ($this->db && $this->db->from_reddit($this->reddit->user->name)) {
            unset($_SESSION['reddit'], $_SESSION['oauth_reddit']);
            $this->ajaxReply('failed', [
                'error' => "This reddit account is already linked to a Discord account."
            ]);
        }

        $srkarma = new stdClass();
        $srkarma->comment_karma = 0;
        $srkarma->link_karma = 0;
        $srkarma->total = 0;

        foreach ($this->reddit->karma->data as $sub) {
            if ($sub->sr == $this->config->reddit->subreddit) {
                $srkarma = $sub;
                $srkarma->total = ($sub->comment_karma + $sub->link_karma);
                break;
            }
        }

        $redditReqs = array();
        if ($this->config->requirements->reddit->min_karma) {
            $redditReqs['min_karma'] = false;
            if (($this->reddit->user->link_karma + $this->reddit->user->comment_karma) >= $this->config->requirements->reddit->min_karma) {
                $redditReqs['min_karma'] = true;
            }
        }

        if ($this->config->requirements->reddit->min_sr_karma) {
            $redditReqs['min_sr_karma'] = false;
            // we could check comment_karma, link_karma, or the total. Let's check just the total.
            if ($srkarma->total >= $this->config->requirements->reddit->min_sr_karma) {
                $redditReqs['min_sr_karma'] = true;
            }
        }

        if ($this->config->requirements->reddit->min_age) {
            $redditReqs['min_age'] = false;

            if ((time() - $this->reddit->user->created_utc) >= (86400 * $this->config->requirements->reddit->min_age)) {
                $redditReqs['min_age'] = true;
            }
        }

        if ($this->config->requirements->reddit->subscriber && isset($this->reddit->sub)) {
            $redditReqs['subscriber'] = $this->reddit->sub->user_is_subscriber;
        }

        /*
         * Where the subscriber requirement is enabled, we can also evaluate additional information like:
         * user_is_moderator
         * user_is_contributor
         * user_has_favorited
         * user_is_muted
         * user_is_banned
         * user_is_muted
         * user_flair_css_class
         * user_can_flair_in_sr
         */

        $fail = false;
        foreach ($redditReqs as $r) {
            if (!$r) {
                $fail = true;
                break;
            }
        }

        if ($fail || $acknowledged == 1) {
            $this->ajaxReply('reddit_requirements', [
                'failed' => $fail,
                'redditReqs' => (!empty($redditReqs) ? $redditReqs : false),
                'srkarma' => $srkarma,
                'karma' => [
                    'comment_karma' => $this->reddit->user->comment_karma,
                    'link_karma' => $this->reddit->user->link_karma,
                    'total' => ($this->reddit->user->comment_karma + $this->reddit->user->link_karma)
                ]
            ]);
        }

        if (!$this->discord) {
            if (!isset($_SESSION['oauth_discord'])) {
                $state = substr(md5(session_id()), -12);
                $product = $this->product('discord');
                $params = array(
                    'response_type' => $product->response_type,
                    'client_id' => $this->config->discord->oauth->client_id,
                    'scope' => implode(" ", $product->scopes),
                    'state' => $state,
                    'redirect_uri' => $this->config->this_url . "?return=" . $product->name,
                    'prompt' => 'consent'
                );
                $this->ajaxReply('discord_oauth', [
                    'nextURL' => $product->auth_url . "?" . http_build_query($params)
                ]);
            } else {
                $this->FetchDiscordData();
            }
        }

        if ($this->db && $this->db->from_discord($this->discord->user->id)) {
            unset($_SESSION['discord'], $_SESSION['oauth_discord']);
            $this->ajaxReply('failed', [
                'error' => "This Discord user is already linked to a reddit account."
            ]);
        }

        $DiscordReqs = array();
        if ($this->config->requirements->discord->min_age) {
            $DiscordReqs['min_age'] = false;

            if ((time() - $this->snow2ts($this->discord->user->id)) >= (86400 * $this->config->requirements->discord->min_age)) {
                $DiscordReqs['min_age'] = true;
            }
        }

        if ($this->config->requirements->discord->onguild) {
            $DiscordReqs['onguild'] = false;
            if ($this->discord->onGuild) {
                $DiscordReqs['onguild'] = true;
            }
        }

        if ($this->config->requirements->discord->onguild_min && $this->config->discord->bot->enabled) {
            $DiscordReqs['onguild_min'] = false;
            if ($this->discord->member) {
                $joined = strtotime($this->discord->member->joined_at);
                if ($joined && (time() - $joined) >= (86400 * $this->config->requirements->discord->onguild_min)) {
                    $DiscordReqs['onguild_min'] = true;
                }
            }
        }

        if ($this->config->requirements->discord->has_role && $this->config->discord->bot->enabled) {
            $DiscordReqs['has_role'] = false;
            if ($this->discord->member && in_array($this->config->requirements->discord->has_role->id, $this->discord->member->roles)) {
                $DiscordReqs['has_role'] = true;
            }
        }

        $fail = false;
        foreach ($DiscordReqs as $r) {
            if (!$r) {
                $fail = true;
                break;
            }
        }

        if ($fail || $acknowledged <= 2) {
            $this->ajaxReply('discord_requirements', [
                'failed' => $fail,
                'DiscordReqs' => (!empty($DiscordReqs) ? $DiscordReqs : false),
                'member' => ($this->discord->member ? [
                    'joined_at' => $this->discord->member->joined_at,
                    'premium_since' => $this->discord->member->premium_since,
                    'roles' => $this->discord->member->roles
                ] : false)
            ]);
        }

        if ($acknowledged == 3) {
            if ($this->db) {
                if (!$this->db->create($this->discord->user->id, $this->reddit->user->name)) {
                    $this->ajaxReply('failed', [
                        'error' => "The database encountered an error. Please try reloading the page.",
                        'reload' => true
                    ]);
                }
            }
            $this->onCompletion();
            session_destroy();
            $this->ajaxReply('complete');
        }
    }

    public function OAuth_return()
    {
        $state = substr(md5(session_id()), -12);
        $return = (isset($_GET['return']) ? $_GET['return'] : false);
        $product = $this->product($return);
        if ($return && $product && isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] == $state) {
            $params = array(
                'client_id' => $this->config->{$return}->oauth->client_id,
                'client_secret' => $this->config->{$return}->oauth->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'redirect_uri' => $this->config->this_url . "?return=" . $return
            );

            if (!isset($_SESSION['oauth_' . $product->name])) {
                $requestTime = time();
                $request = $this->request($product->token_url, $params, $product->name, 'POST', true);
                if (!$request || !isset($request['response']->access_token)) {
                    $this->ajaxReply('failed', [
                        'error' => "Fatal error: request to the {$product->name} API failed. Please go back and try again."
                    ]);
                }

                $request['response']->expires = 0;
                if (isset($request['response']->expires_in) && $request['response']->expires_in > 0) {
                    $request['response']->expires = ($requestTime + $request['response']->expires_in);
                }

                $_SESSION['oauth_' . $product->name] = $request['response'];
            }
        }
        //redirect in ALL cases
        header("Location: " . $this->config->this_url);
        exit;
    }

    private function onCompletion()
    {
        /*
         * Place your actions here.
         */
    }

    private function RedditKarma()
    {
        $request = $this->request("api/v1/me/karma", null, "reddit");
        if ($request['code'] != 200 || !isset($request['response']->data)) return false;
        return $request['response'];
    }

    private function RedditUser()
    {
        $request = $this->request("api/v1/me", null, "reddit");
        if ($request['code'] != 200 || !isset($request['response']->name)) return false;
        return $request['response'];
    }

    private function RedditSubscribetoSR()
    {
        $request = $this->request("api/subscribe", [
            'action' => 'sub',
            'skip_initial_defaults' => true,
            'sr_name' => $this->config->reddit->subreddit
        ], "reddit");
        return $request['response'];
    }

    private function RedditSRData()
    {
        $request = $this->request("r/" . $this->config->reddit->subreddit . "/about", null, "reddit");
        if ($request['code'] != 200 || !isset($request['response']->data)) return false;
        return $request['response']->data;
    }

    private function DiscordUser()
    {
        $request = $this->request("users/@me", null, "discord");
        if ($request['code'] != 200 || !isset($request['response']->username)) return false;
        return $request['response'];
    }

    private function DiscordMember($user_id)
    {
        $request = $this->request("guilds/{$this->config->discord->guild_id}/members/" . $user_id,
            null,
            "discord",
            null,
            false, [
                "Authorization: Bot {$this->config->discord->bot->token}"
            ]);
        return ($request['code'] == 200 ? $request['response'] : false);
    }

    private function DiscordGuilds()
    {
        $request = $this->request("users/@me/guilds", null, "discord");
        if ($request['code'] != 200) return false;
        return $request['response'];
    }

    private function OnGuild($guild_id, $guilds)
    {
        foreach ($guilds as $guild) {
            if ($guild->id == $guild_id)
                return true;
        }
        return false;
    }

    private function DiscordConnections()
    {
        $request = $this->request("users/@me/connections", null, "discord");
        return ($request['code'] == 200 ? $request['response'] : false);
    }

    private function FetchRedditData()
    {
        if (isset($_SESSION['oauth_reddit'])) {
            $user = $this->RedditUser();
            $karma = $this->RedditKarma();
            $scopes = explode(" ", $_SESSION['oauth_reddit']->scope);
            if ($this->config->reddit->force_subscribe && in_array('subscribe', $scopes)) {
                $this->RedditSubscribetoSR();
            }
            $sub = (in_array('read', $scopes) ? $this->RedditSRData() : false);

            if ($user && $karma) {
                $_SESSION['reddit']['user'] = $user;
                $_SESSION['reddit']['karma'] = $karma;
                if ($sub) $_SESSION['reddit']['sub'] = $sub;
                $_SESSION['reddit']['retrieved'] = time();
                $this->reddit = $this->toObj($_SESSION['reddit']);
            } else {
                unset($_SESSION['oauth_reddit']);
                $this->ajaxReply('reddit_oauth', [
                    'error' => "The reddit API could not be contacted. Please try reloading the page.",
                    'reload' => true
                ]);
            }
        }
    }

    private function FetchDiscordData()
    {
        if (isset($_SESSION['oauth_discord'])) {
            $user = $this->DiscordUser();
            $guilds = $this->DiscordGuilds();
            $scopes = explode(" ", $_SESSION['oauth_discord']->scope);
            $connections = (in_array('connections', $scopes) ? $this->DiscordConnections() : false);
            $member = false;
            if ($user && $guilds !== false) {
                $onGuild = $this->OnGuild($this->config->discord->guild_id, $guilds);
                $_SESSION['discord']['user'] = $user;
                $_SESSION['discord']['guilds'] = $guilds;

                if (!$onGuild) {
                    if ($this->config->discord->force_join && $this->config->discord->bot->enabled) {
                        $join = $this->DiscordJoinGuild($this->discord->user->id, $_SESSION['oauth_discord']->access_token);
                        if ($join['code'] == 201) {
                            $onGuild = true;
                            $member = $join['response'];
                        } elseif ($join['code'] == 204) {
                            $onGuild = true;
                        }
                    }
                }

                if (!$member && $onGuild && $this->config->discord->bot->enabled)
                    $member = $this->DiscordMember($user->id);

                if ($member && isset($member->user)) unset($member->user); //duplicate of the user object; superfluous
                $_SESSION['discord']['connections'] = $connections;
                $_SESSION['discord']['onGuild'] = $onGuild;
                $_SESSION['discord']['member'] = $member;
                $_SESSION['discord']['retrieved'] = time();
                $this->discord = $this->toObj($_SESSION['discord']);

            } else {
                unset($_SESSION['oauth_discord']);
                $this->ajaxReply('failed', [
                    'error' => "The Discord API could not be contacted. Please try reloading the page.",
                    'reload' => true
                ]);
            }
        }
    }

    private function product($name)
    {
        if ($name != "discord" && $name != "reddit") return false;
        $product = new stdClass();
        $product->name = $name;
        $product->response_type = 'code';

        if ($name == "reddit") {
            $product->auth_url = "https://www.reddit.com/api/v1/authorize";
            $product->token_url = "https://www.reddit.com/api/v1/access_token";
            $product->api_url = "https://oauth.reddit.com/";
            $product->scopes = array('identity', 'mysubreddits');
            if ($this->config->requirements->reddit->subscriber) {
                $product->scopes[] = 'read';
            }
            if ($this->config->reddit->force_subscribe) {
                $product->scopes[] = 'subscribe';
            }
        } else {
            $product->scopes = array('identify', 'guilds');
            if ($this->config->discord->force_join) {
                $product->scopes[] = 'guilds.join';
            }
            if ($this->config->discord->oauth->use_connections) {
                $product->scopes[] = 'connections';
            }
            $product->auth_url = "https://discordapp.com/api/oauth2/authorize";
            $product->token_url = "https://discordapp.com/api/oauth2/token";
            $product->api_url = "https://discordapp.com/api/";
        }
        return $product;
    }

    private function toObj($a)
    {
        return is_array($a) ? (object)array_map(__METHOD__, $a) : $a;
    }

    private function snow2ts($snowflake)
    {
        return floor(((intval($snowflake) / 4194304) + 1420070400000) / 1000);
    }

    private function request($url, $data = null, $product = null, $dataMethod = "POST", $getToken = false, $headers = false)
    {
        $opts = array(
            CURLOPT_USERAGENT => "Discreddit/" . $this->ver,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10
        );

        if ($product) {
            $pro = $this->product($product);
            if (!$pro)
                $this->ajaxReply('failed', [
                    'error' => "Invalid API product ($product) provided to the request function."
                ]);

            if (!$getToken) {
                $url = $pro->api_url . $url;
                $oauth = $_SESSION['oauth_' . $pro->name];
                $opts[CURLINFO_HEADER_OUT] = false;
                $opts[CURLOPT_HEADER] = false;
                $opts[CURLOPT_HTTPHEADER] = array("Authorization: {$oauth->token_type} {$oauth->access_token}");
                if ($headers) {
                    //overwrite headers if specified
                    $opts[CURLOPT_HTTPHEADER] = $headers;
                }
            } else {
                if ($pro->name == 'reddit') {
                    $opts[CURLOPT_USERAGENT] = "php-Discreddit:{$this->config->reddit->oauth->appname}:{$this->ver} (by /u/{$this->config->reddit->oauth->author})";
                    $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
                    $opts[CURLOPT_USERPWD] = "{$this->config->reddit->oauth->client_id}:{$this->config->reddit->oauth->client_secret}";
                }
            }
        }

        if ($data != null) {
            $opts[CURLOPT_POSTFIELDS] = $data;
            $opts[CURLOPT_CUSTOMREQUEST] = $dataMethod;
        } elseif ($dataMethod && $dataMethod != "POST") {
            $opts[CURLOPT_CUSTOMREQUEST] = $dataMethod;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);
        $fetch = curl_exec($ch);

        $response = @json_decode($fetch);
        if (json_last_error()) {
            $response = $fetch;
        }
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);
        return array('code' => $code, 'response' => $response);
    }

    /**
     * Using this function requires additional setup options. Please refer to the documentation.
     *
     * To use force_join, you must convert your Discord Application to a Discord Bot, and you must place the Bot's
     * token (NOT the same as the Client Secret) in the config -> discord -> bot -> token field.
     *
     * You must then ensure that you join this bot to your guild, where the bot must have the permission to Create
     * Instant Invites.
     *
     * If you further wish to allow this script to add roles to the user on your Discord guild, you must also grant
     * your bot the permission to Manage Roles; likewise Manage Nicknames if you wish to allow this script to set
     * a user's nickname
     *
     * @param string|int $user_id
     * @param string $access_token
     * @param null|array $roles
     * @param null|string $nick
     * @return array
     */
    private function DiscordJoinGuild($user_id, $access_token, $roles = null, $nick = null)
    {
        $data = array('access_token' => $access_token);
        if ($nick) $data['nick'] = substr($nick, 0, 32);
        if ($roles && is_array($roles)) $data['roles'] = $roles;
        $request = $this->request("guilds/{$this->config->discord->guild_id}/members/" . $user_id,
            $data,
            "discord",
            "PUT",
            false, [
                "Authorization: Bot {$this->config->discord->bot->token}"
            ]);
        return $request;
    }

    /**
     * If a user has officially linked their Discord account with a third party provider, we can fetch the details
     * Supported types: "twitch", "twitter", "reddit", "youtube", "battlenet", "skype", "steam", "facebook", "spotify",
     * "xbox"
     *
     * @param string $type
     * @return bool|mixed
     */
    private function DisCon($type)
    {
        if ($this->discord && is_object($this->discord->connections) && !empty($this->discord->connections)) {
            foreach ($this->discord->connections as $con) {
                if ($con->type == $type)
                    return $con;
            }
        }
        return false;
    }

    /**
     * This function requires a valid and properly configured Bot joined to your target guild, which has the permission
     * to Manage Roles.
     *
     * @param string|int $user_id
     * @param string|int $role_id
     * @return bool
     */
    private function DiscordMemberAddRole($user_id, $role_id)
    {
        $request = $this->request("guilds/{$this->config->discord->guild_id}/members/{$user_id}/roles/" . $role_id,
            null,
            "discord",
            "PUT",
            false, [
                "Authorization: Bot {$this->config->discord->bot->token}"
            ]);
        return ($request['code'] == 204 ? true : false);
    }

    /**
     * This function requires a valid and properly configured Bot joined to your target guild, which has the permission
     * to Manage Nicknames.
     *
     * @param string|int $user_id
     * @param string $nick
     * @return bool
     */
    private function DiscordMemberChangeNick($user_id, $nick)
    {
        $request = $this->request("guilds/{$this->config->discord->guild_id}/members/" . $user_id,
            ['nick' => $nick],
            "discord",
            "PATCH",
            false, [
                "Authorization: Bot {$this->config->discord->bot->token}"
            ]);
        return ($request['code'] == 204 ? true : false);
    }

    /**
     * This function requires a valid and properly configured Bot with access to, and permission to send messages in,
     * the target channel. It's simpler to use Webhooks (recommended) with the DiscordPostWebhook method!
     *
     * Due to a restriction on the Discord API, this method WILL NOT WORK unless you have connected your Bot to
     * the Discord gateway at least once. You can do this by using the Bot's token with any simple bot application. You
     * only have to do this once and it's achievable in a mere couple of minutes. Nonetheless, if you just need to post
     * messages, you can just use Webhooks ;)
     *
     * @param string|int $channel_id
     * @param string|null $message
     * @param array|null $embeds
     * @return bool|mixed
     */
    private function DiscordPostMsg($channel_id, $message = null, $embed = null)
    {
        $data = array();
        if ($message) $data['content'] = $message;
        if ($embed) $data['embed'] = $embed;
        $request = $this->request("channels/{$channel_id}/messages",
            json_encode($data),
            "discord",
            null,
            false, [
                "Authorization: Bot {$this->config->discord->bot->token}",
                "Content-Type: application/json"
            ]);
        return ($request['code'] == 200 ? $request['response'] : false);
    }

    /**
     * @param string $webhook_url
     * @param string|null $message
     * @param array|null $embeds
     * @return bool|mixed
     *
     * If $embeds is specified, it should be array of embed arrays, as webhooks work a little differently
     */
    private function DiscordPostWebhook($webhook_url, $message = null, $embeds = null, $username = null, $avatar_url = null)
    {
        $data = array();
        if ($message) $data['content'] = $message;
        if ($embeds) $data['embeds'] = $embeds;
        if ($username) $data['username'] = $username;
        if ($avatar_url) $data['avatar_url'] = $avatar_url;
        $request = $this->request($webhook_url, json_encode($data));
        return ($request['code'] == 200 ? $request['response'] : false);
    }

}
