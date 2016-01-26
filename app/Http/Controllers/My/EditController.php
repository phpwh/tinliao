<?php

namespace App\Http\Controllers\My;

use App\Models\Skill;
use App\Models\User_skill;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\User_shokushu;
use App\Models\Shokushu;
use App\Models\User_kinmuchi;
use App\Models\Kinmuchi;
use App\Models\Province;
use App\Models\Skill_category;
use App\Models\Resume;
use App\Models\Keitai;

class EditController extends Controller
{
    private $user;
    //登录后,用户主页
    public function __construct()
    {

        if(!session()->has('user')) {
            $user = User::find(5);
            session(['user' => $user]);
        }else{
            $this->user = session('user');
        }

        view()->share(['user' =>session('user')]);
//        dump(session('user'));
}
    function index()
    {
        $user_shokushus = User::with('user_shokushus.shokushu')->find($this->user['id']);
        $shokushus = $user_shokushus['user_shokushus'];
        $user_kinmuchi = User::with('user_kinmuchis.kinmuchi')->find($this->user['id']);
//                return($user_kinmuchi);
        $kinmuchis = $user_kinmuchi['user_kinmuchis'];
        $user_keitais = User::with('user_keitais.keitai')->find($this->user['id']);
        $keitais = $user_keitais['user_keitais'];
//        return ($keitais);


        return view('my.edit.edit',['shokushus' => $shokushus,'kinmuchis' => $kinmuchis,'keitais' => $keitais]);
    }


    //相同路由，不同函数，各种编辑...!!!
    //功能试验
    function edit(Request $request)
    {
//        dump($request->all());
        switch ($request->submitted) {
            case 'edit_name':
                return $this->update_edit_name($request);
                break;
            case 'career':                   //编辑各种信息列表
                return $this->career();
                break;

            case 'skill':                   //显示修改职业技能
                return $this->skill();
                break;
            case 'skill_form':              //执行修改职业技能
                return $this->skill_form();
                break;

            case 'rireki_delete':           //删除职务经历
                $this->rireki_delete();
                break;
            case 'block_scout':
                return $this->skill();
                break;
            case 'rireki_add':              //职务经历追加
                return $this->rireki_add();
                break;

            case 'mail':                    //希望的工作条件,是否接受邮件
                return $this->mail();
                break;

        }
    }

    //编辑各种信息列表


    //删除职务经历
    function rireki_delete()
    {
    }


    //编辑用户信息
    function edit_name()
    {
        return view('my.edit.edit_name');
    }

    //更新用户信息
    function update_edit_name(Request $request)
    {
//        return ($request->all());
        $user = User::find($this->user['id']);
//        $data = array_add($request->except('id'));
        $data = $request->only('name','kana','sex','email','m_email','m_domain');
//        return ($data);
        $user->update($data);
        return  redirect("/my/edit/edit");
    }

    //编辑用户web履历
    function edit_resume()
    {
        $provinces = Province::get();
        $skill_categories = Skill_category::with(['skill.user_skills' =>function($query){
                            $query->where('user_id',$this->user['id'])
                                ->where('value','>','0');
                            }])
                            ->orderBy('sort_order','asc')
                            ->get();
//        return $skill_categories;
        $resumes = Resume::with('shokushu')->with('keitai')->where('user_id',$this->user['id'])->orderBy('syear', 'desc','smonth','desc')
        ->get();
        $shokushus = Shokushu::orderby('sort_order','asc')->get();
        $keitais = Keitai::orderby('sort_order','asc')->get();
        return view('my.edit.edit_resume',['provinces' => $provinces,'resumes' => $resumes,'shokushus' => $shokushus,'keitais' => $keitais,'skill_categories'=>$skill_categories]);
    }

    //更新用户履历
    function update_resume(Request $request)
    {
        $user_data = $request->only('name','kana','sex','birthday_year','birthday_month','birthday_day','ken','jusho','jusho2','tel','tel2','email','m_email','m_domain','g_name','g_gakubu','g_year','g_type','shokureki');
        $user = User::find($this->user['id']);
        $user->update($user_data);
        $resumes = $request->only('resume');
        $resume_column = ['office', 'syear', 'smonth', 'eyear', 'emonth', 'r_shokushu', 'r_keitai', 'job_content'];
        foreach ($resumes['resume']['id'] as $k => $v) {
            $resume = [];
            foreach ($resume_column as $column) {
                $resume["$column"] = $resumes['resume']["$column"]["$k"];
            }
            Resume::where("id",$v)->update($resume);
        }
        return redirect('/my/edit/edit_resume');
    }

    //追加履历
    public function add_resume()
    {
        $shokushus = Shokushu::orderby('sort_order','asc')->get();
        $keitais = Keitai::orderby('sort_order','asc')->get();
        return view('my.edit.resume_add',['shokushus' => $shokushus,'keitais' => $keitais]);
    }

    //存储追加履历
    public function store_add_resume(Request $request)
    {
//        return $request->all();
        Resume::create($request->all());
        return redirect('/my/edit/edit_resume');
    }

    public function delete_resume($resume_id)
    {
        Resume::where('id',$resume_id)->delete();
        return redirect('/my/edit/edit_resume');
    }
    //修改密码
    function change_passwd(Request $request)
    {
        dump($request->all());
        return view('my.edit.change_passwd');
    }


    //修改职业技能
    function edit_skill()
    {
        $skill_categories = Skill_category::with('skill')
            ->orderBy('sort_order','asc')
            ->get();
//        return $skill_categories;
        $user_skills = User_skill::where('user_id',$this->user['id'])->get();
        $my_skills = [];
        foreach ($user_skills as $skill) {
            $my_skills["$skill->skill_id"] = $skill->value;
        }
//        return ($user_skills);
//        return ($my_skills);
        return view('my.edit.skill',['skill_categories' => $skill_categories,'user_skills' => $user_skills,'my_skills' => $my_skills]);
    }

    public function update_skill(Request $request)
    {
//        return $request->all();
        $user_id = $this->user->id;
        User_skill::where("user_id", $user_id)->delete();       //删除原始数据

        //技能表
        foreach ($request->except(['s_other']) as $skill => $value) {
            $user_skill = [];
            $user_skill['user_id'] = $user_id;
            $user_skill['skill_id'] = $skill;
            $user_skill['value'] = $value;
            User_skill::create($user_skill);
        }

        //补充技能
        User::where('id', $user_id)->update(['s_other' => $request->s_other]);
        return redirect('/my/edit/edit_resume');
    }
    //修改职业技能
    function rireki_add()
    {
        return view('my.edit.rireki_add');
    }


    ////希望的工作条件,是否接受邮件
    function mail()
    {
        return view('my.edit.mail');
    }


}
