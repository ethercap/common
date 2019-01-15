/*
 *  *
 *   *   Ethercap
 *    *
 *     */

//判断是否为移动端
function isMobile()
{
    return navigator.userAgent.match('/Android|webOS|iPhone|iPod|BlackBerry/i');
}

$(document).ready(function () {
    if (($.cookie('inspinia_mini_navbar') == 1) && !isMobile()) {
        $('body').addClass('mini-navbar');
    }
    // 在移动端手机展示侧边栏会比较奇怪，因此，在点击后隐藏侧边栏
    if(isMobile()) {
      $('body').removeClass("mini-navbar");
    }

    $('.navbar-minimalize').click(function () {
        if ($("body").hasClass("mini-navbar")) {
            $.cookie('inspinia_mini_navbar', 1, { expires: 365, path: '/' });
        } else {
            $.cookie('inspinia_mini_navbar', 0, { expires: 365, path: '/' });
        }
    });

    $('#crud-datatable-pjax').on('pjax:end', function() {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
        return false;
    });
});

