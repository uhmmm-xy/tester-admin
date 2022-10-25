<?php

namespace App\Services;

use App\Utils\ResultHelper;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Service
{
    use ResultHelper;
    protected $model;

    /**
     * 模糊查询Key
     */
    protected $likeSearch = "";

    /**
     * 排序规则[]
     * 第一个值为排序字段
     * 第二个值为排序规则 ASC|DESC
     */
    protected $sort = [];

    /**
     * 获取所有数据
     * @param array $data
     * @return ResultHelper
     */
    public function all(array $data)
    {
        try {
            $result = $this->model->where($data)->get()->toArray();
            $result = $this->success(Response::HTTP_OK, '获取全部数据成功！', ["menus" => $result]);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 获取所有数据
     * @param array $pageInfo
     * @param array $searchInfo
     * @return ResultHelper
     */
    public function list(array $pageInfo, array $searchInfo)
    {
        try {
            // 备注：这里的模糊查询不能命中索引，适合小数量数据查询,大数量查询请覆盖自己实现
            if ($this->likeSearch != "" && isset($searchInfo[$this->likeSearch])) {
                $like = $searchInfo[$this->likeSearch];
                unset($searchInfo[$this->likeSearch]);
                $result = $this->model->where($searchInfo)->where($this->likeSearch, "like", "%" . $like . "%")
                    ->orderBy('id')->paginate($pageInfo['pageSize'])->toArray();
            } else if ($this->sort) {
                $result = $this->model->where($searchInfo)->orderBy(($this->sort)[0], ($this->sort)[1])->paginate($pageInfo['pageSize'])->toArray();
            } else {
                $result = $this->model->where($searchInfo)->orderBy('id')->paginate($pageInfo['pageSize'])->toArray();
            }
            $result = $this->tableData(Response::HTTP_OK, '获取分页数据成功！', $result);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 添加数据
     * @param array $data
     * @return ResultHelper
     */
    public function create(array $data)
    {
        try {
            $result = $this->model->fill($data)->save();
            $result = $this->success(Response::HTTP_OK, '添加数据成功！', $result);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 指定ID删除数据
     * @param string $id
     * @return ResultHelper
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $result = $this->model->destroy($id);
            $result = $this->success(Response::HTTP_OK, '删除数据成功', $result);
            DB::commit();
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
            DB::rollBack();
        }
        return $result;
    }

    /**
     * 指定ID查询数据
     * @param string $id
     * @return ResultHelper
     */
    public function find($id)
    {
        try {
            $result = $this->model->find($id);
            $result = $this->success(Response::HTTP_OK, '查询数据成功', $result);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }

    /**
     * 指定ID更新数据
     */
    public function update($id, $data)
    {
        try {
            $model = $this->model->find($id);
            $result = $model->update($data);
            $result = $this->success(Response::HTTP_OK, '编辑数据成功', $result);
        } catch (\Exception $ex) {
            $result = $this->failed(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
        return $result;
    }
}
