### 安装
`composer require aoding9/laravel-baidu-aip:dev-master`

### 配置
配置文件如下，一般无需修改，直接到env中进行配置
```php
// baiduAip.php
return [
    'app_id'=>env('BAIDU_AIP_APP_ID',null),
    'api_key'=>env('BAIDU_AIP_API_KEY',null),
    'secret_key'=>env('BAIDU_AIP_SECRET_KEY',null),
    'group_id'=>env('BAIDU_AIP_GROUP_ID',null),
];
```
请在.env中添加如下配置项
```
BAIDU_AIP_APP_ID=
BAIDU_AIP_API_KEY=
BAIDU_AIP_SECRET_KEY=
BAIDU_AIP_GROUP_ID=
```

### 使用

两张人脸图片比对相似度
```php
use Aoding9\BaiduAip\BaiduAipService;

function test() {
    //$aipFace = app('baiduAip')->aipFace();
    $aipFace = app(BaiduAipService::class)->aipFace();
    $score = $aipFace
        ->matchFacesByUrl(
            $url1 = 'https://pix2.tvzhe.com/thumb/star/0/221/260x346.jpg',
            $url2 = 'https://n.sinaimg.cn/sinacn10114/40/w2000h2840/20190226/7aa0-htptaqe7306666.jpg',
        );
    dd($url1, $url2, '吴京1和吴京2相似度' . $score);
    // dd($aipFace->groupAddApi());
    // dd($aipFace->groupDeleteApi());
    // dd($aipFace->getGroupListApi());
    // dd($aipFace->getGroupUsersApi()['result']['user_id_list']);
}


```

在用户组中搜索人脸对应的用户，例如人脸打卡，拍照后判断是哪个用户打的卡
```php

use Illuminate\Database\Eloquent\Model;
use Aoding9\BaiduAip\BaiduAipService;

class Staff extends Model {
    /**
     * @Desc 根据人脸，匹配人员model，需要提前增加用户组，并且把用户的人脸注册进去，绑定userid
     * @param string $face 上传的图片url
     */
    public static function getStaffByFaceImage($face) {
        $aipFace = app(BaiduAipService::class)->aipFace();
        try {
            $staffId = $aipFace->searchApi($face)[0]['user_id'];
        } catch (\Exception $e) {
            throw new \Exception('未匹配到人员信息',$e->getCode());
        }
    
        if (!$staff = Staff::find($staffId)) {
            throw new \Exception('未匹配到人员信息');
        }
        return $staff;
    }
}

// 前端上传人脸图片，根据图片地址，检索用户模型
$face = request()->input('face');
$staff = Staff::getStaffByFaceImage($face);
```

结合模型观察者，管理用户资料
```php
// 创建、更新和删除用户
namespace App\Observers;
use Aoding9\BaiduAip\BaiduAipService;
use App\Models\Staff;


class StaffObserver {
   // 创建用户时，创建用户，并将头像注册到用户人脸库，绑定用户id
    public function created(Staff $model) {
        $aipFace = app(BaiduAipService::class)->aipFace();
        $aipFace->addUserApi($model->avatar_url, $model->id);
    }

  // 头像变更时，更新用户的人脸库
    public function updating(Staff $model) {
        if ($model->isDirty($model->avatar) && $model->avatar) {
            $aipFace = app(BaiduAipService::class)->aipFace();
            $aipFace->updateUserApi($model->avatar_url, $model->id);
        }
    }

    // 删除用户时，注销用户组里的用户
    public function deleted(Staff $model) {
        $aipFace = app(BaiduAipService::class)->aipFace();
        $aipFace->deleteUserApi($model->id);
    }
}
```
