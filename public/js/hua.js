// 自己封装layer.open,这样封装的原因是,不想改原项目的代码,如果写在某个js里,通过layui加载的话,需要在每个页面中layui.use这个js
;!function (win) {
    var hua = function () {
    };
    win.hua = new hua();

    layui.use(['jquery','miniAdmin'], function () {
        var miniAdmin = layui.miniAdmin;
        var area = null;
        /**
         * 如果是手机端,默认弹出的是100%,如果不是,默认是宽高都是80%
         * @param title
         * @param url
         * @param w
         * @param h
         */
        hua.prototype.open = function (title, url, w, h) {
            if (title == null || title === '') {
                title = false;
            }
            if (url == null || url === '') {
                url = "/404";
            }
            if (w == null || w === '') {
                // 宽度默认80%
                w = 80;
            }
            if (h == null || h === '') {
                // 高度默认80%
                h = 80;
            }
            var openOptions = {
                title: title,
                type: 2,
                fix: false,
                maxmin: true,
                area: miniAdmin.checkMobile() ? ['100%', '100%'] : [w + '%', h + '%'],
                shadeClose: true,
                shade: 0.4,
                content: url
            };
            layer.open(openOptions);
        };
    });

}(window);
