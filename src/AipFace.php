<?php
/**
 * @Desc
 * @User yangyang
 * @Date 2023/5/18 17:14
 */

namespace Aoding9\BaiduAip;

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
            throw new \Exception($response['error_msg'] ?? '请求出错', $response['error_code'] ?? -1);
        }
        return $response;
    }

    // public $faceToken;

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

    public function groupAddApi() {
        return $this->parseResponse($this->groupAdd($this->getGroupId()));
    }

    public function groupDeleteApi() {
        return $this->parseResponse($this->groupDelete($this->getGroupId()));
    }

    public function getGroupListApi() {
        return $this->parseResponse($this->getGroupList());
    }

    public $groupId;

    public function getGroupId() {
        return $this->groupId ?? $this->setGroupId(app(BaiduAipService::class)->getConfig('group_id'));
    }

    public function setGroupId($groupId) {
        return $this->groupId = $groupId;
    }

    public function addUserApi($image, $userId, $groupId = null, $imageType = 'URL', $options = []) {
        return $this->parseResponse($this->addUser($image, $imageType, $groupId ?? $this->getGroupId(), $userId, $options))['face_token'] ?? 0;
    }

    public function updateUserApi($image, $userId, $groupId = null, $imageType = 'URL', $options = []) {
        return $this->parseResponse($this->updateUser($image, $imageType, $groupId ?? $this->getGroupId(), $userId, $options))['face_token'] ?? 0;
    }

    public function deleteUserApi($userId, $groupId = null) {
        return $this->parseResponse($this->deleteUser($groupId ?? $this->getGroupId(), $userId));
    }

    public function searchApi($image, $imageType = 'URL', ?array $groupIdList = null, array $options = []) {
        $options["match_threshold"] = 80;
        return $this
                   ->parseResponse(
                       $this->search($image, $imageType, implode(',', $groupIdList ?? [$this->getGroupId()]), $options)
                   )['result']['user_list'];
    }

    public function getGroupUsersApi($groupId = null, $options = []) {
        return $this->parseResponse($this->getGroupUsers($groupId ?? $this->getGroupId(), $options));
    }
}
