<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use App\Models\UserModel;
use Gate;
use Hash;
use Schema;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class User extends Controller
{

    public function index()
    {
        return view('user.index');
    }

    public function getUser(Request $request)
    {
        $page = $request->input('page');
        $limit = $request->input('limit');
        $start = $request->input('birth_start');
        $end = $request->input('birth_end');
        $keyword = $request->input('keyword');

        $offset = ($page - 1) * $limit;
        $is_admin = Gate::allows('isAdmin');
        $query = UserModel::query();
        if ($is_admin) {
//            是管理员不看自己的信息
            $query->where('is_admin', '<>', 1);
        } else {
//            不是管理员,只能看到自己的信息
            $query->where('uid', '=', auth()->user()->uid);
        }
        if ($start) {
            $query->whereDate('birth', '>', $start);
        }
        if ($end) {
            $query->whereDate('birth', '<', $end);
        }
        if ($keyword) {
            // 查找关键词
            $jishuMap = config('modelmap.jishuMap');
            if (in_array($keyword, $jishuMap)) {
                // 寄宿信息单独搜索,必须全词匹配,因为寄宿的信息在数据库中存储的是1,2,3
                $query->where('jishu', '=', array_search($keyword, $jishuMap));
            } else {
                // 获取users表中所有字段
                $temp = Schema::getColumnListing('users');
                // 去除不想要查找的字段
                $temp = array_diff($temp, ['id', 'is_admin', 'rember_token','password', 'created_at', 'updated_at']);
                $sql = [];
                foreach ($temp as $t) {
                    $sql[] = "IFNULL($t,'')";
                }
                $sql = join(',', $sql);
                // 利用mysql的concat函数将所有字段的值拼接成字符串,然后使用like来查找
                $query->whereRaw("CONCAT($sql) LIKE '%$keyword%'");
            }
        }
        $data = $query->offset($offset)->paginate($limit)->toArray();
        $res['data'] = $data['data'];
        $res['code'] = 0;
        $res['count'] = $data['total'];
        $res['msg'] = '获取数据失败';

        return $res;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Gate::denies('isAdmin')) {
            return view('user.noper');
        }
        $jishuMap = config('modelmap.jishuMap');

        return view('user.create', compact('jishuMap'));
    }

    /**
     * 插入学生的家庭主要成员
     * @param  Request  $request
     * @param  UserModel  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function insertParent(Request $request, UserModel $user)
    {
        // 家庭主要成员
        if ($request->has('parent')) {
            $parents_input = $request->only('parent')['parent'];
            $parents = [];
            foreach ($parents_input as $key => $val) {
                foreach ($val as $k => $v) {
                    $parents[$k][$key] = $v;
                }
            }
            // 判断parents中的数据合法性
            foreach ($parents as $p) {
                if ( ! (isset($p['name']) && isset($p['age']) && isset($p['relation']))) {
                    return $this->fail('姓名,年龄,关系为必填项,请先填写');
                }
            }
            $user->parents()->delete();
            $user->parents()->createMany($parents);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'uid' => 'required|unique:users',
                'name' => 'required',
                'phone' => 'required',
                'sysid' => 'required',
                'birth' => 'required',
                'minzu' => 'required',
            ]
        );
        if (Gate::denies('isAdmin')) {
            return $this->fail('您无权添加学生');
        }
        // 学生本人信息
        $student = $request->except('parent', 'is_admin');
        $student['password'] = Hash::make('123456');
        $res = $user = UserModel::create($student);
        // 家庭主要成员
        $this->insertParent($request, $user);
        if ($res) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($id)
    {
        $user = UserModel::with('parents')->find($id);
        if (Gate::denies('is_own', $user)) {
            return view('user.noper');
        }

        return view('user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        $user = UserModel::with('parents')->find($id);
        if (Gate::denies('is_own', $user)) {
            return view('user.noper');
        }
        $jishuMap = config('modelmap.jishuMap');

        return view('user.edit', compact('user', 'jishuMap'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = UserModel::find($id);

        if (Gate::denies('is_own', $user)) {
            return $this->fail('您无权修改不是自己的信息');
        }
        // 家庭主要成员
        $this->insertParent($request, $user);
        // 学生本人信息
        $student = $request->except('parent');
        $res = $user->update($student);
        if ($res) {
            return $this->success('提交成功');
        } else {
            return $this->fail('提交失败');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if (Gate::denies('isAdmin')) {
            return $this->fail('您无权删除学生');
        }
        $res = UserModel::destroy($id);// 删除学生会关联删除他的家庭成员
        if ($res) {
            return $this->success('删除成功');
        }

        return $this->fail('删除失败');
    }

    public function delParent(Request $request, $id)
    {
        $user = UserModel::find($id);
        if (Gate::denies('is_own', $user)) {
            return $this->fail('您无权删除不是自己的家庭成员');
        }
        $p_id = $request->input('pid');
        $parent = $user->parents()->where('id', '=', $p_id);
        $res = $parent->delete();
//        $res = ParentsModel::destroy($p_id);// 可以直接这样,更快,但这里为了练习model一对多
        if ($res) {
            return $this->success('删除成功');
        }

        return $this->fail('删除失败');
    }

    public function export()
    {
        if (Gate::denies('isAdmin')) {
            return view('user.noper');
        }

        return Excel::download(new UsersExport, '学生个人信息表.xlsx');
    }
}
