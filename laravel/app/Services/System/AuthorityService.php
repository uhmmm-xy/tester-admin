<?php

namespace App\Services\System;

use App\Utils\Tree;
use App\Services\Service;
use App\Utils\ResultHelper;
use Illuminate\Support\Facades\DB;
use App\Models\System\AuthorityModel;
use Symfony\Component\HttpFoundation\Response;


class AuthorityService extends Service
{
    use ResultHelper;
    protected $model;

    public function __construct(AuthorityModel $model)
    {
        $this->model = $model;
    }

    /**
     * 重写添加数据
     * @param array $data
     * @return ResultHelper
     */
    public function create(array $data)
    {
        try {
            // 查重
            $checkRepeat = $this->model->where('authority_id', $data['authority_id'])->first();
            if ($checkRepeat) {
                return $this->failed(Response::HTTP_BAD_REQUEST, "角色ID重复,请更改！", []);
            }
            $result = $this->model->fill($data)->save();
            $result = $this->success(Response::HTTP_OK, '添加数据成功！', $result);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 重写获取所有角色
     * @param array $pageInfo
     * @param array $pageInfo
     * @return ResultHelper
     */
    public function list(array $pageInfo, array $searchInfo)
    {
        try {
            $result = $this->model->get()->toArray();
            $result = Tree::makeTree($result, ['primary_key' => 'authority_id']);
            $result = $this->success(Response::HTTP_OK, '获取分页数据成功！', ["list" => $result]);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 重写指定ID删除数据
     * @param string $id
     * @return ResultHelper
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // 查询是否有子角色
            $hasChiled = $this->model->where('parent_id', $id)->get()->toArray();
            if (count($hasChiled) > 0) {
                return $this->failed(Response::HTTP_BAD_REQUEST, "该角色包含子角色，不能删除");
            }
            $result = $this->model->destroy($id);
            // TODO:同时删除权限里的数据
            $result = $this->success(Response::HTTP_OK, '删除数据成功', $result);
            DB::commit();
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
            DB::rollBack();
        }
        return $result;
    }
}
