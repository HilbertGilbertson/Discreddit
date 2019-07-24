<?php
/**
 * Discreddit
 * @file index.php
 * @version 1.0
 * @author HilbertGilbertson
 * @url https://github.com/HilbertGilbertson/Discreddit
 */

require 'Discreddit.php';
require 'config.php';

$DCR = new Discreddit($config);
$requirements = $DCR->config->requirements;

if (isset($_GET['refresh'])) {
    if ($_GET['refresh'] != "reddit" && $_GET['refresh'] != "discord") exit;
    $timeout = (300 - (time() - $DCR->{$_GET['refresh']}->retrieved));
    if ($DCR->{$_GET['refresh']} && $timeout > 0) {
        $DCR->ajaxReply(false, ['retimeout' => $timeout]);
    }
    $DCR->refresh($_GET['refresh']);
    $DCR->ajaxReply(false);
} elseif (isset($_GET['run'])) {
    $acknowledged = (isset($_GET['acknowledged']) && in_array($_GET['acknowledged'], ['1', '2', '3']) ? intval($_GET['acknowledged']) : 1);
    $DCR->run($acknowledged);
    exit;
} elseif (isset($_GET['return'])) {
    $DCR->OAuth_return();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <?php if ($DCR->config->cookie_warning) { ?>
        <link rel="stylesheet" type="text/css"
              href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css">
    <?php } ?>
    <title>Discreddit</title>
    <style>
        body{height:100%}body{display:-ms-flexbox;display:flex;-ms-flex-align:center;align-items:center;padding-top:40px;padding-bottom:40px;background-color:#94bd99}.btn-reddit{color:#fff;background-color:#ff4301;border-color:#ff4301}.btn-reddit:hover{color:#fff;background-color:#d73800;border-color:#d23b06}.btn-reddit:focus,.btn-reddit.focus{box-shadow:0 0 0 .2rem rgba(254,96,41,.5)}.btn-reddit.disabled,.btn-reddit:disabled{color:#fff;background-color:#ff4301;border-color:#ff4301}.btn-reddit:not(:disabled):not(.disabled):active,.btn-reddit:not(:disabled):not(.disabled).active,.show>.btn-reddit.dropdown-toggle{color:#fff;background-color:#d23b06;border-color:#bd3708}.btn-reddit:not(:disabled):not(.disabled):active:focus,.btn-reddit:not(:disabled):not(.disabled).active:focus,.show>.btn-reddit.dropdown-toggle:focus{box-shadow:0 0 0 .2rem rgba(254,96,41,.5)}.btn-discord{color:#fff;background-color:#7289da;border-color:#7289da}.btn-discord:hover{color:#fff;background-color:#576aad;border-color:#5a6ba8}.btn-discord:focus,.btn-discord.focus{box-shadow:0 0 0 .2rem rgba(126,148,236,.5)}.btn-discord.disabled,.btn-discord:disabled{color:#fff;background-color:#7289da;border-color:#7289da}.btn-discord:not(:disabled):not(.disabled):active,.btn-discord:not(:disabled):not(.disabled).active,.show>.btn-discord.dropdown-toggle{color:#fff;background-color:#5a6ba8;border-color:#485dac}.btn-discord:not(:disabled):not(.disabled):active:focus,.btn-discord:not(:disabled):not(.disabled).active:focus,.show>.btn-discord.dropdown-toggle:focus{box-shadow:0 0 0 .2rem rgba(126,148,236,.5)}.pass{color:#28a745}.fail{color:#dc3545}.callout{padding:1.25rem;margin-top:1.25rem;margin-bottom:.25rem;border:1px solid #eee;border-left-width:.25rem;border-radius:.25rem}.callout p:last-child{margin-bottom:0}.callout code{border-radius:.25rem}.callout + .callout{margin-top:-.25rem}.callout-info{border-left-color:#5bc0de}.callout-warning{border-left-color:#f0ad4e}.callout-danger{border-left-color:#d9534f}
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="jumbotron py-5">
                <h2>Link Your Reddit Account with <?php echo $DCR->config->discord->guild_title; ?></h2>
                <p>
                    Here's the place to explain all the wonderful benefits you're offering users linking their reddit
                    and Discord accounts on your community.
                </p>
                <p>
                    <span class="badge badge-secondary handle-container handle_reddit"
                          style="background-color: #ff4400; display: none;"><span class="handle"></span> <i
                                class="fab fa-reddit"></i></span>
                    <span class="badge badge-secondary handle-container handle_discord"
                          style="background-color: #7289da; display: none;"><span class="handle"></span> <i
                                class="fab fa-discord"></i></span>
                </p>
                <hr>
                <div id="reddit_requirements" class="stages">
                    <strong>Reddit Requirements</strong>
                    <ul>
                        <?php if ($requirements->reddit->min_age) { ?>
                            <li id="reddit_min_age">
                                You must have been a reddit user for at
                                least <?php echo "<strong>{$requirements->reddit->min_age}</strong> day" . ($requirements->reddit->min_age > 1 ? "s" : ""); ?>.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                        <?php }
                        if ($requirements->reddit->subscriber) { ?>
                            <li id="reddit_subscriber">
                                You must subscribe to <a
                                        href="https://www.reddit.com/r/<?php echo $DCR->config->reddit->subreddit; ?>"
                                        target="_blank"><?php echo $DCR->config->reddit->subreddit_title; ?></a>.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                        <?php }
                        if ($requirements->reddit->min_karma) { ?>
                            <li id="reddit_min_karma">
                                You must have a total karma of at least
                                <strong><?php echo $requirements->reddit->min_karma; ?></strong> across
                                reddit.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                        <?php }
                        if ($requirements->reddit->min_sr_karma) { ?>
                            <li id="reddit_min_sr_karma">
                                You must have a total karma of at least
                                <strong><?php echo $requirements->reddit->min_sr_karma; ?></strong>
                                on <?php echo $DCR->config->reddit->subreddit_title; ?>.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                        <?php }
                        if (!$requirements->reddit->min_sr_karma &&
                            !$requirements->reddit->min_karma &&
                            !$requirements->reddit->subscriber &&
                            !$requirements->reddit->min_age
                        ) {
                            ?>
                            <li>
                                None <i class="fas fa-lg fa-check-circle pass"></i>
                            </li>
                        <?php } ?>
                    </ul>

                    <div class="callout callout-warning failed" style="display: none;">
                        Your reddit account does not yet meet the minimum requirements for this process.
                        <p class="mt-1 mb-1">
                            <button class="btn btn-reddit btn-sm refresh" data-product="reddit">Refresh <i
                                        class="fas fa-sync"></i></button>
                        </p>
                        <p class="small text-muted refresh_reddit" style="display:none;">Last updated: <span></span></p>
                    </div>

                    <div class="passed ml-4" style="display: none;">
                        <button class="btn btn-success" id="reddit_passed">Continue <i
                                    class="fas fa-arrow-alt-circle-right"></i></button>
                    </div>
                </div>
                <div id="discord_requirements" class="stages">
                    <span>
                        <strong>Discord Requirements</strong>
                        <ul>
                            <?php if ($requirements->discord->min_age) { ?>
                                <li id="discord_min_age">
                                Your Discord account must be at least <?php echo "<strong>{$requirements->discord->min_age}</strong> day" . ($requirements->discord->min_age > 1 ? "s" : ""); ?> old.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                            <?php }
                            if ($requirements->discord->onguild) { ?>
                                <li id="discord_onguild">
                                    You must be a member of
                                    <a href="<?php echo $DCR->config->discord->invite_link; ?>"
                                       target="_blank"><?php echo $DCR->config->discord->guild_title; ?></a>.
                                    <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                            <?php }
                            if ($requirements->discord->onguild_min) { ?>
                                <li id="discord_onguild_min">
                                You must have been a member of
                                <a href="<?php echo $DCR->config->discord->invite_link; ?>"
                                   target="_blank"><?php echo $DCR->config->discord->guild_title; ?></a> for at least
                                <?php echo "<strong>{$requirements->discord->onguild_min}</strong> day" . ($requirements->discord->onguild_min > 1 ? "s" : ""); ?>.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                            <?php }
                            if ($requirements->discord->has_role) { ?>
                                <li id="discord_has_role">
                                You must have the <i><?php echo $requirements->discord->has_role->name; ?></i> role on
                                <?php echo $DCR->config->discord->guild_title; ?>.
                                <i class="fas fa-lg fa-check-circle check pass" style="display:none"></i>
                                <i class="fas fa-lg fa-times-circle check fail" style="display:none"></i>
                            </li>
                            <?php }
                            if (!$requirements->discord->min_age &&
                                !$requirements->discord->has_role &&
                                !$requirements->discord->onguild_min &&
                                !$requirements->discord->onguild) {
                                ?>
                                <li>
                                None <i class="fas fa-lg fa-check-circle pass"></i>
                            </li>
                            <?php } ?>
                            </ul>
                        </span>

                    <div class="callout callout-warning failed" style="display: none;">
                        Your Discord account does not yet meet the minimum requirements for this process.
                        <p class="mt-1 mb-1">
                            <button class="btn btn-discord btn-sm refresh" data-product="discord">Refresh <i
                                        class="fas fa-sync"></i></button>
                        </p>
                        <p class="small text-muted refresh_discord" style="display:none;">Last updated: <span></span>
                        </p>
                    </div>

                    <div class="passed stages" style="display: none;">
                        <hr>
                        <h5>Ready to go?</h5>
                        <p>
                            You have met all the requirements. Should you know of any reason why these two accounts
                            should not be <strike>joined together in holy matrimony</strike> linked, click the button
                            below or forever wait on this page.
                        </p>
                        <button class="btn btn-primary" id="btn_complete">Link Me <i
                                    class="fas fa-link"></i></button>
                    </div>
                </div>
                <div id="start" class="stages">
                    <p>
                        If you do not yet meet these requirements or, should you
                        no longer wish to link your reddit account with your Discord ID, simply close this page.
                        Otherwise, click to begin below.
                    </p>
                    <p>
                        <?php if ($DCR->config->use_tos) { ?>
                            <span id="tos">
                            <input type="checkbox" id="tos_agree">
                            <label for="tos_agree" class="small">
                                <!--
                                I accept the <a href="https://link/to/my/tos" target="_blank">TOS & Privacy Policy</a>
                                -->
                                I accept the <a href="#"
                                                onclick="Swal.fire({title: 'Missing TOS/Privacy Link', text:'If you\'re going to force your users to agree to a policy, make sure the policy exists and then modify the template to link to it, instead of showing you this alert!', type: 'warning', position: 'top'}); return false;"
                                                target="_blank">TOS & Privacy Policy</a>
                            </label>
                        </span>
                        <?php } ?>
                    </p>

                    <p>
                        <button class="btn btn-primary btn-large" id="begin">Begin <i
                                    class="fas fa-arrow-alt-circle-right"></i></button>
                    </p>
                    <?php if ($DCR->config->reddit->force_subscribe) { ?>
                        <div class="callout callout-info small">
                            If you have not already subscribed to <a
                                    href="https://www.reddit.com/r/<?php echo $DCR->config->reddit->subreddit; ?>"
                                    target="_blank"><?php echo $DCR->config->reddit->subreddit_title; ?></a>, you will
                            be subscribed automatically. You may choose to unsubscribe (in the usual way) after linking
                            your reddit account.
                        </div>
                    <?php } ?>
                </div>
                <div id="reddit_oauth" class="stages" style="display: none;">
                    <p>
                        Please sign in through reddit. Upon clicking the below button, you will be redirected to reddit
                        and asked to grant temporary permission to
                        <i><?php echo $DCR->config->reddit->oauth->appname; ?></i> (choose <span
                                class="badge badge-secondary" style="background-color: #ff4400">Allow</span>) to verify
                        your reddit identity.
                    </p>
                    <p>
                        <button class="btn btn-reddit btn-large" id="reddit_login">Sign in through reddit <i
                                    class="fab fa-reddit"></i></button>
                    </p>
                </div>

                <div id="discord_oauth" class="stages" style="display: none;">
                    <p>
                        You must now sign in through Discord. Upon clicking the below button, you will be redirected to
                        the Discord website and asked to grant permission to
                        <i><?php echo $DCR->config->discord->oauth->appname; ?></i> (choose <span
                                class="badge badge-secondary" style="background-color: #7289da">Authorize</span>) to
                        verify your
                        Discord identity.
                    </p>
                    <p>
                        <button class="btn btn-discord btn-large" id="discord_login">Sign in through Discord <i
                                    class="fab fa-discord"></i></button>
                    </p>
                </div>

                <div id="complete" class="stages" style="display: none;">
                    <h4>Complete</h4>
                    <p>
                        Your Discord and reddit accounts are now linked with our community.
                        Congratulations message goes here.
                    </p>
                    <p class="text-center small">
                        You may now close this page.
                    </p>
                </div>
                <div id="failed" class="stages" style="display: none;">
                    <p>
                        <span id="error_msg"></span>
                    </p>
                    <p>
                        <button class="btn btn-danger btn-large" onclick="mainload()">Go Back <i
                                    class="fas fa-step-backward"></i></button>
                    </p>
                </div>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success" id="progress_bar" style="width: 5%"></div>
                <div class="progress-bar bg-danger" id="progress_danger" style="width: 0;"></div>
            </div>
            <footer>
                <div class="mt-4 small text-center">Powered by Discreddit. &copy; 2019 HilbertGilbertson</div>
            </footer>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/js/all.min.js"
        integrity="sha256-xzrHBImM2jn9oDLORlHS1/0ekn1VyypEkV1ALvUx8lU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js" data-cfasync="false"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
        integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script>
    <?php if($DCR->config->cookie_warning){ ?>
    window.cookieconsent.initialise({palette:{popup:{background:"#3c404d",text:"#d6d6d6"},button:{background:"#8bed4f"}}});
    <?php } ?>

    var Ack = <?php echo(isset($_SESSION['oauth_discord']) ? '2' : '1'); ?>,
        began = <?php echo(isset($_SESSION['oauth_discord']) || isset($_SESSION['oauth_reddit']) ? 'true' : 'false'); ?>;

    function passFail(e,s){$("#"+e+" .check").hide(),$("#"+e+" ."+(s?"pass":"fail")).show()}function progress_bar(e,s){void 0!==s&&s&&(s=100-e),$("#progress_bar").css("width",e+"%"),$("#progress_danger").css("width",(1<=s?s:0)+"%")}function process(e){$(".stages").hide(),$("button.disabled").prop("disabled",!1).removeClass("disabled"),"reddit_oauth"===e.stage?(progress_bar(20),$("#reddit_login").click(function(){window.location=e.nextURL})):"reddit_requirements"===e.stage?(!1!==e.redditReqs&&$.each(e.redditReqs,function(e,s){passFail("reddit_"+e,s)}),$("#reddit_requirements div").hide(),$("#reddit_requirements div."+(e.failed?"failed":"passed")).show(),progress_bar(30,e.failed)):"discord_oauth"===e.stage?(progress_bar(60),$("#discord_login").click(function(){window.location=e.nextURL})):"discord_requirements"===e.stage?(!1!==e.DiscordReqs&&$.each(e.DiscordReqs,function(e,s){passFail("discord_"+e,s)}),$("#discord_requirements div").hide(),$("#discord_requirements div."+(e.failed?"failed":"passed")).show(),progress_bar(90,e.failed)):"complete"===e.stage?progress_bar(100):"failed"===e.stage?($("#error_msg").html(e.error),void 0!==e.reload&&e.reload&&$("#failed button").off("click").on("click",function(){location.reload()})):Swal.fire({type:"error",title:"Something went wrong :(",text:"Please reload the page."}),$("#"+e.stage).show(),$("span.handle-container").hide(),$.each(e.handles,function(e,s){$(".handle_"+e).show().find(".handle").html(s)})}function mainload(){began?$.get("?run=1&acknowledged="+Ack,function(e){process(e)}):($(".stages").hide(),$("#reddit_requirements, #discord_requirements, #start").show())}function btnDis(e){$(e).addClass("disabled").prop("disabled",!0)}function dataRefresh(a){btnDis(a),p=$(a).attr("data-product"),$.get("?refresh="+p,function(e){if($(a).prop("disabled",!1).removeClass("disabled"),void 0!==e.retimeout&&e.retimeout){var s=moment().add(e.retimeout,"seconds"),i=s.diff(moment(),"minutes"),t=s.diff(moment(),"seconds")-60*i,r=(1<=i?i+" mins":"")+(1<=i&&1<=t?" and ":"")+(1<=t?t+" second"+(1===t?"":"s"):"");Swal.fire({type:"warning",title:"You cannot refresh this frequently",html:"<p>"+("reddit"===p?'Slow your roll there, chief. We can\'t have the reddit API becoming too <span class="badge badge-secondary" style="background-color: #ff4400;">HOT <i class=\'fas fa-burn\'></i></span>.':"Hold it right there, Butch Cassidy. This isn't a shootout with the API.")+'</p><p class="small">You can request a refresh from '+p+" after 5 minutes from your last request. Available again "+(1<=r.length?"in "+r:"now")+".</p>"})}else void 0!==e.stage&&e.stage?process(e):($("p.refresh_"+p).show().find("span").html(moment().format("MMMM Do \\at HH:mm:ss")),mainload())})}$("#begin").click(function(){if($("#tos_agree").length&&!$("#tos_agree").prop("checked"))return Swal.fire({text:"You must agree to the TOS & Privacy Policy",type:"warning"});began=!0,mainload()}),$("#reddit_passed").click(function(){btnDis(this),Ack=2,mainload()}),$("#btn_complete").click(function(){btnDis(this),Ack=3,mainload()}),$("button.refresh").click(function(){dataRefresh(this)}),$(function(){mainload()});
</script>
</body>
</html>