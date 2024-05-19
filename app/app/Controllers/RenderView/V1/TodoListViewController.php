<?php

namespace App\Controllers\RenderView\V1;

use App\Controllers\BaseController;
use monken\TablesIgniter;
use App\Models\V1\TodoListsModel;

class TodoListViewController extends BaseController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
    }

    /**
     * Load the todo list page.
     *
     * @return void
     */
    public function todoListPage()
    {
        $data = [
            'name' => $this->session->get("user")['name'],
        ];

        return view('TodoList/TodoList', $data);
    }

    /**
     * Get page use datatable.
     *
     * @return void
     */
    public function getDatatableData()
    {
        $user = $this->session->get("user");

        // 設定快取 key
        $cacheKey = 'TodoListViewController_getDatatableData_' . sha1($user['key']);

        // 建立快取實體
        $cache = \Config\Services::cache();

        // 檢查快取文件是否存在且在有效時間內（例如快取持續 10 分鐘）
        if ($cachedData = $cache->get($cacheKey)) {
            return json_decode($cachedData, true);
        }


        $table = new TablesIgniter();
        $todoListsModel = new TodoListsModel();

        $dataTableField = ["t_key", "t_title", "t_content"];

        $table->setTable($todoListsModel->getAllTodoByUserBuilder($user["key"]))
              ->setDefaultOrder("t_key", "DESC")
              ->setSearch($dataTableField)
              ->setOrder($dataTableField)
              ->setOutput([
                  "t_key", "t_title", "t_content", $this->getDataTableActionButton()
              ]);

        $datatableData = $table->getDatatable();

        // 將數據保存到快取中，並設 ttl(過期時間) 為 10 分鐘
        $cache->save($cacheKey, json_encode($datatableData), 600);

        return $datatableData;
    }

    /**
     * Get the datatable used button.
     *
     * @return void
     */
    public function getDataTableActionButton()
    {
        $closureFunction = function ($row) {
            $key = $row["t_key"];
            return <<<EOF
                <button class="btn btn-outline-info" onclick="todoComponent.show('{$key}')"  data-toggle="modal" data-target="#modifyModal">Modify</button>
                <button class="btn btn-outline-danger" onclick="todoComponent.delete('{$key}')">Delete</button>
            EOF;
        };
        return $closureFunction;
    }
}
