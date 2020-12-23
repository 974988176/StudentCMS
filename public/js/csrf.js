// 全局设置ajax,csrf,让提示更加个性化
layui.use("jquery", function() {
    var $ = layui.jquery;
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            var status = XMLHttpRequest.status;
            var responseText = XMLHttpRequest.responseText;
            var msg = '不好，有错误';
            switch (status) {
                case 400:
                    msg = responseText !== '' ? responseText : '失败了';
                    break;
                case 401:
                    msg = responseText !== '' ? responseText : '你没有权限';
                    break;
                case 403:
                    msg =  '你没有权限执行此操作!';
                    break;
                case 404:
                    msg = '你访问的操作不存在';
                    break;
                case 406:
                    msg = '请求格式不正确';
                    break;
                case 410:
                    msg = '你访问的资源已被删除';
                    break;
                case 422:
                    var errors = $.parseJSON(XMLHttpRequest.responseText);

                    if (errors instanceof Object) {
                        var m = '';
                        $.each(errors, function(index, item) {
                            if (item instanceof Object) {
                                $.each(item, function(index, i) {
                                    m = m + i + '<br>';
                                });
                            }
                        });
                        msg = m;
                    }
                    break;
                case 429:
                    msg = '超出访问频率限制';
                    break;
                case 500:
                    msg = '500服务器内部错误';
                    break;
                default:
                    return true;
            }

            layer.msg(msg, {time: 3000, icon: 5});
        }
    });
});
