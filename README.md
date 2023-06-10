### 简介
最近做员工人脸打卡系统，自己封装了一下百度人脸识别SDK，方便以后重复利用。

暂时只封装了一部分接口，具体有哪些方法，看继承自官方sdk的`Aoding9\BaiduAip\AipFace`这个类，每个方法都有中文注释

为了避免覆写sdk，封装的方法名以Api结尾，用法与百度的官方文档相同，点进sdk也有中文注释，其余未封装的方法，直接按官方文档即可调用。

基于原有api，简化了传参和异常处理，例如matchFacesByUrl是对match的封装，参数从4个减少到2个。

人脸比对示例：
![百度人脸识别SDK的简单封装](https://cdn.learnku.com/uploads/images/202306/06/78338/HtKitETh6B.png!large)
![百度人脸识别SDK的简单封装](https://cdn.learnku.com/uploads/images/202306/06/78338/rO79NSwFDz.png!large)

### 安装
`composer require aoding9/laravel-baidu-aip`

因为官方源下载太慢了，国内镜像又有各种问题可能导致安装失败，可以把以下代码添加到composer.json，直接从github安装
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/aoding9/laravel-baidu-aip"
    }
  ]
}
```

官方源（速度慢）

`composer config repo.packagist composer https://packagist.org`



### 配置

在.env中添加如下配置项

```
BAIDU_AIP_APP_ID=
BAIDU_AIP_API_KEY=
BAIDU_AIP_SECRET_KEY=
BAIDU_AIP_GROUP_ID=
```

配置项一般无需修改，如需自定义，使用`php artisan vendor:publish --provider="Aoding9\BaiduAip\BaiduAipServiceProvider"`发布baiduAip.php到config目录
```php
return [
    'app_id'=>env('BAIDU_AIP_APP_ID',null),
    'api_key'=>env('BAIDU_AIP_API_KEY',null),
    'secret_key'=>env('BAIDU_AIP_SECRET_KEY',null),
    'group_id'=>env('BAIDU_AIP_GROUP_ID',null),
];
```

关于BAIDU_AIP_GROUP_ID：默认用户组id，如果调用相关接口时不传用户组id，会以此作为默认值

### 使用

首先从容器中获取服务，然后获取aipFace实例，然后使用实例中的方法

1、两张人脸图片比对相似度`matchFacesByUrl`
```php
use Aoding9\BaiduAip\BaiduAipService;

function test() {
    //$aipFace = app('baiduAip')->aipFace(); // 别名
    $aipFace = app(BaiduAipService::class)->aipFace(); 
    $score = $aipFace
        ->matchFacesByUrl(
            $url1 = 'https://pix2.tvzhe.com/thumb/star/0/221/260x346.jpg',
            $url2 = 'https://n.sinaimg.cn/sinacn10114/40/w2000h2840/20190226/7aa0-htptaqe7306666.jpg'
        );
    dd($url1, $url2, '吴京1和吴京2相似度' . $score);
}


```
2、在用户组中搜索人脸对应的用户，例如人脸打卡，拍照后判断是哪个用户打的卡`searchApi`
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
            $staff = Staff::findOrFail($staffId);

        } catch (\Exception $e) {
            throw new \Exception('未匹配到人员信息',$e->getCode());
        }
    
        return $staff;
    }
}

// 前端上传人脸图片，根据图片地址，检索用户模型
$face = request()->input('face');
$staff = Staff::getStaffByFaceImage($face);
```
3、用户组管理
```php
use Aoding9\BaiduAip\BaiduAipService;

 function test() {
        $aipFace = app(BaiduAipService::class)->aipFace();
        // 不传参则使用env的默认用户组id
        dd($aipFace->groupAddApi());  // 新增用户组
        dd($aipFace->groupDeleteApi()); // 删除用户组
        dd($aipFace->getGroupListApi()); // 获取用户组列表
        dd($aipFace->getGroupUsersApi()); // 获取用户组的用户id列表
    }

```


4、结合模型观察者，管理用户资料`addUserApi|updateUserApi|deleteUserApi`
```php
// 创建、更新和删除用户
namespace App\Observers;
use Aoding9\BaiduAip\BaiduAipService;
use App\Models\Staff;


class StaffObserver {
   // 创建用户时，往用户组注册用户，并将头像添加到用户人脸库，绑定用户id（如果之前没创建用户组，需要先创建用户组）
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
