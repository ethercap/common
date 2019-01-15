// ethercap底层js
window.yii.ethercap = (function($) {
    var pub = {
        isActive: true,
        init: function() {
            initDataMethods();
        },
        //发起ajax请求，会自动处理返回格式
        ajax: function(method, url, params, success, fail) {
            if(!fail) {
                fail = function(xhr, status, error){
                    alert(error);
                };
            }
            $.ajax({
                url:url,
                type:method,
                data:params,
                success: function(result,status,xhr){
                    if(result.code == 0) {
                        if(success) {
                            success(result.data, status, xhr);
                        }
                    }else {
                        fail(xhr, status, result.message);
                    }
                },
                error: function(xhr,status,error) {
                    fail(xhr, status, error);
                }
            }); 
        },
        //判断是否为移动端
        isMobile: function() {
            return navigator.userAgent.match('/Android|webOS|iPhone|iPod|BlackBerry/i');
        },
        isWeixin: function(){
            var ua = window.navigator.userAgent.toLowerCase();
            return ua.match(/MicroMessenger/i) == 'micromessenger';
        },

        //存储数据
        saveLocal: function(key, value) {
            window.localStorage[key] = JSON.stringify(value);
        },
        //获取数据
        getLocal: function(key) {
            var obj = window.localStorage[key]; 
            if(obj) {
                obj = JSON.parse(obj);
            }
            return obj;
        },
        //删除数据
        removeLocal: function(key) {
            window.localStorage.removeItem(key);
        },
        setIframeHeight: function (iframe) {
            if (iframe) {
                var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;
                if (iframeWin.document.body) {
                    iframe.height = iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
                }
            }
        },
    };
    function initDataMethods() {
        //如果form带了yt-autosubmit的class则自动提交
        document.domain = "ethercap.com";
        var handler = function(event){
            var $this = $(this);
            if(!$this.hasClass("yt-exclude-submit")) {
                $(this).parentsUntil("form").parent().submit();
            }
        }; 
        $(document).on('change.yii', 'form.yt-autosubmit input, form.yt-autosubmit select, form.yt-autosubmit textarea', handler);
    }
    return pub;
})(window.jQuery);
