<?php
// 手动复制到config目录，并且在app.php中添加服务提供者
return [
    'app_id'=>env('BAIDU_AIP_APP_ID',null),
    'api_key'=>env('BAIDU_AIP_API_KEY',null),
    'secret_key'=>env('BAIDU_AIP_SECRET_KEY',null),
    'group_id'=>env('BAIDU_AIP_GROUP_ID',null),
];
