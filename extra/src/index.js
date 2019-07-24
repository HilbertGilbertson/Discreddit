function passFail(eid, pass) {
    $('#' + eid + ' .check').hide();
    $('#' + eid + ' .' + (pass ? 'pass' : 'fail')).show();
}

function progress_bar(p, d) {
    if (d !== undefined && d) {
        d = 100 - p;
    }
    $('#progress_bar').css('width', p + '%');
    $('#progress_danger').css('width', (d >= 1 ? d : 0) + '%');
}

function process(r) {
    $('.stages').hide();
    $('button.disabled').prop('disabled', false).removeClass('disabled');
    if (r.stage === "reddit_oauth") {
        progress_bar(20);
        $('#reddit_login').click(function () {
            window.location = r.nextURL;
        });
    } else if (r.stage === "reddit_requirements") {
        if (r.redditReqs !== false) {
            $.each(r.redditReqs, function (i, e) {
                passFail('reddit_' + i, e)
            });
        }
        $('#reddit_requirements div').hide();
        $('#reddit_requirements div.' + (r.failed ? 'failed' : 'passed')).show();
        progress_bar(30, r.failed);
    } else if (r.stage === "discord_oauth") {
        progress_bar(60);
        $('#discord_login').click(function () {
            window.location = r.nextURL;
        });
    } else if (r.stage === "discord_requirements") {
        if (r.DiscordReqs !== false) {
            $.each(r.DiscordReqs, function (i, e) {
                passFail('discord_' + i, e)
            });
        }
        $('#discord_requirements div').hide();
        $('#discord_requirements div.' + (r.failed ? 'failed' : 'passed')).show();
        progress_bar(90, r.failed);
    } else if (r.stage === "complete") {
        progress_bar(100);
    } else if (r.stage === "failed") {
        $('#error_msg').html(r.error);
        if (r.reload !== undefined && r.reload)
            $('#failed button').off('click').on('click', function () {
                location.reload();
            });
    } else {
        Swal.fire({'type': 'error', 'title': 'Something went wrong :(', 'text': 'Please reload the page.'});
    }
    $('#' + r.stage).show();

    $('span.handle-container').hide();
    $.each(r.handles, function (i, e) {
        $('.handle_' + i).show().find('.handle').html(e);
    })
}

function mainload() {
    if (began) {
        $.get('?run=1&acknowledged=' + Ack, function (r) {
            process(r);
        })
    } else {
        $('.stages').hide();
        $('#reddit_requirements, #discord_requirements, #start').show();
    }
}

function btnDis(b) {
    $(b).addClass('disabled').prop('disabled', true);
}

function dataRefresh(b) {
    btnDis(b);
    p = $(b).attr('data-product');
    $.get('?refresh=' + p, function (res) {
        $(b).prop('disabled', false).removeClass('disabled');
        if (res.retimeout !== undefined && res.retimeout) {
            var f = moment().add(res.retimeout, 'seconds'), m = f.diff(moment(), 'minutes'),
                s = f.diff(moment(), 'seconds'), r = s - (m * 60),
                left = (m >= 1 ? m + ' mins' : '') + (m >= 1 && r >= 1 ? ' and ' : '') + (r >= 1 ? r + ' second' + (r === 1 ? '' : 's') : '');
            Swal.fire({
                type: 'warning',
                title: 'You cannot refresh this frequently',
                html: "<p>" + (p === "reddit" ? "Slow your roll there, chief. We can't have the reddit API becoming too <span class=\"badge badge-secondary\" style=\"background-color: #ff4400;\">HOT <i class='fas fa-burn'></i></span>." : "Hold it right there, Butch Cassidy. This isn't a shootout with the API.") + "</p><p class=\"small\">You can request a refresh from " + (p) + " after 5 minutes from your last request. Available again " + (left.length >= 1 ? 'in ' + left : "now") + ".</p>"
            })
        } else if (res.stage !== undefined && res.stage) {
            process(res)
        } else {
            $('p.refresh_' + p).show().find('span').html(moment().format('MMMM Do \\at HH:mm:ss'));
            mainload();
        }
    })
}

$('#begin').click(function () {
    if ($('#tos_agree').length && !$('#tos_agree').prop('checked')) {
        return Swal.fire({'text': 'You must agree to the TOS & Privacy Policy', 'type': 'warning'});
    }
    began = true;
    mainload();
});

$('#reddit_passed').click(function () {
    btnDis(this);
    Ack = 2;
    mainload();
});

$('#btn_complete').click(function () {
    btnDis(this);
    Ack = 3;
    mainload();
});

$('button.refresh').click(function () {
    dataRefresh(this)
});

$(function () {
    mainload()
});