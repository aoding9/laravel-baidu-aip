<?php
/**
 * @Desc baiduAip的人脸相关sdk封装
 * @Desc 名字以Api结尾的方法是封装的api方法，api传参看官方文档，或者找到sdk的方法定义处，有中文注释
 * @User yangyang
 * @Date 2023/5/18 17:14
 */

namespace Aoding9\BaiduAip;

use Illuminate\Support\Facades\Log;

class AipFace extends \AipFace {
    /**
     * @Desc 封装AipFace，调用更方便
     * @param $response
     * @return mixed
     * @Date 2023/5/18 17:33
     */
    protected function parseResponse($response) {
        $response = $response ?? [];
        // 请求成功，但是未获得期望的结果，抛异常
        if (!array_key_exists('error_code', $response) || $response['error_code'] !== 0) {
            $msg = $response['error_msg'] ?? '请求出错';
            $code = (int)($response['error_code'] ?? -1);
            if (function_exists('throwBusinessErr')) {
                throwBusinessErr($msg, $code);
                Log::error('baiduAip请求出错', compact('code', 'msg'));
            } else {
                throw new \Exception($msg ?? '请求出错', $code);
            }
        }
        return $response;
    }
    
    public $faceToken;
    
    /**
     * @Desc 通过url比对两张人脸的相似度，没有相似度则返回-1
     * @param $image1
     * @param $image2
     * @return int|mixed
     * @Date 2023/5/18 17:33
     */
    public function matchFacesByUrl($image1, $image2) {
        return $this->matchApi($image1, 'URL', $image2, 'URL');
    }
    
    /**
     * @Desc 根据face_token和图片url比对人脸
     * @param $faceToken
     * @param $image2
     * @return int|mixed
     * @Date 2023/5/24 9:51
     */
    public function matchFacesByUrlAndFaceToken($faceToken, $image2) {
        return $this->matchApi($faceToken, 'FACE_TOKEN', $image2, 'URL');
    }
    
    /**
     * @Desc 比对两张人脸的相似度，没有相似度则返回-1
     * @param $face1Image
     * @param $face1Type
     * @param $face2Image
     * @param $face2Type
     * @return int|mixed
     * @Date 2023/5/18 17:33
     */
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
    
    /**
     * @Desc 创建用户组
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:31
     */
    public function groupAddApi() {
        return $this->parseResponse($this->groupAdd($this->getGroupId()));
    }
    
    /**
     * @Desc 删除用户组
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:31
     */
    public function groupDeleteApi() {
        return $this->parseResponse($this->groupDelete($this->getGroupId()));
    }
    
    /**
     * @Desc 获取用户组列表
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:31
     */
    public function getGroupListApi() {
        return $this->parseResponse($this->getGroupList());
    }
    
    public $groupId;
    
    /**
     * @Desc 获取默认用户组id（来自env配置项）
     * @return mixed
     * @Date 2023/6/7 10:32
     */
    public function getGroupId() {
        return $this->groupId ?? $this->setGroupId(app(BaiduAipService::class)->getConfig('group_id'));
    }
    
    public function setGroupId($groupId) {
        return $this->groupId = $groupId;
    }
    
    /**
     * @Desc 给用户组添加用户
     * @param        $image
     * @param        $userId
     * @param null   $groupId
     * @param string $imageType
     * @param array  $options
     * @return int|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:32
     */
    public function addUserApi($image, $userId, $groupId = null, $imageType = null, $options = []) {
        return $this->parseResponse($this->addUser($image, $imageType ?? 'URL', $groupId ?? $this->getGroupId(), $userId, $options)) ?? 0;
    }
    
    /**
     * @Desc 更新用户人脸库
     * @param        $image
     * @param        $userId
     * @param null   $groupId
     * @param string $imageType
     * @param array  $options
     * @return int|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:33
     */
    public function updateUserApi($image, $userId, $groupId = null, $imageType = null, $options = []) {
        $options['action_type'] = $options['action_type'] ?? 'REPLACE'; // 文档默认为UPDATE,我改为了REPLACE
        return $this->parseResponse($this->updateUser($image, $imageType ?? 'URL', $groupId ?? $this->getGroupId(), $userId, $options)) ?? 0;
    }
    
    /**
     * @Desc 从用户组删除用户
     * @param      $userId
     * @param null $groupId
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:33
     */
    public function deleteUserApi($userId, $groupId = null) {
        return $this->parseResponse($this->deleteUser($groupId ?? $this->getGroupId(), $userId));
    }
    
    /**
     * @Desc 根据人脸图片，从用户组检索用户信息
     * @param            $image
     * @param string     $imageType
     * @param array|null $groupIdList
     * @param array      $options
     * @return mixed
     * @throws \Exception
     * @Date 2023/6/7 10:33
     */
    public function searchApi($image, $imageType = null, ?array $groupIdList = null, array $options = []) {
        $options["match_threshold"] = $options["match_threshold"] ?? 80;
        $response = $this
            ->parseResponse(
                $this->search($image, $imageType ?? 'URL', implode(',', $groupIdList ?? [$this->getGroupId()]), $options)
            );
        // Log::info($image, $response);
        return $response['result']['user_list'][0]['user_id'] ?? null;
    }
    
    /**
     * @Desc 获取用户组的用户列表
     * @param null  $groupId
     * @param array $options
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:34
     */
    public function getGroupUsersApi($groupId = null, $options = []) {
        return $this->parseResponse($this->getGroupUsers($groupId ?? $this->getGroupId(), $options));
    }
    
    /**
     * @Desc 获取用户信息
     * @param int  $userId
     * @param null $groupId
     * @return array|mixed
     * @throws \Exception
     * @Date 2023/6/7 10:34
     */
    public function getUserApi($userId, $groupId = null) {
        return $this->parseResponse($this->getUser($userId, $groupId ?? $this->getGroupId()));
    }
}
