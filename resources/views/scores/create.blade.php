@extends('common.layout')

@section('content')
    <div class="x-nav">
    <span class="layui-breadcrumb">
        <a href="">首页</a>
        <a href="">成绩管理</a>
        <a><cite>成绩录入</cite></a>
    </span>
        <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right"
           onclick="location.reload()" title="刷新">
            <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
        </a>
    </div>
    <div class="layui-fluid">
        <div class="layui-row">
            <div class="layui-col-xs10 layui-col-xs-offset1">
                <form class="layui-form">
                    <div class="layui-form-item">
                        <div class="layui-col-xs6" style="text-align: center;">
                            <div class="layui-inline">

                                @if (count($exams))
                                    <label class="layui-form-label" style="width: 100px;">选择考试</label>
                                    <div class="layui-input-inline">
                                        <select name="exam_id" lay-verify="required" lay-search="">
                                            @foreach($exams as $exam)
                                                <option value="{{$exam->id}}">{{$exam->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">可模糊搜索,已添加的考试不显示</div>
                                @else
                                    <div class="layui-form-mid layui-word-aux">你已添加全部考试成绩</div>
                                @endif
                            </div>
                        </div>
                        <div class="layui-col-xs6" style="text-align: center;">
                            <div class="layui-inline">
                                <label class="layui-form-label" style="width: 100px;">选择学生</label>
                                <div class="layui-input-inline">
                                    <select name="student_id" lay-verify="required" lay-search="">
                                        @foreach($students as $student)
                                            <option value="{{$student->id}}">{{$student->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="layui-form-mid layui-word-aux">可模糊搜索,学生只能选择自己</div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-card">
                        <div class="layui-card-body ">
                            <table class="layui-table">
                                <colgroup>
                                    <col width="50">
                                    <col width="50">
                                    <col>
                                </colgroup>
                                <thead>
                                <tr>
                                    <th>课程</th>
                                    <th>分数</th>
                                </tr>
                                </thead>
                                @foreach($courses as $course)
                                    <tr>
                                        <td>{{$course->name}}</td>
                                        <td>
                                            <input type="hidden" name="course_id[]" value="{{$course->id}}">
                                            <input type="number" lay-verify="required|score" name="score[]"
                                                   class="layui-input">
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>

                    <div class="layui-form-item" style="text-align: center;">
                        <button class="layui-btn" lay-filter="save" lay-submit="">提交</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        layui.use(['form', 'layer'],
            function () {
                $ = layui.jquery;

                var form = layui.form,
                    layer = layui.layer;
                //自定义验证规则
                form.verify({
                    score: function (value) {
                        if (value < 0 || value > 150) {
                            return '成绩应在0-150分之间'
                        }
                    }
                });
                //监听提交
                form.on('submit(save)',
                    function (data) {
                        $.post('{{route('score.store')}}', data.field, function (res) {
                            if (res.status === 'success') {
                                layer.alert(res.msg, {icon: 6}, function (index) {
                                    layer.close(index);
                                    window.location.reload();
                                });
                            } else {
                                layer.alert(res.msg, {icon: 5});
                            }
                        });
                        return false;
                    });
            });
    </script>
@stop
