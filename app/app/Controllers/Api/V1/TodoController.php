<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\V1\TodoListsModel;
use CodeIgniter\API\ResponseTrait;
use App\Entities\TodoListsEntity;
use CodeIgniter\Validation\ValidationInterface;

class TodoController extends BaseController
{
    use ResponseTrait;

    /**
     * TodoListsModel
     *
     * @var TodoListsModel
     */
    protected TodoListsModel $todoListsModel;

    /**
     * validation instance.
     *
     * @var ValidationInterface
     */
    private ValidationInterface $validation;

    public function __construct()
    {
        $this->todoListsModel = new TodoListsModel();
        $this->validation = \Config\Services::validation();
    }

    /**
     * [Get] /todo
     * Get all todo list data.
     *
     * @return void
     */
    public function index()
    {
        // Find the data from database.
        $todoList = $this->todoListsModel->where("m_key", $this->userData["key"])
            ->findAll();

        $returnData = [];

        // Extract only the required fields from each todo item.
        foreach ($todoList as $todo) {
            $returnData[] = [
                "title"   => $todo->t_title,
                "content" => $todo->t_content,
                'key'     => $todo->t_key,
            ];
        }

        return $this->respond([
            "msg"  => "success",
            "data" => $returnData
        ]);
    }

    /**
     * [GET] /todo/{key}
     *
     * @param integer|null $key
     * @return void
     */
    public function show(?int $key = null)
    {
        if ($key === null) {
            return $this->failNotFound("Enter the the todo key");
        }

        // Find the data from database.
        $todo = $this->todoListsModel->where("m_key", $this->userData["key"])
            ->find($key);

        if ($todo === null) {
            return $this->failNotFound("Todo is not found.");
        }

        // Define the return data structure.
        $returnData = [
            "title"   => $todo->t_title,
            "content" => $todo->t_content,
            'key'     => $todo->t_key,
        ];

        return $this->respond([
            "msg" => "success",
            "data" => $returnData
        ]);
    }

    /**
     * [POST] /todo
     * Create a new todo data into database.
     *
     * @return void
     */
    public function create()
    {
        // 設置驗證規則
        $rules = [
            'title' => 'required|min_length[3]',
            'content' => 'required'
        ];

        // 驗證資料
        if (!$this->validate($rules)) {
            return $this->fail($this->validation->getErrors(), 400);
        }

        // Get the data from request.
        $data    = $this->request->getJSON();
        $title   = $data->title;
        $content = $data->content;

        // Create a new entity instance and populate it.
        $todo = new TodoListsEntity();

        $todo->t_title   = $title;
        $todo->t_content = $content;
        $todo->m_key     = $this->userData["key"];

        // Insert data into database using ORM.
        if (!$this->todoListsModel->save($todo)) {
            return $this->fail("Create failed.");
        }

        $todoListData = [
            'title'   => $todo->t_title,
            'content' => $todo->t_content
        ];

        $this->clearCache($this->userData["key"]);

        return $this->respond([
            "msg"  => "Create successfully",
            "data" => $todoListData
        ]);
    }

    /**
     * [PUT] /todo/{key}
     *
     * @param integer|null $key
     * @return void
     */
    public function update(?int $key = null)
    {
        // 設置驗證規則
        $rules = [
            'title' => 'permit_empty|min_length[3]',
            'content' => 'permit_empty'
        ];

        // 驗證資料
        if (!$this->validate($rules)) {
            return $this->fail($this->validation->getErrors(), 400);
        }

        // Get the  data from request.
        $data    = $this->request->getJSON();
        $title   = $data->title   ?? null;
        $content = $data->content ?? null;

        if ($key === null) {
            return $this->failNotFound("Key is not found.");
        }

        // Get the will update data.
        $willUpdateData = $this->todoListsModel->where(
            "m_key",
            $this->userData["key"]
        )->find($key);

        if ($willUpdateData === null) {
            return $this->failNotFound("This data is not found.");
        }

        // Update the entity.
        if ($title !== null) {
            $willUpdateData->t_title = $title;
        }

        if ($content !== null) {
            $willUpdateData->t_content = $content;
        }

        // Save the updated entity using ORM.
        if (!$this->todoListsModel->save($willUpdateData)) {
            return $this->fail("Update failed.");
        }

        $this->clearCache($this->userData["key"]);

        return $this->respond([
            "msg" => "Update successfully"
        ]);
    }

    /**
     * [DELETE] /todo/{key}
     *
     * @param integer|null $key
     * @return void
     */
    public function delete(?int $key = null)
    {
        if ($key === null) {
            return $this->failNotFound("Key is not found.");
        }

        // Find the existing entity.
        $todo = $this->todoListsModel->where(
            'm_key',
            $this->userData["key"]
        )->find($key);

        if ($todo === null) {
            return $this->failNotFound("This data is not found.");
        }

        // Do delete action.
        $isDeleted = $this->todoListsModel->delete($key);

        if ($isDeleted === false) {
            return $this->fail("Delete failed.");
        }

        $this->clearCache($this->userData["key"]);

        return $this->respond([
            "msg" => "Delete successfully"
        ]);
    }

    /**
     * 清除 Redis 快取方法
     *
     * @param integer $userKey
     * @return void
     */
    private function clearCache($userKey)
    {
        $cacheKey = 'TodoListViewController_getDatatableData_' . sha1($userKey);
        $cache = \Config\Services::cache();
        $cache->delete($cacheKey);
    }
}
