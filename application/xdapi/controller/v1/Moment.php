<?php
/**
 * Created by PhpStorm.
 * User: Sweet Jiao
 * Date: 2018/12/9
 * Time: 13:37
 */

namespace app\xdapi\controller\v1;


use app\lib\exception\MomentsException;
use app\lib\exception\ParameterException;
use app\xdapi\controller\BaseController;
use app\xdapi\model\WhFriends;
use app\xdapi\model\WhMoments;
use app\xdapi\service\Token;
use app\xdapi\validate\MomentNew;
use app\xdapi\service\Moment as MomentService;
use app\xdapi\validate\PagingParameter;

class Moment extends BaseController
{
    public function addMoment()
    {
        $request = (new MomentNew())->goCheck();
        $moment_img = $request->file('moment_img');
        $title = $request->param('title');
        $location = $request->param('location');
        if (!empty($moment_img)) {
            if (is_object($moment_img)) {
                throw new ParameterException([
                   'msg' => '上传图片参数错误',
                ]);
            }
            foreach ($moment_img as $key => $value) {
                if(!MomentService::checkImg($value)) {
                    throw new ParameterException([
                        'msg' => '上传图片参数错误',
                    ]);
                }
            }
        }

        $uid = Token::getCurrentUid();
        //上传动态
        $data = MomentService::releaseMoment($uid,$moment_img,$title,$location);
        return $this->xdreturn($data);
    }

    public function getHot($page = 1, $size = 10)
    {
        (new PagingParameter())->goCheck();
        $uid = Token::getCurrentUid();
        $pagingMoments = WhMoments::getHotMoments($uid, $page, $size);
        if ($pagingMoments->isEmpty()) {
            throw new MomentsException([
                'msg' => '热门动态已见底线',
                'errorCode' => 70001,
            ]);
        }
        $data = $pagingMoments->toArray();
        return json([
            'error_code' => 'Success',
            'data' => $data,
            'current_page' => $pagingMoments->getCurrentPage(),
        ]);
    }

    public function getFollow($page = 1, $size = 10)
    {
        (new PagingParameter())->goCheck();
        $uid = Token::getCurrentUid();
        //获取好友id列表
        $friends = WhFriends::getFriendListId($uid);
        $friends_ids = '';
        foreach($friends as $key => $value) {
            $friends_ids .= $value->friend_id.",";
        }
        $friends_ids = rtrim($friends_ids);
        $pagingMoments = WhMoments::getFollowMoments($uid, $friends_ids, $page, $size);
        if ($pagingMoments->isEmpty()) {
            throw new MomentsException([
                'msg' => '关注动态已见底线',
                'errorCode' => 70002,
            ]);
        }
        $data = $pagingMoments->toArray();
        return json([
            'error_code' => 'Success',
            'data' => $data,
            'current_page' => $pagingMoments->getCurrentPage(),
        ]);
    }
}