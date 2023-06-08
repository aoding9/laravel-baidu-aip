### 简介
最近做一个员工人脸打卡系统，自己封装了一下百度人脸识别SDK，方便以后重复利用。
暂时只封装了一部分接口，具体有哪些方法可用，看继承自sdk同名类的`Aoding9\BaiduAip\AipFace`这个类，为了和sdk区分，封装的方法名以Api结尾，传参看官方文档，或者找到sdk的方法定义处，有中文注释，没封装的方法，直接按官方文档也可以调用。

封装方法基于原有api，简化了传参和异常处理，例如matchFacesByUrl是对match的封装
```php
 // sdk的方法传参比较多，至少4个，同时需要把响应的score返回出来
    public function matchApi($face1Image, $face1Type, $face2Image, $face2Type) {
        return $this->parseResponse($this->match([
                                                     [
                                                         'image'      => $face1Image,
                                                         'image_type' => $face1Type,
                                                     ], [
                                                         'image'      => $face2Image,
                                                         'image_type' => $face2Type,
                                                     ],
                                                 ]))['result']['score'] ?? 0;
    }
// 因为url类型的人脸比对比较常用，所以单独定义一个方法，只传两个图片url即可
	public function matchFacesByUrl($image1, $image2) {
        return $this->matchApi($image1, 'URL', $image2, 'URL');
    }
```
外部调用：
![百度人脸识别SDK的简单封装](https://cdn.learnku.com/uploads/images/202306/06/78338/HtKitETh6B.png!large)
![百度人脸识别SDK的简单封装](https://cdn.learnku.com/uploads/images/202306/06/78338/rO79NSwFDz.png!large)

### 安装
`composer require aoding9/laravel-baidu-aip`

如果安装失败，可能是composer镜像的问题，我切换为官方源就正常了，不过要魔法

官方源

`composer config -g repo.packagist composer https://packagist.org`

阿里云镜像

`composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/`

因为官方源下载太慢了，如果不想切换镜像，可以把以下代码添加到composer.json，这样就能直接从github安装了
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

结合模型观察者，管理用户资料
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
